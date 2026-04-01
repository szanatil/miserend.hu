<?php

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class SimplerruleTest extends TestCase {

    public function __construct(?string $name = null)
    {        
        parent::__construct($name);
        include_once __DIR__ . '/../classes/simplerrule.php';
    }

    // ============================================================================
    // Constructor Tests
    // ============================================================================

    public function testConstructorBasic() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'daily'
        ];
        $rule = new SimpleRRule($rrule);
        $this->assertInstanceOf(SimpleRRule::class, $rule);
    }

    public function testConstructorWithAllParameters() {
        $rrule = [
            'dtstart' => '2024-01-01 10:00:00',
            'until' => '2024-12-31 23:59:59',
            'count' => 10,
            'freq' => 'WEEKLY',
            'interval' => 2,
            'bymonth' => [1, 2, 3],
            'bymonthday' => [1, 15],
            'byweekday' => ['MO', 'WE', 'FR'],
            'bysetpos' => 1,
            'byweekno' => [1, 2, 3],
            'exdate' => ['2024-01-06', '2024-01-13']
        ];
        $rule = new SimpleRRule($rrule);
        $this->assertInstanceOf(SimpleRRule::class, $rule);
    }

    public function testConstructorWithDebugCallback() {
        $debugCalls = [];
        $callback = function($msg, $ctx) use (&$debugCalls) {
            $debugCalls[] = ['msg' => $msg, 'ctx' => $ctx];
        };

        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'daily',
            'count' => 1
        ];
        $rule = new SimpleRRule($rrule, $callback);
        $occurrences = $rule->getOccurrences();
        
        $this->assertGreaterThan(0, count($debugCalls));
    }

    // ============================================================================
    // DAILY Frequency Tests
    // ============================================================================

    public function testDailySimple() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 5
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-05', $occurrences[4]->toDateString());
    }

    public function testDailyWithUntil() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'until' => '2024-01-10'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(10, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-10', $occurrences[9]->toDateString());
    }

    public function testDailyWithInterval() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'interval' => 2,
            'count' => 5
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-03', $occurrences[1]->toDateString());
        $this->assertEquals('2024-01-05', $occurrences[2]->toDateString());
    }

    public function testDailyWithByWeekday() {
        $rrule = [
            'dtstart' => '2024-01-01', // Monday
            'freq' => 'DAILY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // Should only include Monday, Wednesday, Friday
        $this->assertCount(6, $occurrences);
        foreach ($occurrences as $occurrence) {
            $dayOfWeek = $occurrence->dayOfWeek;
            if ($dayOfWeek === 0) $dayOfWeek = 7; // Sunday as 7
            $this->assertContains($dayOfWeek, [1, 3, 5]); // Monday, Wednesday, Friday
        }
    }

    public function testDailyWithByWeekdayNumeric() {
        $rrule = [
            'dtstart' => '2024-01-01', // Monday = 1
            'freq' => 'DAILY',
            'byweekday' => [1, 3, 5],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
    }

    public function testDailyWithTime() {
        $rrule = [
            'dtstart' => '2024-01-01 14:30:45',
            'freq' => 'DAILY',
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(14, $occurrence->hour);
            $this->assertEquals(30, $occurrence->minute);
            $this->assertEquals(45, $occurrence->second);
        }
    }

    // ============================================================================
    // WEEKLY Frequency Tests
    // ============================================================================

    public function testWeeklySimple() {
        $rrule = [
            'dtstart' => '2024-01-01', // Monday
            'freq' => 'WEEKLY',
            'count' => 4
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(4, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-08', $occurrences[1]->toDateString());
        $this->assertEquals('2024-01-15', $occurrences[2]->toDateString());
        $this->assertEquals('2024-01-22', $occurrences[3]->toDateString());
    }

    public function testWeeklyWithByWeekday() {
        $rrule = [
            'dtstart' => '2024-01-01', // Monday
            'freq' => 'WEEKLY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        // All occurrences should be Monday, Wednesday or Friday
        foreach ($occurrences as $occurrence) {
            $dayOfWeek = $occurrence->dayOfWeek;
            if ($dayOfWeek === 0) $dayOfWeek = 7;
            $this->assertContains($dayOfWeek, [1, 3, 5]);
        }
    }

    public function testWeeklyWithInterval() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'interval' => 2,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-15', $occurrences[1]->toDateString());
        $this->assertEquals('2024-01-29', $occurrences[2]->toDateString());
    }

    public function testWeeklyWithUntil() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'until' => '2024-02-01'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertGreaterThan(0, count($occurrences));
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $lastOccurrence = end($occurrences);
        $this->assertLessThanOrEqual('2024-02-01', $lastOccurrence->toDateString());
    }

    public function testWeeklyWithByWeekNo() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'byweekno' => [1, 2, 3],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
    }

    public function testWeeklyWithTime() {
        $rrule = [
            'dtstart' => '2024-01-01 09:15:30',
            'freq' => 'WEEKLY',
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(9, $occurrence->hour);
            $this->assertEquals(15, $occurrence->minute);
            $this->assertEquals(30, $occurrence->second);
        }
    }

    // ============================================================================
    // MONTHLY Frequency Tests
    // ============================================================================

    public function testMonthlySimple() {
        $rrule = [
            'dtstart' => '2024-01-15',
            'freq' => 'MONTHLY',
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-15', $occurrences[0]->toDateString());
        $this->assertEquals('2024-02-15', $occurrences[1]->toDateString());
        $this->assertEquals('2024-03-15', $occurrences[2]->toDateString());
    }

    public function testMonthlyWithInterval() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'interval' => 3,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-04-01', $occurrences[1]->toDateString());
        $this->assertEquals('2024-07-01', $occurrences[2]->toDateString());
    }

    public function testMonthlyWithUntil() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'until' => '2024-06-01'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-06-01', $occurrences[5]->toDateString());
    }

    public function testMonthlyWithByWeekday() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['MO'],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        // All should be Monday
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(1, $occurrence->dayOfWeek); // Monday
        }
    }

    public function testMonthlyWithByWeekdayAndBySetpos() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['MO'],
            'bysetpos' => 1,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        // All should be first Monday of their respective months
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(1, $occurrence->dayOfWeek);
            // Should be in the first week
            $this->assertLessThanOrEqual(7, $occurrence->day);
        }
    }

    public function testMonthlyWithByWeekdayAndBySetposNegative() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['FR'],
            'bysetpos' => -1,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        // All should be last Friday of their respective months
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(5, $occurrence->dayOfWeek); // Friday
        }
    }

    public function testMonthlyWithTime() {
        $rrule = [
            'dtstart' => '2024-01-15 11:45:20',
            'freq' => 'MONTHLY',
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(11, $occurrence->hour);
            $this->assertEquals(45, $occurrence->minute);
            $this->assertEquals(20, $occurrence->second);
        }
    }

    // ============================================================================
    // YEARLY Frequency Tests
    // ============================================================================

    public function testYearlySimple() {
        $rrule = [
            'dtstart' => '2024-06-15',
            'freq' => 'YEARLY',
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-06-15', $occurrences[0]->toDateString());
        $this->assertEquals('2025-06-15', $occurrences[1]->toDateString());
        $this->assertEquals('2026-06-15', $occurrences[2]->toDateString());
    }

    public function testYearlyWithByMonth() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [1, 6, 12],
            'bymonthday' => [1],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-06-01', $occurrences[1]->toDateString());
        $this->assertEquals('2024-12-01', $occurrences[2]->toDateString());
    }

    public function testYearlyWithByMonthAndByMonthDay() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [1, 7],
            'bymonthday' => [1, 15],
            'count' => 4
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(4, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-15', $occurrences[1]->toDateString());
        $this->assertEquals('2024-07-01', $occurrences[2]->toDateString());
        $this->assertEquals('2024-07-15', $occurrences[3]->toDateString());
    }

    public function testYearlyWithInterval() {
        $rrule = [
            'dtstart' => '2024-05-20',
            'freq' => 'YEARLY',
            'interval' => 2,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-05-20', $occurrences[0]->toDateString());
        $this->assertEquals('2026-05-20', $occurrences[1]->toDateString());
        $this->assertEquals('2028-05-20', $occurrences[2]->toDateString());
    }

    public function testYearlyWithUntil() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'until' => '2026-12-31'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2025-01-01', $occurrences[1]->toDateString());
        $this->assertEquals('2026-01-01', $occurrences[2]->toDateString());
    }

    public function testYearlyLeapYear() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [2],
            'bymonthday' => [29],
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // 2024 is a leap year, but 2025 is not, so only 2024 should have Feb 29
        $this->assertCount(1, $occurrences);
        $this->assertEquals('2024-02-29', $occurrences[0]->toDateString());
    }

    public function testYearlyInvalidDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [2],
            'bymonthday' => [30],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // Feb 30 doesn't exist, so no occurrences
        $this->assertCount(0, $occurrences);
    }

    public function testYearlyWithTime() {
        $rrule = [
            'dtstart' => '2024-03-21 16:20:10',
            'freq' => 'YEARLY',
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(16, $occurrence->hour);
            $this->assertEquals(20, $occurrence->minute);
            $this->assertEquals(10, $occurrence->second);
        }
    }

    // ============================================================================
    // ExDate (Exception Dates) Tests
    // ============================================================================

    public function testExDateSingleDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 5,
            'exdate' => ['2024-01-03']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(4, $occurrences);
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-01-03', $dates);
    }

    public function testExDateMultipleDates() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 10,
            'exdate' => ['2024-01-03', '2024-01-05', '2024-01-07']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(7, $occurrences);
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-01-03', $dates);
        $this->assertNotContains('2024-01-05', $dates);
        $this->assertNotContains('2024-01-07', $dates);
    }

    public function testExDateWithWeekly() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'count' => 5,
            'exdate' => ['2024-01-08', '2024-01-22']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-01-08', $dates);
        $this->assertNotContains('2024-01-22', $dates);
    }

    public function testExDateWithMonthly() {
        $rrule = [
            'dtstart' => '2024-01-15',
            'freq' => 'MONTHLY',
            'count' => 4,
            'exdate' => ['2024-02-15']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-02-15', $dates);
    }

    // ============================================================================
    // Count and Until Limits Tests
    // ============================================================================

    public function testCountZero() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 0
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(0, $occurrences);
    }

    public function testUntilBeforeStart() {
        $rrule = [
            'dtstart' => '2024-06-01',
            'freq' => 'DAILY',
            'until' => '2024-05-01'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(0, $occurrences);
    }

    public function testUntilEqualToStart() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'until' => '2024-01-01'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(1, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
    }

    public function testCountAndUntilBothSet() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 3,
            'until' => '2024-12-31'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // Should stop at count limit
        $this->assertCount(3, $occurrences);
    }

    // ============================================================================
    // Edge Cases and Special Scenarios
    // ============================================================================

    public function testInvalidFrequency() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'INVALID'
        ];
        $rule = new SimpleRRule($rrule);
        $this->expectException(Exception::class);
        $rule->getOccurrences();
    }

    public function testInvalidBySetpos() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['MO'],
            'bysetpos' => -5,
            'count' => 1
        ];
        $rule = new SimpleRRule($rrule);
        $this->expectException(Exception::class);
        $rule->getOccurrences();
    }

    public function testEmptyByWeekday() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'byweekday' => [],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // Should generate all days
        $this->assertCount(3, $occurrences);
    }

    public function testEmptyByMonth() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [],
            'bymonthday' => [],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        // Should use start month/day
        $this->assertCount(3, $occurrences);
    }

    public function testStartAtEndOfMonth() {
        $rrule = [
            'dtstart' => '2024-01-31',
            'freq' => 'MONTHLY',
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        $this->assertEquals('2024-01-31', $occurrences[0]->toDateString());
    }

    public function testMultipleByWeekdaysInDaily() {
        $rrule = [
            'dtstart' => '2024-01-01', // Monday
            'freq' => 'DAILY',
            'byweekday' => ['MO', 'TU', 'WE', 'TH', 'FR'],
            'count' => 10
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(10, $occurrences);
        // All should be weekdays
        foreach ($occurrences as $occurrence) {
            $dayOfWeek = $occurrence->dayOfWeek;
            if ($dayOfWeek === 0) $dayOfWeek = 7;
            $this->assertLessThanOrEqual(5, $dayOfWeek);
        }
    }

    public function testNormalizeByWeekdayLowercase() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'byweekday' => ['mo', 'we', 'fr'],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
    }

    public function testNormalizeByWeekdayMixed() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'byweekday' => ['Mo', 'WE', 'fR'],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
    }

    // ============================================================================
    // FilterDates Tests
    // ============================================================================

    public function testFilterDatesEmpty() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 3,
            'exdate' => []
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
    }

    public function testFilterDatesAllExcluded() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 3,
            'exdate' => ['2024-01-01', '2024-01-02', '2024-01-03']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(0, $occurrences);
    }

    // ============================================================================
    // ToText Tests
    // ============================================================================

    public function testToTextSimple() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 5
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Freq: DAILY', $text);
        $this->assertStringContainsString('Interval: 1', $text);
        $this->assertStringContainsString('Count: 5', $text);
    }

    public function testToTextWithByWeekday() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 10
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Freq: WEEKLY', $text);
        $this->assertStringContainsString('ByWeekday:', $text);
    }

    public function testToTextWithBySetpos() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['MO'],
            'bysetpos' => -1,
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Freq: MONTHLY', $text);
        $this->assertStringContainsString('BySetpos: -1', $text);
    }

    public function testToTextWithByMonth() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [1, 6, 12],
            'bymonthday' => [1],
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Freq: YEARLY', $text);
        $this->assertStringContainsString('ByMonth:', $text);
        $this->assertStringContainsString('ByMonthDay:', $text);
    }

    public function testToTextWithByWeekNo() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'byweekno' => [1, 2, 3],
            'count' => 5
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Freq: WEEKLY', $text);
        $this->assertStringContainsString('ByWeekNo:', $text);
    }

    public function testToTextWithExDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 5,
            'exdate' => ['2024-01-03', '2024-01-04']
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('ExDate:', $text);
    }

    public function testToTextWithInterval() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'interval' => 2,
            'count' => 5
        ];
        $rule = new SimpleRRule($rrule);
        $text = $rule->toText();
        
        $this->assertStringContainsString('Interval: 2', $text);
    }

    // ============================================================================
    // Complex Scenarios
    // ============================================================================

    public function testComplexDailyWithByWeekdayAndExDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 9,
            'exdate' => ['2024-01-03', '2024-01-10']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertLessThan(9, count($occurrences));
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-01-03', $dates);
        $this->assertNotContains('2024-01-10', $dates);
    }

    public function testComplexWeeklyWithMultipleByWeekdaysAndExDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 5,
            'exdate' => ['2024-01-03']
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $dates = array_map(fn($d) => $d->toDateString(), $occurrences);
        $this->assertNotContains('2024-01-03', $dates);
    }

    public function testComplexMonthlyLastFriday() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'byweekday' => ['FR'],
            'bysetpos' => -1,
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(5, $occurrence->dayOfWeek); // Friday
        }
    }

    public function testComplexYearlyMultipleDaysPerMonth() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'YEARLY',
            'bymonth' => [1, 7],
            'bymonthday' => [1, 15, 28],
            'count' => 6
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
    }

    public function testComplexScenarioWithAllRestrictions() {
        $rrule = [
            'dtstart' => '2024-01-01 08:30:00',
            'freq' => 'WEEKLY',
            'interval' => 1,
            'byweekday' => ['MO', 'WE', 'FR'],
            'byweekno' => [1, 2, 3],
            'count' => 3,
            'exdate' => []
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertGreaterThan(0, count($occurrences));
        foreach ($occurrences as $occurrence) {
            $this->assertEquals(8, $occurrence->hour);
            $this->assertEquals(30, $occurrence->minute);
        }
    }

    // ============================================================================
    // Edge Cases with Timezones
    // ============================================================================

    public function testWithTimezoneInfo() {
        $rrule = [
            'dtstart' => '2024-01-01 12:00:00',
            'freq' => 'DAILY',
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(2, $occurrences);
    }

    // ============================================================================
    // Frequency Case Insensitivity
    // ============================================================================

    public function testFrequencyLowercase() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'daily',
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
    }

    public function testFrequencyMixed() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WeEkLy',
            'count' => 2
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(2, $occurrences);
    }

    // ============================================================================
    // Default Values Tests
    // ============================================================================

    public function testDefaultInterval() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 3
            // interval not specified, should default to 1
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-01-02', $occurrences[1]->toDateString());
        $this->assertEquals('2024-01-03', $occurrences[2]->toDateString());
    }

    public function testDefaultFrequency() {
        $rrule = [
            'dtstart' => '2024-01-01',
            // freq not specified, should default to DAILY
            'count' => 3
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
    }

    public function testDefaultExDate() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 3
            // exdate not specified, should default to empty array
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
    }

    // ============================================================================
    // Large Range Tests
    // ============================================================================

    public function testDailyLargeCount() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'DAILY',
            'count' => 100
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(100, $occurrences);
        $this->assertEquals('2024-01-01', $occurrences[0]->toDateString());
        $this->assertEquals('2024-04-09', $occurrences[99]->toDateString());
    }

    public function testWeeklyLargeRange() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'WEEKLY',
            'until' => '2025-12-31'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertGreaterThan(50, count($occurrences));
    }

    public function testMonthlyLargeRange() {
        $rrule = [
            'dtstart' => '2024-01-01',
            'freq' => 'MONTHLY',
            'until' => '2030-12-31'
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertGreaterThan(80, count($occurrences));
    }

}
