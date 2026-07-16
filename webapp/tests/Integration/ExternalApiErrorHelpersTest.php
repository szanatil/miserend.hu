<?php

use PHPUnit\Framework\TestCase;
use ExternalApi\ExternalApi;

class ExternalApiErrorHelpersTest extends TestCase {

    // ── hasError() ──────────────────────────────────────────────

    public function testHasErrorReturnsFalseWhenNoError(): void {
        $api = new ExternalApi();
        $this->assertFalse($api->hasError());
    }

    public function testHasErrorReturnsTrueWithStringError(): void {
        $api = new ExternalApi();
        $api->error = "Connection timed out after 60001 milliseconds (curl)";
        $this->assertTrue($api->hasError());
    }

    public function testHasErrorReturnsTrueWithArrayError(): void {
        $api = new ExternalApi();
        $api->error = [28, "Connection timed out"];
        $this->assertTrue($api->hasError());
    }

    public function testHasErrorReturnsFalseWithEmptyString(): void {
        $api = new ExternalApi();
        $api->error = "";
        $this->assertFalse($api->hasError());
    }

    public function testHasErrorReturnsFalseWithEmptyArray(): void {
        $api = new ExternalApi();
        $api->error = [];
        $this->assertFalse($api->hasError());
    }

    // ── getErrorMessage() ───────────────────────────────────────

    public function testGetErrorMessageWithString(): void {
        $api = new ExternalApi();
        $api->error = "Connection timed out";
        $this->assertEquals("Connection timed out", $api->getErrorMessage());
    }

    public function testGetErrorMessageWithArray(): void {
        $api = new ExternalApi();
        $api->error = [28, "Connection timed out"];
        $this->assertEquals("28 | Connection timed out", $api->getErrorMessage());
    }

    public function testGetErrorMessageWithNull(): void {
        $api = new ExternalApi();
        $api->error = null;
        $this->assertEquals("", $api->getErrorMessage());
    }

    // ── getErrorMessageHtml() ───────────────────────────────────

    public function testGetErrorMessageHtmlContainsDetails(): void {
        $api = new ExternalApi();
        $api->error = "timeout";
        $html = $api->getErrorMessageHtml();
        
        $this->assertStringContainsString('<details>', $html);
        $this->assertStringContainsString('<summary>', $html);
        $this->assertStringContainsString('</details>', $html);
        $this->assertStringContainsString('timeout', $html);
    }

    public function testGetErrorMessageHtmlEscapesHtml(): void {
        $api = new ExternalApi();
        $api->error = '<script>alert("xss")</script>';
        $html = $api->getErrorMessageHtml();
        
        // The <script> tag should be escaped, not raw
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testGetErrorMessageHtmlUsesCustomSummary(): void {
        $api = new ExternalApi();
        $api->error = "some error";
        $html = $api->getErrorMessageHtml('Az Overpass API nem elérhető');
        
        $this->assertStringContainsString('Az Overpass API nem elérhető', $html);
    }
}
