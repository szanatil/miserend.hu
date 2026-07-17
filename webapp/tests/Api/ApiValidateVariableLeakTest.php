<?php

use PHPUnit\Framework\TestCase;
use Api\Api;

/**
 * Regressziós teszt egy #182-szerű info-leakre: a validateVariable() a `$input===null`
 * ágban `printr($this->input)`-t hívott, ami a TELJES request-payloadot (a tokent is!)
 * `<pre>...</pre>`-ként a HTTP-válaszba echózta — egy null lista-elem (pl. `add:[1,null,2]`,
 * a favorites add/remove úton) kiváltotta ezt, elrontva a JSON-t és belső adatot szivárogtatva.
 *
 * A javítás törölte a blokkot; a valódi null a validateInteger-en akad fenn, tiszta hibával.
 */
class ApiValidateVariableLeakTest extends TestCase
{
    public function testNullListElementThrowsWithoutLeakingPayload(): void
    {
        $api = new Api();
        $api->input = ['add' => [1, null, 2], 'token' => 'secret-should-not-leak'];

        ob_start();
        $threw = false;
        try {
            $api->validateVariable('list', 'add', ['integer' => []], [1, null, 2]);
        } catch (\Throwable $e) {
            $threw = true;
            $this->assertStringContainsString('integer', $e->getMessage());
        }
        $output = ob_get_clean();

        $this->assertTrue($threw, 'null lista-elemre dobnia kell');
        $this->assertSame('', trim($output), 'a request-payload nem szivároghat a válaszba (leak-regresszió)');
        $this->assertStringNotContainsString('secret-should-not-leak', $output);
    }
}
