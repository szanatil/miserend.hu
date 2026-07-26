<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A Church remarks-státusz összegzésének lefedése (icon + státusz-szöveg).
 *
 * A templom észrevételeinek `allapot`-jaiból prioritásos redukció: bármelyik 'u'
 * (új) -> NEW, különben bármelyik 'f' (feldolgozás alatt) -> PROCESSING, különben
 * nem-üres -> ALLDONE, különben NO. Kulcs élhelyzet: az üres-sztring allapot 'j'-nek
 * számít (feldolgozott), így egyetlen '' észrevétel is ALLDONE, nem NO.
 *
 * DB nélkül, setRelation-nel előtöltött remarks-kollekcióval. Integration-suite a
 * Church modell + collect() betöltéséhez.
 */
class ChurchRemarksIconTest extends TestCase
{
    /** @param string[] $allapotok */
    private function churchWithRemarks(array $allapotok): \Eloquent\Church
    {
        $remarks = collect(array_map(
            fn($a) => (object) ['allapot' => $a],
            $allapotok
        ));
        $church = new \Eloquent\Church();
        $church->setRelation('remarks', $remarks);
        return $church;
    }

    public function testNewRemarkTakesPriority(): void
    {
        $c = $this->churchWithRemarks(['j', 'u', 'f']);
        $this->assertSame('ICONS_REMARKS_NEW', $c->remarksicon);
        $this->assertSame('Új észrevétel érkezett.', $c->remarksStatusText);
    }

    public function testProcessingWhenNoNew(): void
    {
        $c = $this->churchWithRemarks(['j', 'f']);
        $this->assertSame('ICONS_REMARKS_PROCESSING', $c->remarksicon);
        $this->assertSame('Van még feldolgozás alatt álló észrevétel.', $c->remarksStatusText);
    }

    public function testAllDoneWhenOnlyProcessed(): void
    {
        $c = $this->churchWithRemarks(['j', 'j']);
        $this->assertSame('ICONS_REMARKS_ALLDONE', $c->remarksicon);
        $this->assertSame('Minden észrevétel feldolgozva.', $c->remarksStatusText);
    }

    public function testNoRemarksWhenEmpty(): void
    {
        $c = $this->churchWithRemarks([]);
        $this->assertSame('ICONS_REMARKS_NO', $c->remarksicon);
        $this->assertSame('Nem érkezett még észrevétel.', $c->remarksStatusText);
    }

    /**
     * Kulcs élhelyzet: egyetlen üres-sztring allapot 'j'-nek normalizálódik,
     * tehát ALLDONE — NEM a "nincs észrevétel" (NO).
     */
    public function testEmptyStringAllapotIsTreatedAsProcessed(): void
    {
        $c = $this->churchWithRemarks(['']);
        $this->assertSame('ICONS_REMARKS_ALLDONE', $c->remarksicon);
        $this->assertSame('Minden észrevétel feldolgozva.', $c->remarksStatusText);
    }
}
