<?php

use PHPUnit\Framework\TestCase;

class UtilityFunctionsTest extends TestCase {

    public function testBr2nlConvertsBreakTagsToNewLines() {
        $input = "first<br>second<br />third";

        $result = br2nl($input);

        $this->assertEquals("first" . PHP_EOL . "second" . PHP_EOL . "third", $result);
    }

    public function testEnvReturnsDefaultWhenVariableIsMissing() {
        putenv('MISEREND_TEST_ENV');

        $result = env('MISEREND_TEST_ENV', 'fallback-value');

        $this->assertEquals('fallback-value', $result);
    }

    public function testEnvReturnsValueWhenVariableExists() {
        putenv('MISEREND_TEST_ENV=present-value');

        $result = env('MISEREND_TEST_ENV', 'fallback-value');

        $this->assertEquals('present-value', $result);

        putenv('MISEREND_TEST_ENV');
    }

    public function testFileExistsCiFindsKnownClassFileCaseInsensitively() {
        $expectedPath = PATH . 'classes/translator.php';
        $lookupPath = PATH . 'classes/TRANSLATOR.php';

        $result = file_exists_ci($lookupPath);

        $this->assertEquals($expectedPath, $result);
    }

    public function testFileExistsCiReturnsInputPathWhenFileExistsWithExactCase() {
        $expectedPath = PATH . 'classes/translator.php';

        $result = file_exists_ci($expectedPath);

        $this->assertEquals($expectedPath, $result);
    }

    public function testFileExistsCiReturnsFalseWhenFileIsNotFound() {
        $lookupPath = PATH . 'classes/this_file_does_not_exist.php';

        $result = file_exists_ci($lookupPath);

        $this->assertFalse($result);
    }
}
