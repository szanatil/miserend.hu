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
            try {
                $osm = new \OSM();
                $elements = $osm->downloadChurchesWithinBoundary($osmtype, $osmid);
            } catch (\Exception $e) {
                $return['error'] = 'Az OSM adatok lekérése nem sikerült: ' . $e->getMessage();
                header('Content-Type: application/json');
                echo json_encode($return);
                return;
            }

            // #572: ha az Overpass NEM adott vissza elemet (túlterhelt / üres / null válasz),
            // NE szinkronizáljunk üresre — a sync() alább törölné a meglévő templom-
            // társításokat, MIELŐTT az újak megérkeznének, és ha nem jönnek meg, csak
            // veszítünk. Inkább megtartjuk a meglévőt és jelezzük, hogy próbálja később.
            if (empty($elements)) {
                $return['error'] = 'Az OSM (Overpass) most nem adott vissza adatot (valószínűleg túlterhelt). A meglévő társításokat megtartjuk — próbáld újra később.';
                $return['church_ids'] = $churchIds; // a meglévő (fent lekért) lista marad
                header('Content-Type: application/json');
                echo json_encode($return);
                return;
            }

            $churchIds = [];
            foreach ($elements as $element) {
                // #410: ugyanaz a robusztus mintázat mint az osm.php/josm.php-ban.
                // Nem horgonyzott, így a http/https/www prefix nem számít; kezeli az
                // opcionális `?`-et, a =/ szeparátort és a tetszőleges hosszú id-t.
                // Az id az 1. csoportba kerül -> $match[1].
                // #510: az uj.miserend.hu-t NEM matcheljük (negatív lookbehind, hibás adat).
                preg_match('#(?<!uj\.)miserend\.hu/?\??templom(?:=|/)(\d+)#i', $element->tags->{'url:miserend'} ?? '', $match);
                if(!isset($match[1])) {
                    /*
                    * TODO: Van url:miserend, de az értéke vacak.
                    */
                    //printr($element);
                } else {
                    $churchIds[] = $match[1];
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