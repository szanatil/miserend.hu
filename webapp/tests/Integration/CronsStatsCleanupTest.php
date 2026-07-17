<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * #351: A stats_externalapi napi takarítása (\Crons::cleanExternalApiStats).
 * A 30 napnál régebbi statisztika-sorokat törli, az utolsó 30 napot megőrzi.
 *
 * Tranzakcióban fut, tearDown-ban rollback — a valós tábla érintetlen marad.
 */
class CronsStatsCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function insertStat(string $date): int
    {
        return DB::table('stats_externalapi')->insertGetId([
            'name'         => 'phpunit',
            'url'          => 'http://example.test/phpunit',
            'responsecode' => 200,
            'date'         => $date,
            'count'        => 1,
            'diff'         => 0,
        ]);
    }

    public function testDeletesRowsOlderThan30Days(): void
    {
        $oldId    = $this->insertStat(date('Y-m-d', strtotime('-45 days')));
        $recentId = $this->insertStat(date('Y-m-d', strtotime('-2 days')));

        \Crons::cleanExternalApiStats();

        $this->assertNull(
            DB::table('stats_externalapi')->where('id', $oldId)->first(),
            'A 45 napos sornak törlődnie kell.'
        );
        $this->assertNotNull(
            DB::table('stats_externalapi')->where('id', $recentId)->first(),
            'A 2 napos sornak meg kell maradnia.'
        );
    }

    public function testKeepsExactly30DayOldRowAndDeletes31(): void
    {
        // A cutoff = date('-30 days'); a szűrő `< cutoff`, tehát a pont 30 napos
        // sor (date == cutoff) MEGMARAD, a 31 napos törlődik.
        $day30 = $this->insertStat(date('Y-m-d', strtotime('-30 days')));
        $day31 = $this->insertStat(date('Y-m-d', strtotime('-31 days')));

        \Crons::cleanExternalApiStats();

        $this->assertNotNull(
            DB::table('stats_externalapi')->where('id', $day30)->first(),
            'A pontosan 30 napos sor megmarad (a cutoff inkluzív a megőrzésre).'
        );
        $this->assertNull(
            DB::table('stats_externalapi')->where('id', $day31)->first(),
            'A 31 napos sor törlődik.'
        );
    }
}
