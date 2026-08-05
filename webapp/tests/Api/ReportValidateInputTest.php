<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A Report::validateInput cross-field validációjának lefedése.
 * (1) pid===2 -> 'text' kötelező; (2) version>3 -> parse-olható 'dbdate' kötelező;
 * (3) 'timestamp' megadva -> parse-olhatónak kell lennie. A token-ág (DB) kihagyva.
 */
class ReportValidateInputTest extends TestCase
{
    private function report(array $input, int $version): \Api\Report
    {
        $r = (new \ReflectionClass(\Api\Report::class))->newInstanceWithoutConstructor();
        $r->input = $input;
        $r->version = $version;
        return $r;
    }

    public function testPid2RequiresText(): void
    {
        $this->expectException(\Exception::class);
        $this->report(['tid' => 7, 'pid' => 2], 4)->validateInput();
    }

    public function testVersionAbove3RequiresParsableDbdate(): void
    {
        $this->expectException(\Exception::class);
        $this->report(['tid' => 7, 'pid' => 0], 4)->validateInput(); // hiányzó dbdate
    }

    public function testUnparsableDbdateThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->report(['tid' => 7, 'pid' => 0, 'dbdate' => 'nonsense'], 4)->validateInput();
    }

    public function testUnparsableTimestampThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->report(['tid' => 7, 'pid' => 0, 'timestamp' => 'notadate'], 3)->validateInput();
    }

    public function testValidVersion3PidZeroPasses(): void
    {
        $this->report(['tid' => 7, 'pid' => 0], 3)->validateInput();
        $this->assertTrue(true);
    }

    public function testFullValidVersion4Passes(): void
    {
        $this->report(['tid' => 7, 'pid' => 2, 'text' => 'x', 'dbdate' => '2024-01-16'], 4)->validateInput();
        $this->assertTrue(true);
    }
}
