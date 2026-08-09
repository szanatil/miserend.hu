<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Hibakereső üzemmódban a külső API hibája eddig MINDIG kikerült a lapra, teljes
 * verem-kiírással. A stagingen ettől csúfult el egy templomoldal: az Overpass
 * pillanatnyi elérhetetlensége miatt a látogató egy PHP-hívásláncot kapott, pedig a
 * területi adat hiánya nem akadályozza meg abban, hogy megnézze a miserendet.
 *
 * Ahol a sikertelenség VÁRT kimenet és a hívó kezeli is, ott a $quiet elnyomja a
 * kiírást — a hiba maga viszont továbbra is elérhető marad.
 */
final class ExternalApiQuietTest extends TestCase {

    private $originalDebug;

    protected function setUp(): void {
        parent::setUp();
        global $config;
        $this->originalDebug = $config['debug'] ?? 0;
        // A lapra írás csak hibakereső üzemmódban történik — épp azt teszteljük.
        $config['debug'] = 1;

        if (session_id() === '') {
            @session_start();
        }
    }

    protected function tearDown(): void {
        global $config;
        $config['debug'] = $this->originalDebug;
        parent::tearDown();
    }

    private function messageCount(): int {
        return (int) DB::table('messages')->where('sid', session_id())->count();
    }

    /** Elérhetetlen végpont, hogy a hiba biztosan bekövetkezzen. */
    private function unreachableOverpass(): \ExternalApi\OverpassApi {
        $api = new \ExternalApi\OverpassApi();
        $api->apiUrl = 'http://127.0.0.1:9/nincs-itt-semmi';
        $api->cache = false;
        $api->queryTimeout = 2;
        return $api;
    }

    public function testQuietFailureDoesNotWriteOnThePage(): void {
        $before = $this->messageCount();

        $api = $this->unreachableOverpass();
        $api->quiet = true;
        $api->downloadEnclosingBoundaries(47.5, 19.05);

        self::assertTrue($api->hasError(), 'a hívásnak el kellett hasalnia');
        self::assertSame($before, $this->messageCount(), 'csendes módban nem kerülhet üzenet a lapra');
    }

    /* A csendes mód nem nyeli el a hibát, csak nem teszi ki: a hívó lássa. */
    public function testQuietStillRecordsTheError(): void {
        $api = $this->unreachableOverpass();
        $api->quiet = true;
        $api->downloadEnclosingBoundaries(47.5, 19.05);

        self::assertTrue($api->hasError());
        self::assertNotSame('', $api->getErrorMessage());
    }

    /* Alapértelmezésben marad a régi viselkedés: hibakereső módban kiírjuk. */
    public function testLoudFailureStillWritesOnThePage(): void {
        $before = $this->messageCount();

        $api = $this->unreachableOverpass();
        $api->downloadEnclosingBoundaries(47.5, 19.05);

        self::assertTrue($api->hasError());
        self::assertGreaterThan($before, $this->messageCount(), 'csendes mód nélkül a hibának ki kell kerülnie');
    }

    /*
     * A területi adatok pótlása ilyen „várt kudarc" hely: null-lal tér vissza, és a
     * hívó ezt kezeli — közben semmit nem ír a lapra.
     */
    public function testBoundaryDownloadStaysSilentOnFailure(): void {
        global $config;
        $originalUrl = $config['overpass']['apiUrl'] ?? null;
        $config['overpass']['apiUrl'] = 'http://127.0.0.1:9/nincs-itt-semmi';

        try {
            $before = $this->messageCount();
            $result = (new \OSM())->downloadBoundaries(47.5, 19.05);

            self::assertNull($result, 'elérhetetlen Overpassnál null a helyes válasz');
            self::assertSame($before, $this->messageCount(), 'a templomoldal nem kaphat hibakiírást');
        } finally {
            if ($originalUrl === null) {
                unset($config['overpass']['apiUrl']);
            } else {
                $config['overpass']['apiUrl'] = $originalUrl;
            }
        }
    }
}
