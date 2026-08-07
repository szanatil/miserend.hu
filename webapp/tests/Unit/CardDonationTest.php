<?php

use PHPUnit\Framework\TestCase;

final class CardDonationTest extends TestCase
{
    /** @dataProvider donationValues */
    public function testOsmValueGetsPublicMessage(string $value, bool $available, string $message): void
    {
        $church = new \Eloquent\Church();
        $church->setAttribute('payment:credit_cards', $value);

        $this->assertSame($value, $church->cardDonation['value']);
        $this->assertSame($available, $church->cardDonation['available']);
        $this->assertSame($message, $church->cardDonation['message']);
    }

    public static function donationValues(): array
    {
        return [
            ['yes', true, 'Bankkártyás digitális persely bármikor elérhető.'],
            ['limited', true, 'Bankkártyás adományozás a sekrestyében vagy külön kérésre lehetséges.'],
            ['no', false, 'Csak készpénzes adományozás lehetséges.'],
        ];
    }

    public function testMissingOsmValueDoesNotCreatePublicClaim(): void
    {
        $church = new \Eloquent\Church();

        $this->assertNull($church->cardDonation['message']);
        $this->assertFalse($church->cardDonation['available']);
    }
}
