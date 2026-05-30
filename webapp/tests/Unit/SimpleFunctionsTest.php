<?php

use PHPUnit\Framework\TestCase;

class SimpleFunctionsTest extends TestCase {

    public function testSanitizeTrimsAndKeepsAllowedTags() {
        $input = "  <b>Árvíztűrő</b>\n<script>alert('x')</script><a href='/ok'>link</a>  ";

        $result = sanitize($input);

        $this->assertEquals("<b>Árvíztűrő</b><br/>alert('x')<a href='/ok'>link</a>", $result);
    }

    public function testSanitizeWorksRecursivelyOnArrays() {
        $input = array(
            'title' => "  <strong>Cím</strong>  ",
            'body' => "sor1\nsor2"
        );

        $result = sanitize($input);

        $this->assertEquals('<strong>Cím</strong>', $result['title']);
        $this->assertEquals('sor1<br/>sor2', $result['body']);
    }
  
    public function testTwigHungarianDateFormatForTodayWithoutTime() {
        $result = twig_hungarian_date_format(date('Y-m-d H:i:s'), '');

        $this->assertEquals('ma', $result);
    }

    public function testTwigHungarianDateFormatForTodayWithDefaultTime() {
        $timestamp = time();

        $result = twig_hungarian_date_format($timestamp);

        $this->assertStringStartsWith('ma ', $result);
        $this->assertMatchesRegularExpression('/^ma \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForYesterday() {
        $timestamp = strtotime('-1 day');

        $result = twig_hungarian_date_format($timestamp, '');

        $this->assertStringStartsWith('tegnap, ', $result);
    }

    public function testTwigHungarianDateFormatForYesterdayWithTime() {
        $timestamp = strtotime('-1 day');

        $result = twig_hungarian_date_format($timestamp, 'H:i');

        $this->assertStringStartsWith('tegnap, ', $result);
        $this->assertMatchesRegularExpression('/ \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForTomorrow() {
        $timestamp = strtotime('+1 day');

        $result = twig_hungarian_date_format($timestamp, '');

        $this->assertStringStartsWith('holnap, ', $result);
    }

    public function testTwigHungarianDateFormatForTomorrowWithTime() {
        $timestamp = strtotime('+1 day');

        $result = twig_hungarian_date_format($timestamp, 'H:i');

        $this->assertStringStartsWith('holnap, ', $result);
        $this->assertMatchesRegularExpression('/ \d{2}:\d{2}$/', $result);
    }

    public function testTwigHungarianDateFormatForDateInCurrentWeekButNotTodayYesterdayTomorrow() {    
        $lastSunday = strtotime('last sunday');
        $todayMidnight = strtotime(date('Y-m-d'));
        $daysBetween = (int)(($todayMidnight - $lastSunday) / 86400);
        if ($daysBetween > 3) {
            $candidate = strtotime('last monday', $todayMidnight);
        } else {
            $candidate = strtotime('next saturday', $lastSunday);
        }
                
        $result = twig_hungarian_date_format($candidate, '');

        $this->assertMatchesRegularExpression('/[a-záéíóöőüű]+\.?\s+\d+\./', $result);
    }

    public function testTwigPhoneLinksWrapsPlusThirtySixFormat() {
        $result = (string)twig_phone_links('Hívható: +36 30 1234567 között.');

        $this->assertStringContainsString('<a href="tel:+36301234567"', $result);
        $this->assertStringContainsString('class="phone-link"', $result);
        $this->assertStringContainsString('>+36 30 1234567</a>', $result);
    }

    public function testTwigPhoneLinksWrapsZeroSixWithSpacesAndDashes() {
        $result = (string)twig_phone_links('Iroda: 06-30-123-4567');

        $this->assertStringContainsString('href="tel:+36301234567"', $result);
        $this->assertStringContainsString('>06-30-123-4567</a>', $result);
    }

    public function testTwigPhoneLinksWrapsCompactZeroSixNumber() {
        $result = (string)twig_phone_links('06301234567');

        $this->assertStringContainsString('href="tel:+36301234567"', $result);
    }

    public function testTwigPhoneLinksWrapsBudapestLandlineWithParens() {
        $result = (string)twig_phone_links('Plébánia: +36 (1) 234-5678');

        $this->assertStringContainsString('href="tel:+3612345678"', $result);
    }

    public function testTwigPhoneLinksWrapsParensAreaCodeWithoutCountryPrefix() {
        // borazslo #438 review: a régi adatokban gyakran így van a vidéki szám:
        // "(62) 442-384" - zárójeles körzet országhívó nélkül. Magyarnak vesszük
        // és +36-tal prefixáljuk a tel: linket.
        $result = (string)twig_phone_links('Tel: (62) 442-384');

        $this->assertStringContainsString('href="tel:+3662442384"', $result);
        $this->assertStringContainsString('>(62) 442-384</a>', $result);
    }

    public function testTwigPhoneLinksWrapsParensAreaCodeWithSpaceSeparator() {
        // Másik gyakori formátum: "(1) 234 5678" - zárójeles körzet, szóköz tagolás.
        $result = (string)twig_phone_links('Hivatal: (1) 234 5678');

        $this->assertStringContainsString('href="tel:+3612345678"', $result);
    }

    public function testTwigPhoneLinksLeavesShortDigitSequencesAlone() {
        // No country prefix -> not a phone, just a date or post code
        $input = 'Mise: 2023-04-05, irányítószám: 1052';

        $result = (string)twig_phone_links($input);

        $this->assertEquals($input, $result);
    }

    public function testTwigPhoneLinksDoesNotRewrapExistingAnchorTel() {
        $input = '<a href="tel:+36301234567">+36 30 1234567</a>';

        $result = (string)twig_phone_links($input);

        $this->assertEquals($input, $result);
        // No nested <a>
        $this->assertEquals(1, substr_count($result, '<a '));
    }

    public function testTwigPhoneLinksLeavesPlainTextUntouched() {
        $input = 'A plébánia címe: Budapest, Fő utca 1.';

        $this->assertEquals($input, (string)twig_phone_links($input));
    }

    public function testTwigPhoneLinksHandlesMultipleNumbersInOneString() {
        $result = (string)twig_phone_links("Iroda: +36 1 234 5678\nMobil: 06 30 1234567");

        $this->assertEquals(2, substr_count($result, '<a href="tel:'));
        $this->assertStringContainsString('tel:+3612345678', $result);
        $this->assertStringContainsString('tel:+36301234567', $result);
    }

    public function testTwigPhoneLinksReturnsEmptyStringWhenInputIsEmpty() {
        $this->assertEquals('', twig_phone_links(''));
    }

    public function testTwigPhoneLinksReturnsNullInputUnchanged() {
        $this->assertNull(twig_phone_links(null));
    }

    public function testTwigPhoneLinksHandlesStringableObjectsLikeTwigMarkup() {
        // |nl2br already returned a Twig\Markup before our filter runs.
        $markupLike = new class {
            public function __toString(): string { return 'Hívható: +36 30 1234567'; }
        };

        $result = (string)twig_phone_links($markupLike);

        $this->assertStringContainsString('href="tel:+36301234567"', $result);
    }

    public function testTwigPhoneLinksReturnsTwigMarkupSoTrailingRawIsNotNeeded() {
        // borazslo #438 review: a "|raw|nl2br|phone_links" lánc akkor működik
        // helyesen (nem encode-olja <strong>-et &lt;strong&gt;-vé), ha a
        // phone_links is Twig\Markup-ot ad vissza. Így a template-ben nem kell
        // trailing |raw.
        $result = twig_phone_links('<strong>Phone:</strong> +36 30 1234567');

        $this->assertInstanceOf(\Twig\Markup::class, $result);
    }

    public function testTwigPhoneLinksReturnsTwigMarkupEvenForUnmatchedInput() {
        // Akkor is Markup legyen, ha nincs telefonszám a stringben - különben
        // a template auto-escape-eli az output-ot.
        $result = twig_phone_links('Csak sima HTML <em>kiemelés</em> nélkül szám.');

        $this->assertInstanceOf(\Twig\Markup::class, $result);
    }

    public function testTwigHungarianDateFormatForDateInCurrentWeekIncludesTimeWhenRequested() {
        $lastSunday = strtotime('last sunday');
        $todayMidnight = strtotime(date('Y-m-d'));
        $daysBetween = (int)(($todayMidnight - $lastSunday) / 86400);
        if ($daysBetween > 3) {
            $candidate = strtotime('last monday', $todayMidnight);
        } else {
            $candidate = strtotime('next saturday', $lastSunday);
        }

        $result = twig_hungarian_date_format($candidate, 'H:i');

        $this->assertMatchesRegularExpression('/[a-záéíóöőüű]+\.?\s+\d+\..*\d{2}:\d{2}$/', $result);
    }
}