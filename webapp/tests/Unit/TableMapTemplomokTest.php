<?php

use PHPUnit\Framework\TestCase;

/**
 * Regressziós teszt egy valódi /api Table-crashre: a mapTemplomok() a
 * `denomination` oszlopnál TÖMB-indexeléssel (`$row['egyhazmegye']`) fért hozzá
 * egy stdClass sorhoz (a DB::table()->get() stdClass-t ad), így a végpont fatal
 * `Error: Cannot use object of type stdClass as array`-jel elszállt, valahányszor
 * egy kliens a denomination oszlopot kérte. Javítva object-hozzáférésre.
 *
 * A mapTemplomok() public; a konstruktor kihagyva (newInstanceWithoutConstructor).
 */
class TableMapTemplomokTest extends TestCase
{
    private function table(): \Api\Table
    {
        return (new \ReflectionClass(\Api\Table::class))->newInstanceWithoutConstructor();
    }

    public function testDenominationGreekCatholicDioceses(): void
    {
        foreach ([17, 18, 34] as $ehm) {
            $t = $this->table();
            $t->table = [(object) ['egyhazmegye' => $ehm]];
            $t->columns = ['denomination'];
            $t->mapTemplomok();
            $this->assertSame('greek_catholic', $t->table[0]['denomination'], "egyhazmegye=$ehm");
        }
    }

    public function testDenominationRomanCatholicDefault(): void
    {
        $t = $this->table();
        $t->table = [(object) ['egyhazmegye' => 5]];
        $t->columns = ['denomination'];
        $t->mapTemplomok();
        $this->assertSame('roman_catholic', $t->table[0]['denomination']);
    }

    public function testStdClassRowDoesNotCrash(): void
    {
        // A bug előtt ez fatal Error-t dobott; most tiszta lefutás.
        $t = $this->table();
        $t->table = [(object) ['egyhazmegye' => 34]];
        $t->columns = ['denomination'];
        $t->mapTemplomok();
        $this->assertIsArray($t->table[0]);
    }
}
