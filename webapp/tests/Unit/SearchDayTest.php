<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A Search::day dátum-validációjának (throw-ágak) lefedése. A whenMass/when
 * paramétert oldja fel (weekday/today/tomorrow -> YYYY-MM-DD), majd regex-validál.
 * Az érvénytelen bemenetek a timeRange ELŐTT dobnak, ezért DB nélkül tesztelhetők.
 */
class SearchDayTest extends TestCase
{
    private function callDay(string $whenDate): void
    {
        $s = (new \ReflectionClass(\Search::class))->newInstanceWithoutConstructor();
        $s->day($whenDate);
    }

    public function testRejectsInvalidMonth(): void
    {
        $this->expectException(\Exception::class);
        $this->callDay('2026-13-01');
    }

    public function testRejectsZeroMonth(): void
    {
        $this->expectException(\Exception::class);
        $this->callDay('2026-00-10');
    }

    public function testRejectsGarbage(): void
    {
        $this->expectException(\Exception::class);
        $this->callDay('notaday');
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(\Exception::class);
        $this->callDay('');
    }
}
