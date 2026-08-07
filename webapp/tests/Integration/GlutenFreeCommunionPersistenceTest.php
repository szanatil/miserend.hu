<?php

use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

final class GlutenFreeCommunionPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
    }

    public function testDetailedSettingsAndDerivedOsmValueAreSavedTogether(): void
    {
        \GlutenFreeCommunion::save(1, [
            \GlutenFreeCommunion::HOLIDAYS_KEY => 'at_end',
            \GlutenFreeCommunion::WEEKDAYS_KEY => 'ask_sacristy',
        ]);

        $attributes = DB::table('attributes')->where('church_id', 1)
            ->whereIn('key', [
                \GlutenFreeCommunion::HOLIDAYS_KEY,
                \GlutenFreeCommunion::WEEKDAYS_KEY,
                'diet:gluten_free',
            ])
            ->pluck('value', 'key');

        $this->assertSame('at_end', $attributes[\GlutenFreeCommunion::HOLIDAYS_KEY]);
        $this->assertSame('ask_sacristy', $attributes[\GlutenFreeCommunion::WEEKDAYS_KEY]);
        $this->assertSame('yes', $attributes['diet:gluten_free']);
    }

    public function testInvalidSettingIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \GlutenFreeCommunion::save(1, [\GlutenFreeCommunion::HOLIDAYS_KEY => 'invalid']);
    }
}
