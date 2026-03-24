<?php

use PHPUnit\Framework\TestCase;

class SimpleFunctionsTest extends TestCase {

    public function testSanitizeTrimsAndKeepsAllowedTags() {
        $input = "  <b>Árvíztűrő</b>\n<script>alert('x')</script><a href='/ok'>link</a>  ";

        $result = sanitize($input);

        $this->assertEquals("<b>Árvíztűrő</b><br/>alert('x')<a href='/ok'>link</a>", $result);
    }

    public function testSanitizeWorksRecursivelyOnArrays() {
        $input = array(
            'title' => "  <strong>Cím</strong>  ",
            'body' => "sor1\nsor2"
        );

        $result = sanitize($input);

        $this->assertEquals('<strong>Cím</strong>', $result['title']);
        $this->assertEquals('sor1<br/>sor2', $result['body']);
    }

    public function testGetWeekInMonthForFirstOccurrence() {
        $this->assertEquals(1, getWeekInMonth('2026-03-01'));
    }

    public function testGetWeekInMonthCountsWeeksBackwardsWithinMonth() {
        $this->assertEquals(3, getWeekInMonth('2026-03-15'));
    }

    public function testGetWeekInMonthCountsWeeksForwardWithinMonthWhenOrderIsMinus() {
        $this->assertEquals(-3, getWeekInMonth('2026-03-15', '-'));
    }

    public function testGetWeekInMonthReturnsZeroForUnknownOrder() {
        $this->assertEquals(0, getWeekInMonth('2026-03-15', 'unknown'));
    }

    public function testTwigHungarianDateFormatForTodayWithoutTime() {
        $result = twig_hungarian_date_format(date('Y-m-d H:i:s'), '');

        $this->assertEquals('ma', $result);
    }

    public function testTwigHungarianDateFormatForTodayWithDefaultTime() {
        $timestamp = time();

        $result = twig_hungarian_date_format($timestamp);

        $this->assertStringStartsWith('ma ', $result);
        $this->assertMatchesRegularExpression('/^ma \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForYesterday() {
        $timestamp = strtotime('-1 day');

        $result = twig_hungarian_date_format($timestamp, '');

        $this->assertStringStartsWith('tegnap, ', $result);
    }

    public function testTwigHungarianDateFormatForYesterdayWithTime() {
        $timestamp = strtotime('-1 day');

        $result = twig_hungarian_date_format($timestamp, 'H:i');

        $this->assertStringStartsWith('tegnap, ', $result);
        $this->assertMatchesRegularExpression('/ \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForTomorrow() {
        $timestamp = strtotime('+1 day');

        $result = twig_hungarian_date_format($timestamp, '');

        $this->assertStringStartsWith('holnap, ', $result);
    }

    public function testTwigHungarianDateFormatForTomorrowWithTime() {
        $timestamp = strtotime('+1 day');

        $result = twig_hungarian_date_format($timestamp, 'H:i');

        $this->assertStringStartsWith('holnap, ', $result);
        $this->assertMatchesRegularExpression('/ \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForDateInCurrentWeekButNotTodayYesterdayTomorrow() {
        $todayMidnight = strtotime(date('Y-m-d'));
        $candidate = strtotime('-3 days', $todayMidnight);
        if (date('Y-m-d', $candidate) === date('Y-m-d', strtotime('-1 day', $todayMidnight))) {
            $candidate = strtotime('-4 days', $todayMidnight);
        }

        global $_honapok;
        $monthNumber = (int)date('n', $candidate);
        if (!isset($_honapok[$monthNumber][0]) || $_honapok[$monthNumber][0] === '') {
            $_honapok[$monthNumber] = ['mon', 'month'];
        }

        $result = twig_hungarian_date_format($candidate, '');

        $this->assertMatchesRegularExpression('/\(.*\d+\.\)$/', $result);
    }

    public function testTwigHungarianDateFormatForDateInCurrentWeekIncludesTimeWhenRequested() {
        $todayMidnight = strtotime(date('Y-m-d'));
        $candidate = strtotime('-3 days', $todayMidnight);
        if (date('Y-m-d', $candidate) === date('Y-m-d', strtotime('-1 day', $todayMidnight))) {
            $candidate = strtotime('-4 days', $todayMidnight);
        }

        global $_honapok;
        $monthNumber = (int)date('n', $candidate);
        if (!isset($_honapok[$monthNumber][0]) || $_honapok[$monthNumber][0] === '') {
            $_honapok[$monthNumber] = ['mon', 'month'];
        }

        $result = twig_hungarian_date_format($candidate, 'H:i');

        $this->assertMatchesRegularExpression('/\(.*\d+\.\) \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForOlderDateUsesMonthDayAndWeekday() {
        $timestamp = strtotime('-10 days');

        $result = twig_hungarian_date_format($timestamp, '');

        $this->assertStringContainsString(', ', $result);
        $this->assertStringContainsString('.', $result);
    }

    public function testTwigHungarianDateFormatForOlderDateIncludesTimeWhenRequested() {
        $timestamp = strtotime('-10 days');

        $result = twig_hungarian_date_format($timestamp, 'H:i');

        $this->assertMatchesRegularExpression('/ \d{2}:\d{2}$/', $result);
    }
}
