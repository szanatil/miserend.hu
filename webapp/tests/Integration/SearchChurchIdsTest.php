<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Search::churchIds()
 *
 * Verifies the Elasticsearch query structure produced by the method.
 * No DB or ES connection required – we only inspect the query array.
 */
class SearchChurchIdsTest extends TestCase {

    // ── churches index ───────────────────────────────────────────────────────

    public function testSingleIdUsesSingleTermOnChurchIndex(): void {
        $search = new Search('churches');
        $search->churchIds([42]);

        $must = $search->query['bool']['must'];
        $this->assertCount(1, $must, 'One must clause expected');
        $this->assertArrayHasKey('term', $must[0], '"term" (singular) expected for one ID');
        $this->assertSame(42, $must[0]['term']['id'], 'ID should be 42');
    }

    public function testMultipleIdsUsesTermsOnChurchIndex(): void {
        $search = new Search('churches');
        $search->churchIds([1, 2, 3]);

        $must = $search->query['bool']['must'];
        $this->assertCount(1, $must, 'One must clause expected');
        $this->assertArrayHasKey('terms', $must[0], '"terms" (plural) expected for multiple IDs');
        $this->assertSame([1, 2, 3], $must[0]['terms']['id']);
    }

    public function testEmptyIdsAddsNoFilterOnChurchIndex(): void {
        $search = new Search('churches');
        $search->churchIds([]);

        $must = $search->query['bool']['must'];
        $this->assertEmpty($must, 'Empty array should not add any must clause');
    }

    public function testNonIntegerIdsAreCastAndFiltered(): void {
        $search = new Search('churches');
        $search->churchIds(['7', '0', 'abc', '12']);

        $must = $search->query['bool']['must'];
        // '0' and 'abc' both cast to 0, which filter() removes; '7' -> 7, '12' -> 12
        $this->assertCount(1, $must);
        $this->assertArrayHasKey('terms', $must[0]);
        $this->assertSame([7, 12], array_values($must[0]['terms']['id']));
    }

    // ── mass index ───────────────────────────────────────────────────────────

    public function testSingleIdUsesSingleTermOnMassIndex(): void {
        $search = new Search('masses');
        $search->churchIds([99]);

        $must = $search->query['bool']['must'];
        $this->assertCount(1, $must);
        $this->assertArrayHasKey('term', $must[0]);
        $this->assertSame(99, $must[0]['term']['church.id'],
            'Mass index should use "church.id" as the field name');
    }

    public function testMultipleIdsUsesTermsOnMassIndex(): void {
        $search = new Search('masses');
        $search->churchIds([5, 10, 15]);

        $must = $search->query['bool']['must'];
        $this->assertCount(1, $must);
        $this->assertArrayHasKey('terms', $must[0]);
        $this->assertSame([5, 10, 15], $must[0]['terms']['church.id']);
    }

    // ── filters label ────────────────────────────────────────────────────────

    public function testFiltersLabelIsSingularForOneId(): void {
        $search = new Search('churches');
        $search->churchIds([42]);

        $this->assertContains('Adott templom', $search->filters,
            'Singular label expected for one church ID');
    }

    public function testFiltersLabelIsPluralForMultipleIds(): void {
        $search = new Search('churches');
        $search->churchIds([1, 2, 3]);

        $found = false;
        foreach ($search->filters as $f) {
            if (strpos($f, '3') !== false && strpos($f, 'kiválasztott') !== false) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Plural label expected for multiple church IDs');
    }

    public function testEmptyIdsAddsNoFilterLabel(): void {
        $search = new Search('churches');
        $search->churchIds([]);

        $this->assertEmpty($search->filters, 'No filter label should be added for empty IDs');
    }
}
