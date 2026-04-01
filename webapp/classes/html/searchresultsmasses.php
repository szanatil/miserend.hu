<?php

namespace Html;

use Exception;
use ExternalApi\NapilelkibatyuApi;
use Illuminate\Database\Capsule\Manager as DB;

class SearchResultsMasses extends Html {

    public function __construct() {
        parent::__construct();
        global $user, $config;

        $this->setTitle("Szentmise kereső");
        $search = new \Search('masses');

        //Data for pagination
		$params = [
            'q' => 'SearchResultsMasses',
            'varos' => \Request::Text('varos'),
            'tavolsag' => \Request::IntegerwDefault('tavolsag', 4),
            'hely' => \Request::Text('hely'),
            'kulcsszo' => \Request::Text('kulcsszo'),
            'espker' => \Request::Integer('espker'),
            'ehm' => \Request::Integer('ehm'),
            'types' => \Request::ArrayArray('types'),  // Be aware: this is a nested array with rite keys, e.g. types[rite1][should], types[rite1][must_not], types[rite2][should], etc.
            'rites' => \Request::StringArray('rites'), // Be aware: this is an array with 'should' and 'must_not' keys, e.g. rites[should], rites[must_not]
            'mikordatum' => \Request::Text('mikordatum'),
            'mikortol' => \Request::Text('mikortol'),
            'lang' => \Request::StringArray('lang'), // Be aware: this is an array with 'should' and 'must_not' keys, e.g. lang[should], lang[must_not]
            'timezone' => \Request::Text('timezone')
        ];
                
        $search->timezone =  $params['timezone'];

        // egyhazmegye filter
        if ($params['ehm'] > 0) {
            $ehmnev = DB::table('egyhazmegye')->where('id',$params['ehm'])->pluck('nev')[0];
            $search->addMust(["wildcard" => ['church.egyhazmegye.keyword' => $ehmnev ]]);
            $search->filters[] = "Egyházmegye: <b>" . htmlspecialchars($ehmnev) ." egyházmegye</b>";
        }

        // nyelvek filter
        if($params['lang']) {
            $langsShould = isset($params['lang']['should']) ? array_filter(array_map('trim', explode(',', $params['lang']['should']))) : [];
            $langsMustNot = isset($params['lang']['must_not']) ? array_filter(array_map('trim', explode(',', $params['lang']['must_not']))) : [];

            if (!empty($langsShould)) {
                $search->languages($langsShould);                                              
            }

            if (!empty($langsMustNot)) {
                $search->addMustNot([ 'terms' => ['church.nyelvek.keyword' => $langsMustNot] ]);
                $translated = array_map(function($l){ return t('LANGUAGES.'.$l); }, $langsMustNot);
                $search->filters[] = "A liturgia nyelve ne legyen <b>" . implode('</b> se <b>', $translated) . "</b>";                              
            }
        }
        
        // Main keyword search
        if ($params['kulcsszo']) {
            $search->keyword($params['kulcsszo']);
        }
    
        // Time range search
        if(isset($params['mikordatum']) AND $params['mikordatum'] != '') {
            $mikordatum = $params['mikordatum'];
            $hourFrom = ( isset($params['mikortol']) and $params['mikortol'] != '') ? $params['mikortol'] : '00:00';
            $hourTo = "23:59";
            $from = $mikordatum."T".$hourFrom.":00";
            $until = $mikordatum."T".$hourTo.":00";
        } else {
            $from = date('Y-m-d')."T00:00:00";
            $until = date('Y-m-d',strtotime("+ 6 days"))."T23:59:00";
        }
        $search->timeRange($from, $until);
        $api = new \ExternalApi\NapilelkibatyuApi();
        $this->liturgicalDays = $api->getLiturgicalDaysInRange($from, $until);
                                 
        // Process advanced rites/types filters (if provided)
        $typesReq = isset($params['types']) ? $params['types'] : [];
        $ritesReq = isset($params['rites']) ? $params['rites'] : [];

        if (!empty($typesReq) || !empty($ritesReq)) {
            // 1) Handle rites.must_not - exclude these rites entirely
            if (!empty($ritesReq['must_not'])) {
                $mustNotRites = array_filter(array_map('trim', explode(',', $ritesReq['must_not'])));
                foreach ($mustNotRites as $r) {
                    if ($r === '') continue;
                    $search->filters[] = "A rítus nem lehet: <i>" . htmlspecialchars(t($r)) . "</i>";
                    // add to query must_not
                    $search->query['bool']['must_not'][] = [ 'term' => ['rite.keyword' => $r] ];
                }
            }

            // 2) Handle rites.should - at least one of these rite+type combinations must match
            if (!empty($ritesReq['should'])) {
                $shouldRites = array_filter(array_map('trim', explode(',', $ritesReq['should'])));
                $shouldClauses = [];

                // Add a human-readable filter listing allowed rites (translated)
                if (!empty($shouldRites)) {
                    $translated = array_map(function($r){ return t($r); }, $shouldRites);
                    $search->filters[] = 'A rítus lehet <i>' . implode('</i> vagy <i>', $translated) . '</i>';
                }
                foreach ($shouldRites as $r) {
                    
                    if ($r === '') continue;
                    // Build clause requiring this rite
                    $cl = [ 'bool' => [ 'must' => [ [ 'term' => ['rite.keyword' => $r] ] ] ] ];

                    // If types specification exists for this rite, apply its should/must_not rules
                    if (!empty($typesReq[$r]) && is_array($typesReq[$r])) {
                        // parse comma separated lists
                        $tShould = [];
                        if (!empty($typesReq[$r]['should'])) {
                            if (is_array($typesReq[$r]['should'])) {
                                $tShould = $typesReq[$r]['should'];
                            } else {
                                $tShould = array_filter(array_map('trim', explode(',', $typesReq[$r]['should'])));
                            }
                            
                        }
                        $tMustNot = [];
                        if (!empty($typesReq[$r]['must_not'])) {
                            if (is_array($typesReq[$r]['must_not'])) {
                                $tMustNot = $typesReq[$r]['must_not'];
                            } else {
                                $tMustNot = array_filter(array_map('trim', explode(',', $typesReq[$r]['must_not'])));
                            }
                        }

                        // If there are positive type constraints, require that the event has at least one of them
                        if (!empty($tShould)) {
                            // use 'terms' to require any of the types
                            $shouldTerms = [];
                            foreach ($tShould as $tt) {
                                if ($tt === '') continue;
                                $shouldTerms[] = [ 'term' => ['types.keyword' => $tt] ];
                            }
                            $cl['bool']['must'][] = [ 'bool' => [ 
                                'should' => $shouldTerms, 
                                'minimum_should_match' => 1 
                            ]];                            
                        }

                        // If there are negative type constraints, add must_not for each
                        if (!empty($tMustNot)) {
                            foreach ($tMustNot as $tt) {
                                $cl['bool']['must_not'][] = [ 'term' => ['types.keyword' => $tt] ];
                            }
                        }
                        foreach($tShould as $k => $ts)  $tShould[$k] = t($ts);
                        foreach($tMustNot as $k => $ts)  $tMustNot[$k] = t($ts);

                        if (!empty($tShould) or !empty($tMustNot)) {
                            $search->filters[] = "Ha <b>".t($r)."</b> rítus, akkor  " . 
                                (!empty($tShould) ? "legyen: <b>" . implode('</b> vagy <b>', $tShould) . "</b>" : '') . 
                                (!empty($tShould) && !empty($tMustNot) ? ", de " : '') .
                                (!empty($tMustNot) ? "ne legyen: <b>" . implode('</b> vagy <b>', $tMustNot) . "</b>" : '');
                        }
                    }

                    $shouldClauses[] = $cl;
                }

                if (!empty($shouldClauses)) {
                    // Ensure at least one of the should clauses matches
                    $search->query['bool']['must'][] = [ 'bool' => [ 'should' => $shouldClauses, 'minimum_should_match' => 1 ] ];                    
                }
            }
            
        }

        $offset = $this->pagination->take * $this->pagination->active;
        $limit = $this->pagination->take;     	        
        $results = $search->getResults($offset, $limit, false);
                                        
        if ($search->total != 0) {                   
            foreach ($results as &$result) {
                $church = \Eloquent\Church::find($result->church_id);
                $result->church = $church->toArray();       
                //$result->rrule = $church->getGeneratedMassRRulesAttribute();
                
                $result->mass = \Eloquent\CalMass::find($result->mass_id)->toArray();
                if($result->mass['rrule'])
                    $rrule = new \SimpleRRule($result->mass['rrule']);
                    if(isset($rrule)) {
                        $result->mass['rrule']['readable'] = $rrule->toText();
                    }
                if(isset($result->mass['periodId'])) {
                    $result->period = \Eloquent\CalPeriod::find($result->mass['periodId'])->toArray();                    
                }
            }
        }            
       
        $url = \Pagination::qe($params, '/?' );
        $this->pagination->set($search->total, $url );

        $this->filters = $search->getFilters();

        $this->alert = (new \ExternalApi\NapilelkibatyuApi())->LiturgicalAlert(isset($mikordatum) ? $mikordatum : false);
                
        $this->template = 'search/resultsmasses.twig';
        
        $this->results = $results;                
    }

}
