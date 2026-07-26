<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: Az OSM changeset-XML összeállításának lefedése (prepareNewChangeset).
 * Tiszta SimpleXML-transzformáció: default created_by='miserend.hu' + default comment,
 * ha hiányoznak; minden tag k/v attribútumként. A konstruktor kihagyva (config-igény).
 */
class OpenstreetmapChangesetTest extends TestCase
{
    private function api(): \ExternalApi\OpenstreetmapApi
    {
        return (new \ReflectionClass(\ExternalApi\OpenstreetmapApi::class))->newInstanceWithoutConstructor();
    }

    public function testInjectsDefaultCreatedByAndComment(): void
    {
        $xml = $this->api()->prepareNewChangeset([])->asXML();
        $this->assertStringContainsString('created_by', $xml);
        $this->assertStringContainsString('miserend.hu', $xml);
        $this->assertStringContainsString('experiences', $xml); // a default comment része
    }

    public function testKeepsCustomComment(): void
    {
        $xml = $this->api()->prepareNewChangeset(['comment' => 'egyedi-uzenet'])->asXML();
        $this->assertStringContainsString('egyedi-uzenet', $xml);
    }

    public function testSerializesCustomTagsAsAttributes(): void
    {
        $xml = $this->api()->prepareNewChangeset(['created_by' => 'x', 'foo' => 'bar'])->asXML();
        $this->assertStringContainsString('k="foo"', $xml);
        $this->assertStringContainsString('v="bar"', $xml);
    }
}
