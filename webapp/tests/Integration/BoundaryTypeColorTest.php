<?php

use PHPUnit\Framework\TestCase;

/**
 * #374: A Boundary típus- és szín-leképezésének lefedése (type/color accessor).
 *
 * A boundary/denomination/admin_level hármasból egy definíciós tábla adja a magyar
 * megnevezést és a színt (több ág: felekezet-tudatos religious_administration,
 * administrative, postal_code, és a szürke fallback). A térkép-legenda és a
 * boundary-megjelenítés erre épül; egy elrontott ág rossz címkét/színt adna.
 *
 * DB nélkül példányosítjuk a modellt (közvetlen attribútum-beállítás), csak az
 * accessor tiszta logikáját teszteljük. Integration-suite a modell betöltéséhez.
 * Az ismeretlen-boundary ág (\Translator::translate) szándékosan kimarad.
 */
class BoundaryTypeColorTest extends TestCase
{
    private function boundary(string $boundary, ?int $adminLevel = null, ?string $denomination = null): \Eloquent\Boundary
    {
        $b = new \Eloquent\Boundary();
        $b->boundary = $boundary;
        $b->admin_level = $adminLevel;
        $b->denomination = $denomination;
        return $b;
    }

    public function testRomanCatholicParishLevel8(): void
    {
        $b = $this->boundary('religious_administration', 8, 'roman_catholic');
        $this->assertSame('plébánia', $b->type);
        $this->assertSame('#6A5ACD', $b->color);
    }

    public function testGreekCatholicDioceseLevel6(): void
    {
        $b = $this->boundary('religious_administration', 6, 'greek_catholic');
        $this->assertSame('egyházmegye', $b->type);
        $this->assertSame('#9370DB', $b->color);
    }

    public function testAdministrativeSettlementLevel8(): void
    {
        $b = $this->boundary('administrative', 8);
        $this->assertSame('település', $b->type);
        $this->assertSame('#5E8C61', $b->color);
    }

    public function testAdministrativeCountryLevel2(): void
    {
        $b = $this->boundary('administrative', 2);
        $this->assertSame('ország', $b->type);
        $this->assertSame('#D9534F', $b->color);
    }

    public function testPostalCode(): void
    {
        $b = $this->boundary('postal_code');
        $this->assertSame('postai kód', $b->type);
        $this->assertSame('#F0D966', $b->color);
    }

    /**
     * Definiált felekezet, de az adott admin_level nincs megadva -> szürke fallback
     * a "boundary (denomination)" névvel.
     */
    public function testReligiousAdministrationUndefinedLevelFallsBackToGray(): void
    {
        $b = $this->boundary('religious_administration', 8, 'greek_catholic');
        $this->assertSame('religious_administration (greek_catholic)', $b->type);
        $this->assertSame('#9E9E9E', $b->color);
    }
}
