<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: Unit-tesztek a Distance geometriai segédfüggvényeire.
 *
 * Tiszta, I/O-mentes logika (koordináta-validáció + haversine + bounding box),
 * ami a szomszédság-számítás alapja, de eddig tesztlefedettség nélkül volt.
 * Egy csendes elrontás (pl. felcserélt határ) a distance-cront rontaná el
 * észrevétlenül.
 */
class DistanceTest extends TestCase
{
    private Distance $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new Distance();
    }

    // ─── isPoint(): koordináta-validáció ────────────────────────────────────

    public function testIsPointAcceptsValidNumericCoordinates(): void
    {
        $this->assertTrue($this->d->isPoint(['lat' => 47.5, 'lon' => 19.0]));
    }

    public function testIsPointAcceptsNumericStrings(): void
    {
        $this->assertTrue($this->d->isPoint(['lat' => '47.5', 'lon' => '19.0']));
    }

    public function testIsPointAcceptsZeroZero(): void
    {
        // A (0,0) geometriailag érvényes; a nearby.php üzleti szabálya külön
        // utasítja el — az nem a Distance dolga.
        $this->assertTrue($this->d->isPoint(['lat' => 0, 'lon' => 0]));
    }

    public function testIsPointRejectsMissingKey(): void
    {
        $this->assertFalse($this->d->isPoint(['lat' => 47.5]));
    }

    public function testIsPointRejectsEmptyString(): void
    {
        $this->assertFalse($this->d->isPoint(['lat' => '', 'lon' => 19]));
    }

    public function testIsPointRejectsNonNumeric(): void
    {
        $this->assertFalse($this->d->isPoint(['lat' => 'abc', 'lon' => 19]));
    }

    public function testIsPointRejectsOutOfRangeLatitude(): void
    {
        $this->assertFalse($this->d->isPoint(['lat' => 91, 'lon' => 0]));
    }

    public function testIsPointRejectsOutOfRangeLongitude(): void
    {
        $this->assertFalse($this->d->isPoint(['lat' => 0, 'lon' => 181]));
    }

    // ─── validatePoint(): dobás érvénytelenre ───────────────────────────────

    public function testValidatePointThrowsOnInvalid(): void
    {
        $this->expectException(\Exception::class);
        $this->d->validatePoint(['lat' => 91, 'lon' => 0]);
    }

    public function testValidatePointReturnsTrueOnValid(): void
    {
        $this->assertTrue($this->d->validatePoint(['lat' => 47.5, 'lon' => 19.0]));
    }

    // ─── getRawDistance(): haversine (méterben) ─────────────────────────────

    public function testDistanceIdenticalPointsIsZero(): void
    {
        $this->assertSame(0, $this->d->getRawDistance(
            ['lat' => 47.5, 'lon' => 19.0],
            ['lat' => 47.5, 'lon' => 19.0]
        ));
    }

    public function testDistanceOneDegreeLatitude(): void
    {
        // 1° szélesség ~ 111,2 km
        $this->assertEqualsWithDelta(
            111194.93,
            $this->d->getRawDistance(['lat' => 47, 'lon' => 19], ['lat' => 48, 'lon' => 19]),
            1.0
        );
    }

    public function testDistanceBudapestVienna(): void
    {
        $this->assertEqualsWithDelta(
            214044.0,
            $this->d->getRawDistance(
                ['lat' => 47.4979, 'lon' => 19.0402],
                ['lat' => 48.2082, 'lon' => 16.3738]
            ),
            500.0
        );
    }

    // ─── getBBox(): határoló doboz a pont körül ─────────────────────────────

    public function testBBoxIsCenteredAndOrdered(): void
    {
        $bbox = $this->d->getBBox(['lat' => 47.5, 'lon' => 19.0], 5000);

        $this->assertGreaterThan($bbox['latMin'], $bbox['latMax']);
        $this->assertGreaterThan($bbox['lonMin'], $bbox['lonMax']);
        // A pont a doboz közepén: a lat-kilengés szimmetrikus.
        $this->assertEqualsWithDelta(
            $bbox['latMax'] - 47.5,
            47.5 - $bbox['latMin'],
            1e-9
        );
    }
}
