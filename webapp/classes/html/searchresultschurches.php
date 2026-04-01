<?php

namespace Html;

use Illuminate\Database\Capsule\Manager as DB;

class SearchResultsChurches extends Html {

    public $template = 'search/resultsChurches.twig';

    public function __construct() {
        parent::__construct();
        global $user, $config;

        $this->setTitle('Templom keresése');

        //Data for pagination        
		$params = [
            'q' => 'SearchResultsChurches',
            'kulcsszo' => \Request::Text('kulcsszo'),
            'lang' => \Request::StringArray('lang'),
            'ehm' => \Request::IntegerwDefault('ehm', 0)
        ];
                        
        $search = new \Search('churches');
        
        // Main keyword search
        if (isset($params['kulcsszo'])) {
            $search->keyword($params['kulcsszo']);
            $this->form['kulcsszo']['value'] = $params['kulcsszo'];
        } else {
             $this->form['kulcsszo']['value'] = '';
        }

        // Diocese filter
        if ($params['ehm'] > 0) {
            $ehmnev = DB::table('egyhazmegye')->where('id',$params['ehm'])->pluck('nev')[0];
            $search->addMust(["wildcard" => ['egyhazmegye.keyword' => $ehmnev ]]);
            $search->filters[] = "Egyházmegye: <b>" . htmlspecialchars($ehmnev) ." egyházmegye</b>";
        }

        // nyelvek filter
        $lang = $params['lang'];
        if (!empty($lang)) {
            $langsShould = isset($lang['should']) ? array_filter(array_map('trim', explode(',', $lang['should']))) : [];
            $langsMustNot = isset($lang['must_not']) ? array_filter(array_map('trim', explode(',', $lang['must_not']))) : [];

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
                		
        

        $url = \Pagination::qe($params, '/?' );
        $this->pagination->set($resultsCount, $url );

        $this->filters = $search->getFilters();

        if ($resultsCount < 1) {
            addMessage('A keresés nem hozott eredményt', 'info');
            return;
        } else if ($resultsCount == 1) {
            $url = '/templom/' . $results['results'][0]->id;            
            $this->redirect($url);
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
