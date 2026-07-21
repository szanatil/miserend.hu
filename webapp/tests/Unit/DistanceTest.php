<?php

use PHPUnit\Framework\TestCase;

require_once PATH . 'classes/distance.php';

/**
 * #172: a szomszédság (distances) számító-motor tiszta geometriája + a
 * Mapquest-független visszaesés. A DB-orchestrációt (MupdateChurch) nem itt
 * teszteljük - az integrációs terep -, de a számítás magját igen.
 */
class DistanceTest extends TestCase {

    private function d(): \Distance {
        return new \Distance();
    }

    public function testGetRawDistanceForOneHundredthDegreeLatitude() {
        // 0.01° szélesség-különbség ~ 1112 m légvonalban.
        $from = ['lat' => 47.4979, 'lon' => 19.0402];
        $to   = ['lat' => 47.5079, 'lon' => 19.0402];
        $this->assertEqualsWithDelta(1112, $this->d()->getRawDistance($from, $to), 8);
    }

    public function testGetRawDistanceForIdenticalPointIsZero() {
        $p = ['lat' => 47.4979, 'lon' => 19.0402];
        $this->assertSame(0, $this->d()->getRawDistance($p, $p));
    }

    public function testGetBBoxContainsCenterPoint() {
        $p = ['lat' => 47.4979, 'lon' => 19.0402];
        $bbox = $this->d()->getBBox($p, 5000);
        $this->assertGreaterThan($bbox['latMin'], $p['lat']);
        $this->assertLessThan($bbox['latMax'], $p['lat']);
        $this->assertGreaterThan($bbox['lonMin'], $p['lon']);
        $this->assertLessThan($bbox['lonMax'], $p['lon']);
    }

    public function testGetBBoxHalfWidthMatchesRadius() {
        // 5 km sugár ~ 0.045° szélességi félszélesség.
        $p = ['lat' => 47.4979, 'lon' => 19.0402];
        $bbox = $this->d()->getBBox($p, 5000);
        $this->assertEqualsWithDelta(0.0449, $bbox['latMax'] - $p['lat'], 0.005);
    }

    public function testResolveDistanceFallsBackToStraightLineWhenMapquestUnavailable() {
        // #172 lényege: Mapquest-kulcs nélkül (mint a CI-ban) a distance() eldob,
        // és a resolveDistance a légvonalbeli (haversine) értékre esik vissza -
        // így a szomszédság-frissítés sosem áll le csendben.
        $from = ['lat' => 47.4979, 'lon' => 19.0402];
        $to   = ['lat' => 47.5079, 'lon' => 19.0402];
        $raw  = $this->d()->getRawDistance($from, $to);
        $this->assertSame($raw, $this->d()->resolveDistance($from, $to, $raw));
    }
}
