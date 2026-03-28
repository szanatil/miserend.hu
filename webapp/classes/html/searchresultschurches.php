<?php

namespace Html;

use Illuminate\Database\Capsule\Manager as DB;

class SearchResultsChurches extends Html {

    public $template = 'search/resultsChurches.twig';

    public function __construct() {
        parent::__construct();
        global $user, $config;

        $this->input = $_REQUEST;

        $this->setTitle('Templom keresése');

        $search = new \Search('churches');
        
        // Main keyword search
        if (isset($this->input['kulcsszo'])) {
            $search->keyword($this->input['kulcsszo']);    
            $this->form['kulcsszo']['value'] = $this->input['kulcsszo'];        
        } else {
             $this->form['kulcsszo']['value'] = '';
        }
    
        // Diocese filter		
        $ehm = isset($_REQUEST['ehm']) ? $_REQUEST['ehm'] : 0;
        if ($ehm > 0) {
            $ehmnev = DB::table('egyhazmegye')->where('id',$ehm)->pluck('nev')[0];
            $search->addMust(["wildcard" => ['egyhazmegye.keyword' => $ehmnev ]]); 
            $search->filters[] = "Egyházmegye: <b>" . htmlspecialchars($ehmnev) ." egyházmegye</b>";                              
        }

        // nyelvek filter
        if(isset($_REQUEST['lang']) AND is_array($_REQUEST['lang'])) {
            $langsShould = isset($_REQUEST['lang']['should']) ? array_filter(array_map('trim', explode(',', $_REQUEST['lang']['should']))) : [];
            $langsMustNot = isset($_REQUEST['lang']['must_not']) ? array_filter(array_map('trim', explode(',', $_REQUEST['lang']['must_not']))) : [];

            if (!empty($langsShould)) {
                $search->addMust([ 'terms' => ['nyelvek' => $langsShould] ]);
                $translated = array_map(function($l){ return t('LANGUAGES.'.$l); }, $langsShould);
                $search->filters[] = "Amelyik templomban van liturgia <b>" . implode('</b> vagy <b>', $translated) . "</b> nyelven.";                              
            }

            if (!empty($langsMustNot)) {
                $search->addMustNot([ 'terms' => ['nyelvek' => $langsMustNot] ]);
                $translated = array_map(function($l){ return t('LANGUAGES.'.$l); }, $langsMustNot);
                $search->filters[] = "Amelyik templomban nincs liturgia <b>" . implode('</b> se <b>', $translated) . "</b> nyelven.";                              
            }
        }
        
        //Let's do the search
        $offset = $this->pagination->take * $this->pagination->active;
        $limit = $this->pagination->take;        		        
        $results = [];
        $results['results'] = $search->getResults($offset, $limit, false);                
        $resultsCount = $search->total;
                		
        //Data for pagination
		$params = [];
        $params['q'] = 'SearchResultsChurches';
		foreach( ['kulcsszo', 'lang','ehm'] as $param ) {
			if( isset($_REQUEST[$param]) AND $_REQUEST[$param] != ''  AND $_REQUEST[$param] != '0' ) {
				$params[$param] = $_REQUEST[$param];
			}
		}		
        $url = \Pagination::qe($params, '/?' );
        $this->pagination->set($resultsCount, $url );

        $this->filters = $search->getFilters();

        if ($resultsCount < 1) {
            addMessage('A keresés nem hozott eredményt', 'info');
            return;
        } else if ($resultsCount == 1) {
            $url = '/templom/' . $results['results'][0]->id;
            $event = ['Search', 'fast', ( isset($_REQUEST['varos']) ? $_REQUEST['varos'] : ''  ). $_REQUEST['kulcsszo'] . ( isset($_REQUEST['e']) ? $_REQUEST['e'] : '' ) ];  
            $this->redirectWithAnalyticsEvent($url, $event);
            return;
        } elseif ($resultsCount < $this->pagination->take * $this->pagination->active) {
            addMessage('Csupán ' . $resultsCount . " templomot találtunk.", 'info');
            return;
        }

        /* foreach ($results['results'] as $result) {
            $churchIds[] = $result->id;
        }
        $this->churches = \Eloquent\Church::whereIn('id', $churchIds)->get(); */
        $this->churches = json_decode(json_encode($results['results']), true);
        
    }

}
