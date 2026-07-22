<?php

namespace Html\Church;

/**
 * Hierarchia controller – visszaadja egy misézőhely és összes leszármazottjának ID listáját.
 * Ezt az Angular naptár widget használja a hierarchia URL-hez.
 *
 * URL: /templom/:id/hierarchia
 * Visszatér: { "ids": [42, 101, 205, 310] }
 */
class Hierarchia extends \Html\Html {

    public function __construct($path) {
        $tid = (int) $path[0];
        $church = \Eloquent\Church::find($tid);
        if (!$church) {
            throw new \Exception('Nincs ilyen templom.');
        }

        $ids = $church->descendantIds;

        header('Content-Type: application/json');
        echo json_encode(['ids' => $ids]);
        exit;
    }
}
