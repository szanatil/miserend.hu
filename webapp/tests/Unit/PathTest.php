<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A Path::convertAliases URL-alias átírásának lefedése.
 *
 * A magyar URL-aliasok (templom/{id} -> church/{id}, impresszum -> staticpage/...,
 * terkep -> map, '' -> home, stb.) tiszta regex-átírás, eddig teszt nélkül. Egy
 * elrontott minta a routingot törné (rossz oldal / 404) — ezt csak teszt fogja meg.
 *
 * A ->url az alias-átírás tiszta eredménye (fájlrendszer-független); a ->arguments
 * a prepare() fájl-feloldásától függ, ezért csak a stabil eseteket ellenőrizzük.
 */
class PathTest extends TestCase
{
    /**
     * @dataProvider aliasProvider
     */
    public function testConvertAliasesRewritesUrl(string $input, string $expectedUrl): void
    {
        $p = new Path($input);
        $this->assertSame($expectedUrl, $p->url);
    }

    public static function aliasProvider(): array
    {
        return [
            'templom/{id} -> church/{id}'       => ['templom/123', 'church/123'],
            'templom/list -> church/catalogue'  => ['templom/list', 'church/catalogue'],
            'impresszum -> staticpage'          => ['impresszum', 'staticpage/impressum'],
            'gdpr -> staticpage'                => ['gdpr', 'staticpage/gdpr'],
            'hazirend -> staticpage'            => ['hazirend', 'staticpage/termsandconditions'],
            'terkep -> map'                     => ['terkep', 'map'],
            'üres -> home'                       => ['', 'home'],
            'egyhazmegye/list -> catalogue'     => ['egyhazmegye/list', 'diocesecatalogue'],
            'eszrevetelek -> remark/list'       => ['templom/456/eszrevetelek', 'remark/list/456'],
            'ujeszrevetel -> remark/addform'    => ['templom/789/ujeszrevetel', 'remark/addform/789'],
            'feedback -> email/remarkfeedback'  => ['remark/321/feedback', 'email/remarkfeedback/321'],
        ];
    }

    public function testArgumentsExtractedForChurch(): void
    {
        // A church/{id} feloldásakor az id az argumentumok közé kerül.
        $p = new Path('templom/123');
        $this->assertContains('123', $p->arguments);
    }
}
