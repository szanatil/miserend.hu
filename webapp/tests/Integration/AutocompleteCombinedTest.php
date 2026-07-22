<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the /ajax/AutocompleteCombined endpoint.
 *
 * Makes real HTTP calls against a running local server.
 * Requires PANTHER_EXTERNAL_BASE_URI (or defaults to http://127.0.0.1:8000).
 */
class AutocompleteCombinedTest extends TestCase {

    private string $baseUrl;

    protected function setUp(): void {
        $this->baseUrl = rtrim(getenv('PANTHER_EXTERNAL_BASE_URI') ?: 'http://127.0.0.1:8000', '/');
    }

    private function fetchJson(string $query): array {
        $url  = $this->baseUrl . '/ajax/AutocompleteCombined?' . $query;
        $json = @file_get_contents($url);
        $this->assertNotFalse($json, 'HTTP request failed for: ' . $url);
        $data = json_decode($json, true);
        $this->assertIsArray($data, 'Response should be valid JSON');
        return $data;
    }

    // ── Basic structure ──────────────────────────────────────────────────────

    public function testResponseHasResultsKey(): void {
        $data = $this->fetchJson('text=Budapest');
        $this->assertArrayHasKey('results', $data);
    }

    public function testResultsIsAnArray(): void {
        $data = $this->fetchJson('text=Budapest');
        $this->assertIsArray($data['results']);
    }

    public function testShortTextReturnsEmptyResults(): void {
        $data = $this->fetchJson('text=Bu');
        $this->assertEmpty($data['results'], 'Less than 3 chars should return empty results');
    }

    public function testEmptyTextReturnsEmptyResults(): void {
        $data = $this->fetchJson('text=');
        $this->assertEmpty($data['results']);
    }

    // ── Result item fields ───────────────────────────────────────────────────

    public function testEveryResultHasKindIdNameScore(): void {
        $data = $this->fetchJson('text=Budapest');
        foreach ($data['results'] as $result) {
            $this->assertArrayHasKey('kind',  $result, 'kind field missing');
            $this->assertArrayHasKey('id',    $result, 'id field missing');
            $this->assertArrayHasKey('name',  $result, 'name field missing');
            $this->assertArrayHasKey('score', $result, 'score field missing');
        }
    }

    public function testChurchResultsHaveCityField(): void {
        $data = $this->fetchJson('text=Budapest');
        foreach ($data['results'] as $result) {
            if ($result['kind'] === 'church') {
                $this->assertArrayHasKey('city', $result, 'church result must have city');
            }
        }
    }

    public function testBoundaryResultsHaveTypeAndColor(): void {
        $data = $this->fetchJson('text=Budapest');
        foreach ($data['results'] as $result) {
            if ($result['kind'] === 'boundary') {
                $this->assertArrayHasKey('type',  $result, 'boundary result must have type');
                $this->assertArrayHasKey('color', $result, 'boundary result must have color');
            }
        }
    }

    public function testKindIsOnlyBoundaryOrChurch(): void {
        $data = $this->fetchJson('text=Budapest');
        foreach ($data['results'] as $result) {
            $this->assertContains($result['kind'], ['boundary', 'church'],
                'kind must be "boundary" or "church"');
        }
    }

    // ── Ordering ─────────────────────────────────────────────────────────────

    public function testResultsAreOrderedByScoreDesc(): void {
        $data = $this->fetchJson('text=Budapest');
        $scores = array_column($data['results'], 'score');
        $sorted = $scores;
        rsort($sorted);
        $this->assertSame($sorted, $scores, 'Results must be ordered by score DESC');
    }

    // ── excluded_ids ─────────────────────────────────────────────────────────

    public function testExcludedBoundaryIdsAreNotReturned(): void {
        // First call without exclusion to get boundary IDs
        $data1 = $this->fetchJson('text=Budapest');
        $boundaryIds = [];
        foreach ($data1['results'] as $r) {
            if ($r['kind'] === 'boundary') {
                $boundaryIds[] = $r['id'];
            }
        }

        if (empty($boundaryIds)) {
            $this->markTestSkipped('No boundary results for "Budapest" – cannot test exclusion');
        }

        // Second call with those IDs excluded
        $excluded = implode(',', $boundaryIds);
        $data2 = $this->fetchJson('text=Budapest&excluded_ids=' . urlencode($excluded));

        foreach ($data2['results'] as $r) {
            if ($r['kind'] === 'boundary') {
                $this->assertNotContains($r['id'], $boundaryIds,
                    'Excluded boundary ID ' . $r['id'] . ' should not appear');
            }
        }
    }

    public function testExcludedChurchIdsAreNotReturned(): void {
        $data1 = $this->fetchJson('text=Budapest');
        $churchIds = [];
        foreach ($data1['results'] as $r) {
            if ($r['kind'] === 'church') {
                $churchIds[] = $r['id'];
            }
        }

        if (empty($churchIds)) {
            $this->markTestSkipped('No church results for "Budapest" – cannot test exclusion');
        }

        $excluded = implode(',', $churchIds);
        $data2 = $this->fetchJson('text=Budapest&excluded_church_ids=' . urlencode($excluded));

        foreach ($data2['results'] as $r) {
            if ($r['kind'] === 'church') {
                $this->assertNotContains($r['id'], $churchIds,
                    'Excluded church ID ' . $r['id'] . ' should not appear');
            }
        }
    }

    // ── Cap at 12 ────────────────────────────────────────────────────────────

    public function testResultsAreCappedAtTwelve(): void {
        $data = $this->fetchJson('text=a');
        $this->assertLessThanOrEqual(12, count($data['results']),
            'Combined results should be capped at 12');
    }
}
