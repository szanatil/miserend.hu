<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A NapilelkibatyuApi::extractDateInfo tiszta transzformációjának lefedése.
 *
 * Egy nap liturgikus JSON-jából {date, name, level, isSunday}-t készít. Két rögzített
 * viselkedés van benne, amit teszt véd:
 *  - level = az ELSŐ celebration szintje (celebration[0]->level),
 *  - name  = az UTOLSÓ celebration neve (end()) — ez egy korábbi fix ("ne az elsőt,
 *    hanem az utolsót vegyük"), amit regresszió könnyen visszatörne,
 *  - isSunday = date('N') == 7.
 *
 * A metódus privát; Reflectionnel hívjuk. A konstruktor üres, a jsonData public,
 * így nincs szükség hálózatra/DB-re.
 */
class NapilelkibatyuExtractTest extends TestCase
{
    private function extract($jsonData, string $date): array
    {
        $api = new \ExternalApi\NapilelkibatyuApi();
        $api->jsonData = $jsonData;
        $m = new \ReflectionMethod(\ExternalApi\NapilelkibatyuApi::class, 'extractDateInfo');
        $m->setAccessible(true);
        return $m->invoke($api, $date);
    }

    /** {date: {celebration: [ {level,name}, ... ]}} JSON-szerű objektum. */
    private function jsonFor(string $date, array $celebrations): object
    {
        return json_decode(json_encode([$date => ['celebration' => $celebrations]]));
    }

    public function testSundayWithTwoCelebrationsUsesFirstLevelAndLastName(): void
    {
        $date = '2026-07-19'; // vasárnap
        $info = $this->extract(
            $this->jsonFor($date, [
                ['level' => 13, 'name' => 'A'],
                ['level' => 6, 'name' => 'B'],
            ]),
            $date
        );

        $this->assertSame($date, $info['date']);
        $this->assertSame(13, $info['level'], 'a level az ELSŐ celebration-é');
        $this->assertSame('B', $info['name'], 'a name az UTOLSÓ celebration-é (regressziós-őr)');
        $this->assertTrue($info['isSunday']);
    }

    public function testWeekdaySingleCelebration(): void
    {
        $date = '2026-07-20'; // hétfő
        $info = $this->extract(
            $this->jsonFor($date, [['level' => 4, 'name' => 'X']]),
            $date
        );

        $this->assertSame(4, $info['level']);
        $this->assertSame('X', $info['name']);
        $this->assertFalse($info['isSunday']);
    }

    public function testMissingNameOnLastCelebrationYieldsEmptyString(): void
    {
        $date = '2026-07-20';
        // Az utolsó celebration-nek nincs neve -> name üres.
        $info = $this->extract(
            $this->jsonFor($date, [['level' => 4, 'name' => 'X'], ['level' => 2]]),
            $date
        );

        $this->assertSame('', $info['name']);
        $this->assertSame(4, $info['level']);
    }
}
