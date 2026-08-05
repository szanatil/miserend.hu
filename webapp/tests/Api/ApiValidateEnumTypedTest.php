<?php

use PHPUnit\Framework\TestCase;
use Api\Api;

/**
 * #374: A validateEnum tömb-alapú (típusos) szabály-ága eddig hiányzó 3. ($input)
 * argumentummal hívta a validateInteger/validateFloat-ot -> ArgumentCountError (\Error,
 * amit a catch(\Exception) nem fog el), így minden típusos enum elszállt volna. Javítva.
 */
class ApiValidateEnumTypedTest extends TestCase
{
    public function testIntegerRuleAcceptsMatchingValue(): void
    {
        $api = new Api();
        // A javítás előtt ez ArgumentCountError-t dobott; most tisztán átmegy.
        $api->validateEnum('pid', [['integer' => ['minimum' => 1]]], 5);
        $this->assertTrue(true);
    }

    public function testIntegerRuleRejectsNonMatchingValue(): void
    {
        $api = new Api();
        $this->expectException(\Exception::class);
        $api->validateEnum('pid', [['integer' => []]], 'not-an-integer');
    }
}
