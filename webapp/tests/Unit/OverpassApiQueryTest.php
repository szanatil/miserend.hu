<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: Unit-tesztek az OverpassApi query-string építőire.
 *
 * A build*Query() metódusok tiszta, determinisztikus Overpass QL összeállítás
 * (nincs hálózat/DB/config), és ezek hajtják a boundary-lekérdezést meg a
 * templom-a-határon-belül keresést. Egy elrontott query csendben rossz OSM-adatot
 * hozna. Az elvárt stringeket standalone harness-szel rögzítettem.
 */
class OverpassApiQueryTest extends TestCase
{
    private function api(): \ExternalApi\OverpassApi
    {
        return new \ExternalApi\OverpassApi();
    }

    public function testBuildOneEntityQuery(): void
    {
        $o = $this->api();
        $o->buildOneEntityQuery('node', 456);
        $this->assertSame('(node(id:456););out body qt center;', $o->query);
    }

    public function testBuildSimpleQueryDefault(): void
    {
        $o = $this->api();
        $o->buildSimpleQuery();
        $this->assertSame('(node;way;relation;);out body qt center;', $o->query);
    }

    public function testBuildSimpleQueryWithCustomFilter(): void
    {
        $o = $this->api();
        $o->buildSimpleQuery('["amenity"="place_of_worship"]');
        $this->assertSame(
            '(node["amenity"="place_of_worship"];way["amenity"="place_of_worship"];relation["amenity"="place_of_worship"];);out body qt center;',
            $o->query
        );
    }

    public function testBuildEnclosingBoundariesQuery(): void
    {
        $o = $this->api();
        $o->buildEnclosingBoundariesQuery(47.5, 19.0);

        // is_in a koordinátával, boundary-szűrő, és mindhárom elemtípus.
        $this->assertStringStartsWith('is_in(47.5,19)->.a;', $o->query);
        $this->assertStringContainsString("['type'='boundary' ]['disused:boundary'!~'.*']", $o->query);
        $this->assertStringContainsString('node[', $o->query);
        $this->assertStringContainsString('way[', $o->query);
        $this->assertStringContainsString('relation[', $o->query);
        $this->assertStringContainsString('(pivot.a);out bb center tags;', $o->query);
    }

    public function testBuildChurchesWithinBoundaryQuery(): void
    {
        $o = $this->api();
        $o->buildChurchesWithinBoundaryQuery('relation', 22234);
        $this->assertSame(
            'relation(22234)->.rel;.rel map_to_area->.searchArea;( nwr["url:miserend"](area.searchArea); );out body;',
            $o->query
        );
    }

    /**
     * buildQuery() a query-t `?data=`-vel + urlencode-dal + a timeout-tal csomagolja.
     */
    public function testBuildQueryWrapsWithTimeoutAndUrlencode(): void
    {
        $o = $this->api();
        $o->buildOneEntityQuery('node', 456); // ez hívja a buildQuery()-t
        $expectedRaw = '?data=' . urlencode('[out:json][timeout:30];(node(id:456););out body qt center;');
        $this->assertSame($expectedRaw, $o->rawQuery);
    }
}
