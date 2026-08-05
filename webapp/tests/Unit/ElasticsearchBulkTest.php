<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: Az ES _bulk NDJSON-payload összeállításának lefedése (buildBulkNdjson).
 * Kiemelt tiszta logika a putBulk-ból; tömb-elemek json_encode-olva, string-elemek
 * változatlanul, \n-nel + záró \n.
 */
class ElasticsearchBulkTest extends TestCase
{
    public function testBuildsNdjsonFromMixedItems(): void
    {
        $out = \ExternalApi\ElasticsearchApi::buildBulkNdjson([['a' => 1], 'raw-passthrough', ['b' => 2]]);
        $this->assertSame("{\"a\":1}\nraw-passthrough\n{\"b\":2}\n", $out);
    }

    public function testNonArrayInputPassesThroughUnchanged(): void
    {
        $this->assertSame('already-ndjson-string', \ExternalApi\ElasticsearchApi::buildBulkNdjson('already-ndjson-string'));
    }

    public function testEmptyArrayYieldsSingleNewline(): void
    {
        $this->assertSame("\n", \ExternalApi\ElasticsearchApi::buildBulkNdjson([]));
    }
}
