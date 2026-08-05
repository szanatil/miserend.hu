<?php

use PHPUnit\Framework\TestCase;

/**
 * #428: A CalMass::effectiveExperiod tiszta union-logikájának lefedése.
 *
 * A metódus az automatikusan számolt `experiod` (ütközés-elkerülés) és a kézzel
 * beállított `manual_experiod` unióját adja, JSON-string-normalizálással, dedup-pal
 * és a saját period_id kizárásával. A mise-generálás (calmass.php:204 és :407) erre
 * épül; egy csendes elrontás vagy elveszett kézi kizárásokat, vagy dupla-számolt
 * kizárásokat okozna.
 *
 * A metódus privát statikus és DB-mentes (stdClass fixture-ökkel dolgozik), ezért
 * Reflectionnel, tranzakció nélkül teszteljük. Integration-suite, mert a CalMass
 * Eloquent-modell itt garantáltan betöltődik.
 */
class CalMassEffectiveExperiodTest extends TestCase
{
    private static function eff($experiod, $manualExperiod = null, $periodId = null): array
    {
        $mass = new \stdClass();
        $mass->experiod = $experiod;
        $mass->manual_experiod = $manualExperiod;
        $mass->period_id = $periodId;

        $m = new \ReflectionMethod(\Eloquent\CalMass::class, 'effectiveExperiod');
        $m->setAccessible(true);
        return $m->invoke(null, $mass);
    }

    public function testUnionOfTwoArraysDeduplicates(): void
    {
        $this->assertSame([1, 2, 3], self::eff([1, 2], [2, 3]));
    }

    public function testJsonStringColumnsAreDecoded(): void
    {
        $this->assertSame([1, 2, 3], self::eff('[1,2]', '[3]'));
    }

    public function testSelfPeriodIsExcluded(): void
    {
        $this->assertSame([5, 7], self::eff([5, 6, 7], null, 6));
    }

    public function testAllNullYieldsEmptyArray(): void
    {
        $this->assertSame([], self::eff(null, null, null));
    }

    public function testInvalidJsonIsCoercedToEmpty(): void
    {
        // A 'not json' érvénytelen JSON -> üres; csak a [4] marad.
        $this->assertSame([4], self::eff('not json', [4]));
    }

    public function testDedupAndSelfExclusionCombined(): void
    {
        // '[10,10,20]' ∪ [20,30] = [10,20,30], majd period_id=10 kizárva -> [20,30].
        $this->assertSame([20, 30], self::eff('[10,10,20]', [20, 30], 10));
    }
}
