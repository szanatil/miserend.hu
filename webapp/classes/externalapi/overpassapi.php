<?php

namespace ExternalApi;

# http://wiki.openstreetmap.org/wiki/Overpass_API#Introduction

class OverpassApi extends \ExternalApi\ExternalApi {

    public $name = 'overpass';
    public $apiUrl = "http://overpass-api.de/api/interpreter";
	public $testQuery = 'nwr["name"="Tápiószecső"];out geom;';
    public $queryFilter;

    function buildQuery() {
        $this->rawQuery = "[out:json][timeout:" . $this->queryTimeout . "];";
        $this->rawQuery .= $this->query;
        $this->rawQuery = "?data=" . urlencode($this->rawQuery); 
    }

    function buildEnclosingBoundariesQuery($lat, $lon) {
        $this->queryFilter = "['type'='boundary' ]['disused:boundary'!~'.*']"; // A nem aktuális disued:boundary-knak nincs nevük meg ilyenek olykor, és az hibát tud generálni
        $this->buildEnclosingFeaturesQuery($lat, $lon);
    }

    function buildEnclosingFeaturesQuery($lat, $lon) {
        $this->query = "is_in(" . $lat . "," . $lon . ")->.a;"
                . "node" . $this->queryFilter . "(pivot.a);out bb center tags;"
                . "way" . $this->queryFilter . "(pivot.a);out bb center tags;"
                . "relation" . $this->queryFilter . "(pivot.a);out bb center tags;";
        $this->buildQuery();
    }

    function buildSimpleQuery($filter = false, $out = "body qt center") {  
        if ($filter) {
            $this->queryFilter = $filter;
        }
        $this->query = "("
                . "node" . $this->queryFilter . ";"
                . "way" . $this->queryFilter . ";"
                . "relation" . $this->queryFilter . ";);"
                . "out " . $out . ";";
        $this->buildQuery();
    }
	
	function buildOneEntityQuery($type, $id) {
		$this->query = "("
                . $type . "(id:" . $id . ");"
				. ");"
                . "out body qt center;";
        $this->buildQuery();
	
	}

    function downloadEnclosingBoundaries($lat, $lon) {
        $this->buildEnclosingBoundariesQuery($lat, $lon);
        $this->run();
    }

    function buildChurchesWithinBoundaryQuery($osmtype, $osmid) {        
        $this->query = $osmtype."(".$osmid.")->.rel;"
                . ".rel map_to_area->.searchArea;"
                . "( nwr[\"url:miserend\"](area.searchArea); );"
                . "out body;";

        $this->buildQuery();
    }

    function downloadChurchesWithinBoundary($osmtype, $osmid) {        
        $this->buildChurchesWithinBoundaryQuery($osmtype, $osmid);
        $this->run();
    }

	
	
    function downloadUrlMiserend() {
        $this->buildSimpleQuery("['url:miserend']");
        $this->run();
    }

}
