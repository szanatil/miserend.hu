<?php

namespace Html\Ajax;

class ChurchesInBoundary extends Ajax {

    public function __construct() {

        $osmtype = \Request::SimpletextRequired('osmtype');
        $osmid = \Request::IntegerRequired('osmid');
        $redownload = \Request::Boolean('download');

        $return = [
            'success' => false,
        ];

        $boundary = \Eloquent\Boundary::where('osmtype', $osmtype)
                ->where('osmid', $osmid)
                ->first();

        if (!$boundary) {
            throw new \Exception('Adatbázisunkban (még) nincs ilyen terület: '.$osmtype.':'.$osmid);
            return;
        }

        $churchIds = $boundary->churches()->pluck('church_id')->toArray();

        if ($osmtype && $osmid && $redownload) {
            $osm = new \OSM();
            $elements = $osm->downloadChurchesWithinBoundary($osmtype, $osmid);
            $churchIds = [];
            foreach ($elements as $element) {                    
                preg_match('/miserend\.hu\/\?{0,1}templom(\/|=)([0-9]{1,5})/i', $element->tags->{'url:miserend'}, $match);
                if(!isset($match[2])) {
                    /*
                    * TODO: Van url:miserend, de az értéke vacak. 
                    */
                    //printr($element);                
                } else {
                    $churchIds[] = $match[2];
                }
            }

            $sync = $boundary->churches()->sync($churchIds);

            $return = $sync;
            
        }

        $churchIds = $boundary->churches()->pluck('church_id')->toArray();
        $return['church_ids'] = $churchIds;

        $return['success'] = true;

        header('Content-Type: application/json');
        echo json_encode($return);

    }

}