<?php

namespace Html\Ajax;

use Illuminate\Database\Capsule\Manager as DB;

class ChurchRelationshipsInBBox extends Ajax {

    public function __construct() {
        $bbox = explode(';', $_REQUEST['bbox']);
        if (count($bbox) != 4) {
            echo json_encode(['relationships' => []]);
            return;
        }
        foreach ($bbox as $int) {
            if (!is_numeric($int)) {
                echo json_encode(['relationships' => []]);
                return;
            }
        }

        // Lekérjük az összes templomot a bbox-ban
        $churchesInBBox = \Eloquent\Church::inBBox([
            'latMin' => $bbox[0],
            'lonMin' => $bbox[1],
            'latMax' => $bbox[2],
            'lonMax' => $bbox[3]
        ])->pluck('id')->toArray();

        if (empty($churchesInBBox)) {
            echo json_encode(['relationships' => []]);
            return;
        }

        // Lekérjük az összes kapcsolatot, ahol legalább az egyik templom a bbox-ban van
        $relationships = DB::table('church_relationships')
            ->where(function($query) use ($churchesInBBox) {
                $query->whereIn('parent_church_id', $churchesInBBox)
                      ->orWhereIn('child_church_id', $churchesInBBox);
            })
            ->get();

        $return = [];

        foreach ($relationships as $rel) {
            $parentChurch = \Eloquent\Church::find($rel->parent_church_id);
            $childChurch = \Eloquent\Church::find($rel->child_church_id);

            if (!$parentChurch || !$childChurch) {
                continue;
            }

            $return[] = [
                'parent' => [
                    'id' => (int) $parentChurch->id,
                    'name' => !empty($parentChurch->names) ? $parentChurch->names[0] : $parentChurch->nev,
                    'lat' => (float) $parentChurch->lat,
                    'lon' => (float) $parentChurch->lon
                ],
                'child' => [
                    'id' => (int) $childChurch->id,
                    'name' => !empty($childChurch->names) ? $childChurch->names[0] : $childChurch->nev,
                    'lat' => (float) $childChurch->lat,
                    'lon' => (float) $childChurch->lon
                ],
                // #663: a kapcsolat típusa kivezetésre került — minden kapcsolat
                // alárendeltség, a térkép egységes stílussal rajzolja.
            ];
        }

        echo json_encode(['relationships' => $return]);
    }

}
