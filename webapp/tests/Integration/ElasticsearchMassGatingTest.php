<?php

use PHPUnit\Framework\TestCase;

/**
 * #306: A teljes (top-level, tids nélküli) ES mise-újragenerálás gating-logikája.
 *
 * A döntést a tiszta, DB/ES-mentes ElasticsearchApi::shouldFullReindex() hozza meg,
 * ezért itt közvetlenül, mockok nélkül tesztelhető. A tényleges I/O (index-üresség,
 * cron.lastsuccess_at, generatedPeriods max updated_at) a hívó updateMasses()-ben
 * gyűlik össze — az integrációt az ElasticsearchApiLoggerTest fedi.
 */
class ElasticsearchMassGatingTest extends TestCase
{
    /** Üres/hiányzó index (startup) mindig teljes futást kényszerít. */
    public function testEmptyIndexAlwaysReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-10 03:00:00', '2026-07-01', true),
            'Üres index esetén mindig teljes újragenerálás kell.'
        );
    }

    /** Korábbi sikeres futás hiánya (null / nulldátum) teljes futást kényszerít. */
    public function testNoPriorSuccessReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex(null, '2026-07-01', false),
            'Ha nincs korábbi sikeres futás, futni kell.'
        );
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('0000-00-00 00:00:00', '2026-07-01', false),
            'Nulldátumú lastsuccess_at is korábbi-siker-hiánynak számít.'
        );
    }

    /** Ha a periódusok a legutóbbi sikeres futás UTÁN frissültek -> futás. */
    public function testPeriodsChangedAfterLastSuccessReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-01 03:00:00', '2026-07-05', false),
            'Frissült periódus (későbbi dátum) -> teljes futás.'
        );
    }

    /** Aznapi periódus-frissítés (date-inkluzív >=) -> futás (adatbiztos irány). */
    public function testSameDayPeriodUpdateReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-05 03:00:00', '2026-07-05', false),
            'Aznapi (napi granularitású) periódus-frissítés esetén inkább fussunk le.'
        );
    }

    /** Ha semmi nem változott a legutóbbi sikeres futás óta -> SKIP. */
    public function testNoChangeSkips(): void
    {
        $this->assertFalse(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-10 03:00:00', '2026-07-01', false),
            'Változatlan periódusok + nem üres index esetén kihagyjuk a teljes futást.'
        );
    }

    public function testMassChangedSinceLastSuccessReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex(
                '2026-07-10 03:00:00',
                '2026-07-01',
                false,
                '2026-07-11'
            ),
            'Mise módosítása önmagában is teljes újraindexelést kér.'
        );
    }

    public function testIndexCreatedAfterLastSuccessReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex(
                '2026-01-26 17:58:39',
                '2026-01-06',
                false,
                '2026-01-20',
                '2026-08-06 20:00:00'
            ),
            'A cron utolsó sikere után létrejött indexet újra kell építeni.'
        );
    }

    public function testOlderIndexAndUnchangedMassesSkip(): void
    {
        $this->assertFalse(
            \ExternalApi\ElasticsearchApi::shouldFullReindex(
                '2026-07-10 03:00:00',
                '2026-07-01',
                false,
                '2026-07-01',
                '2026-06-01 12:00:00'
            )
        );
    }

    /** Ha egyáltalán nincs generatedPeriod -> ne blokkoljunk. */
    public function testNoGeneratedPeriodsReindexes(): void
    {
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-10 03:00:00', null, false),
            'GeneratedPeriod hiányában ne blokkoljunk (adatbiztos irány).'
        );
        $this->assertTrue(
            \ExternalApi\ElasticsearchApi::shouldFullReindex('2026-07-10 03:00:00', '0000-00-00', false),
            'Nulldátumú periódus-updated_at se blokkoljon.'
        );
    }
}
