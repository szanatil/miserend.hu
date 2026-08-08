<?php

use Symfony\Component\Panther\PantherTestCase;

/**
 * #640: a /terkep elsőre hibára futott, ctrl+R-re viszont megjavult.
 *
 * Két, egymást takaró sorrendi hiba volt:
 *  - a script végén állt egy onMapMoved() hívás, ami az auto-geolokációs ágon még nézet
 *    nélküli térképen futott -> "Set map center and zoom first",
 *  - a 'load' esemény viszont a nem-geolokációs ágon túl KORÁN sült el, amikor a rétegek
 *    és az svgIcons még nem léteztek -> "Cannot access 'svgIcons' before initialization".
 * Frissítésre azért működött, mert a gyorsítótárazott pozíció megnyerte a versenyt.
 *
 * Ez a teszt azt őrzi, hogy a térkép ELSŐ betöltésre is hibátlanul álljon fel.
 */
final class MapInitializationTest extends PantherTestCase {

    private function client() {
        return static::createPantherClient(
            ['external_base_uri' => getenv('PANTHER_EXTERNAL_BASE_URI') ?: 'http://127.0.0.1:8000'],
            [],
            ['browser' => static::CHROME]
        );
    }

    /*
     * A /terkep az egyetlen hely, ahol az auto-geolokáció be van kapcsolva — épp ez az
     * ág szállt el. A böngésző a tesztben nem ad pozíciót, tehát a fallback fut le.
     */
    public function testMapPageLoadsWithoutJavascriptErrorsOnFirstVisit(): void {
        $client = $this->client();
        $client->request('GET', '/terkep');

        // A nézet a geolokációs fallback után áll be (3 mp-es időzítő), várjuk meg.
        $client->wait(10)->until(static function ($driver) {
            return $driver->executeScript('return !!(window.mymap && window.mymap._loaded);');
        });

        $errors = $client->executeScript(
            <<<'JS'
            return (window.__mapErrors || []).slice(0, 10);
            JS
        );
        self::assertSame([], $errors, "JS-hiba a térkép betöltésekor: " . json_encode($errors));

        $hasCenter = $client->executeScript('return !!window.mymap.getCenter();');
        self::assertTrue($hasCenter, 'A térkép nézet nélkül maradt.');
    }

    /*
     * A templom-adatlap kis térképe a nem-geolokációs ágat járja (kap `center`-t).
     * Itt a másik hiba (svgIcons a rétegek előtt) jött volna elő.
     */
    public function testChurchPageMapLoadsWithoutJavascriptErrors(): void {
        $client = $this->client();
        $client->request('GET', '/templom/1');

        $client->wait(10)->until(static function ($driver) {
            return $driver->executeScript('return !!(window.mymap && window.mymap._loaded);');
        });

        $errors = $client->executeScript('return (window.__mapErrors || []).slice(0, 10);');
        self::assertSame([], $errors, "JS-hiba a templom-térkép betöltésekor: " . json_encode($errors));
    }
}
