<?php

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class DSTSimplerruleTest extends TestCase {

    public function __construct(?string $name = null)
    {        
        parent::__construct($name);
        include_once __DIR__ . '/../classes/simplerrule.php';
    }

    // ============================================================================
    // DST Transition Tests (Europe/Budapest)
    // ============================================================================

    /**
     * Test DAILY frequency across DST transition (Spring forward - March 29, 2026)
     * 2026-03-29 02:00 CET → 03:00 CEST (lose 1 hour)
     * 
     * Verifies that times remain constant across DST boundary
     */
    public function testDailyAcrossDSTSpringForward() {
        $rrule = [
            'dtstart' => '2026-03-27 18:00:00', // Before DST transition
            'freq' => 'DAILY',
            'count' => 5 // Will cross the DST boundary on March 29
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Day " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
        }
    }

    /**
     * Test DAILY frequency across DST transition (Fall back - October 25, 2026)
     * 2026-10-25 03:00 CEST → 02:00 CET (gain 1 hour)
     * 
     * Verifies that times remain constant when falling back
     */
    public function testDailyAcrossDSTFallBack() {
        $rrule = [
            'dtstart' => '2026-10-23 18:00:00', // Before DST transition
            'freq' => 'DAILY',
            'count' => 5 // Will cross the DST boundary on October 25
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Day " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
        }
    }

    /**
     * Test WEEKLY frequency across DST transition (Spring forward)
     * 
     * Verifies that weekly recurring events maintain their time across DST
     */
    public function testWeeklyAcrossDSTSpringForward() {
        $rrule = [
            'dtstart' => '2026-03-23 18:00:00', // Monday before DST
            'freq' => 'WEEKLY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 6 // Will include March 30 (first Monday after DST transition)
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        // Check that times are preserved across DST
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Week " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
        }
    }

    /**
     * Test WEEKLY frequency across DST transition (Fall back)
     * 
     * Verifies that weekly recurring events maintain their time when falling back
     */
    public function testWeeklyAcrossDSTFallBack() {
        $rrule = [
            'dtstart' => '2026-10-19 18:00:00', // Monday before DST
            'freq' => 'WEEKLY',
            'byweekday' => ['MO', 'WE', 'FR'],
            'count' => 6 // Will include October 26 (first Monday after DST transition)
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Week " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
        }
    }

    /**
     * Test MONTHLY frequency across DST transition (Spring forward)
     * 
     * Verifies that monthly recurring events maintain their time across DST
     */
    public function testMonthlyAcrossDSTSpringForward() {
        $rrule = [
            'dtstart' => '2026-01-15 18:00:00',
            'freq' => 'MONTHLY',
            'count' => 5 // Jan-May, crossing DST in March
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Month " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
            $this->assertEquals(15, $occurrence->day, "Should be 15th of month");
        }
    }

    /**
     * Test MONTHLY frequency across DST transition (Fall back)
     * 
     * Verifies that monthly recurring events maintain their time when falling back
     */
    public function testMonthlyAcrossDSTFallBack() {
        $rrule = [
            'dtstart' => '2026-08-15 18:00:00',
            'freq' => 'MONTHLY',
            'count' => 5 // Aug-Dec, crossing DST in October
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Month " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
            $this->assertEquals(15, $occurrence->day, "Should be 15th of month");
        }
    }

    /**
     * Test MONTHLY with BYDAY across DST transition
     * 
     * Verifies that monthly recurring events with weekday constraints maintain time
     */
    public function testMonthlyWithBydayAcrossDSTSpringForward() {
        $rrule = [
            'dtstart' => '2026-01-05 18:00:00', // First Monday of January
            'freq' => 'MONTHLY',
            'byweekday' => ['MO'],
            'bysetpos' => 1,
            'count' => 4 // Jan-Apr, crossing DST in March
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(4, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Month " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
            $this->assertEquals(1, $occurrence->dayOfWeek, "Should be Monday");
        }
    }

    /**
     * Test YEARLY frequency across DST transition (Spring forward)
     * 
     * Verifies that yearly recurring events maintain their time across DST
     */
    public function testYearlyAcrossDSTSpringForward() {
        $rrule = [
            'dtstart' => '2024-04-15 18:00:00',
            'freq' => 'YEARLY',
            'bymonth' => [4],
            'bymonthday' => [15],
            'count' => 3 // 2024, 2025, 2026 (2026 will be after DST for April)
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Year " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
            $this->assertEquals(4, $occurrence->month, "Should be April");
            $this->assertEquals(15, $occurrence->day, "Should be 15th");
        }
    }

    /**
     * Test YEARLY frequency across DST transition (Fall back)
     * 
     * Verifies that yearly recurring events maintain their time when falling back
     */
    public function testYearlyAcrossDSTFallBack() {
        $rrule = [
            'dtstart' => '2024-10-15 18:00:00',
            'freq' => 'YEARLY',
            'bymonth' => [10],
            'bymonthday' => [15],
            'count' => 3 // 2024, 2025, 2026 (all have DST transitions in October)
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(3, $occurrences);
        // Check that times are preserved
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Year " . ($i + 1) . " (" . $occurrence->toDateString() . ") hour should be 18, got " . $occurrence->hour);
            $this->assertEquals(10, $occurrence->month, "Should be October");
            $this->assertEquals(15, $occurrence->day, "Should be 15th");
        }
    }

    /**
     * Test with explicit timezone in dtstart
     * 
     * Verifies that times are preserved even with explicit timezone specification
     */
    public function testWithExplicitTimezoneInDtstart() {
        $rrule = [
            'dtstart' => '2026-03-27T18:00:00+01:00', // CET timezone
            'freq' => 'DAILY',
            'count' => 5 // Will cross DST on March 29
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Day " . ($i + 1) . " with TZ info: hour should be 18, got " . $occurrence->hour);
        }
    }

    /**
     * Comprehensive DST diagnostic test - DAILY across transition with boundary date
     * 
     * This test specifically ensures that the boundary date (the DST transition day itself)
     * is handled correctly
     */
    public function testDSTBoundaryDateDaily() {
        // March 29, 2026 is the DST transition day
        $rrule = [
            'dtstart' => '2026-03-25 18:00:00',
            'freq' => 'DAILY',
            'count' => 6 // 25, 26, 27, 28, 29(DST!), 30
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(6, $occurrences);
        
        // Verify the boundary date (March 29) has correct time
        $boundaryDate = $occurrences[4]; // 5th occurrence = March 29
        $this->assertEquals('2026-03-29', $boundaryDate->toDateString());
        $this->assertEquals(18, $boundaryDate->hour, 'DST boundary date should maintain 18:00');
        
        // Verify all dates maintain correct time
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Occurrence " . ($i + 1) . " (" . $occurrence->toDateString() . ") should be 18:00");
        }
    }

    /**
     * Test multiple DST transitions in same year (Spring and Fall)
     * 
     * Verifies that recurring events maintain time across both DST transitions
     */
    public function testMultipleDSTTransitionsInYear() {
        $rrule = [
            'dtstart' => '2026-01-15 18:00:00',
            'freq' => 'MONTHLY',
            'count' => 12 // Full year: crosses both spring (March 29) and fall (October 25) DST
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(12, $occurrences);
        
        // Verify all months maintain correct time
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(18, $occurrence->hour, 
                "Month " . ($i + 1) . " (" . $occurrence->format('Y-m-d') . ") should be 18:00, got {$occurrence->hour}:00");
        }
    }

    /**
     * Edge case: Very early morning time near DST transition
     * 
     * DST happens at 02:00, so 02:30 wouldn't exist on spring forward day
     * This test ensures times are still correctly set
     */
    public function testEarlyMorningTimeDuringDST() {
        $rrule = [
            'dtstart' => '2026-03-27 06:30:00', // 6:30 AM - early but safe
            'freq' => 'DAILY',
            'count' => 5 // Will cross DST on March 29
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(6, $occurrence->hour, 
                "Hour should be 6");
            $this->assertEquals(30, $occurrence->minute, 
                "Minute should be 30");
        }
    }

    /**
     * Edge case: Evening time near end of DST transition
     * 
     * Tests a time that's close to the DST transition point
     */
    public function testEveningTimeDuringDST() {
        $rrule = [
            'dtstart' => '2026-03-27 23:45:00', // 23:45 PM
            'freq' => 'DAILY',
            'count' => 5 // Will cross DST on March 29
        ];
        $rule = new SimpleRRule($rrule);
        $occurrences = $rule->getOccurrences();
        
        $this->assertCount(5, $occurrences);
        foreach ($occurrences as $i => $occurrence) {
            $this->assertEquals(23, $occurrence->hour, 
                "Hour should be 23");
            $this->assertEquals(45, $occurrence->minute, 
                "Minute should be 45");
        }
    }
}
