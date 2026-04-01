<?php

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {

    public function __construct(?string $name = null)
    {        
        parent::__construct($name);
        include_once __DIR__ . '/../classes/request.php';
        
        // Mock sanitize function if not exists
        if (!function_exists('sanitize')) {
            function sanitize($value) {
                return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    protected function tearDown(): void {
        // Clean up $_REQUEST after each test
        $_REQUEST = [];
    }

    // Integer() tests
    public function testInteger() {
        $_REQUEST['test'] = 123;
        $this->assertEquals(123, \Request::Integer('test'));
    }

    public function testIntegerInvalid() {
        $_REQUEST['test'] = 'abc';
        $this->expectException(Exception::class);
        \Request::Integer('test');
    }

    public function testIntegerEmpty() {
        $_REQUEST['test'] = '';
        $this->assertEquals('', \Request::Integer('test'));
    }

    public function testIntegerNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::Integer('test'));
    }

    // IntegerRequired() tests
    public function testIntegerRequired() {
        $_REQUEST['test'] = 456;
        $this->assertEquals(456, \Request::IntegerRequired('test'));
    }

    public function testIntegerRequiredInvalid() {
        $_REQUEST['test'] = 'invalid';
        $this->expectException(Exception::class);
        \Request::IntegerRequired('test');
    }

    public function testIntegerRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::IntegerRequired('test');
    }

    // IntegerwDefault() tests
    public function testIntegerWithDefault() {
        $_REQUEST['test'] = 789;
        $this->assertEquals(789, \Request::IntegerwDefault('test', 100));
    }

    public function testIntegerWithDefaultUseDefault() {
        unset($_REQUEST['test']);
        $this->assertEquals(100, \Request::IntegerwDefault('test', 100));
    }

    public function testIntegerWithDefaultInvalid() {
        $_REQUEST['test'] = 'invalid';
        $this->expectException(Exception::class);
        \Request::IntegerwDefault('test', 100);
    }

    // Text() tests
    public function testText() {
        $_REQUEST['test'] = 'Hello World';
        $result = \Request::Text('test');
        $this->assertIsString($result);
    }

    public function testTextEmpty() {
        $_REQUEST['test'] = '';
        $result = \Request::Text('test');
        $this->assertEquals('', $result);
    }

    public function testTextNotSet() {
        unset($_REQUEST['test']);
        $result = \Request::Text('test');
        // sanitize(false) returns empty string or '0'
        $this->assertIsString($result);
    }

    // TextRequired() tests
    public function testTextRequired() {
        $_REQUEST['test'] = 'Required Text';
        $result = \Request::TextRequired('test');
        $this->assertIsString($result);
    }

    public function testTextRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::TextRequired('test');
    }

    // TextwDefault() tests
    public function testTextWithDefault() {
        $_REQUEST['test'] = 'Custom';
        $result = \Request::TextwDefault('test', 'Default');
        $this->assertIsString($result);
    }

    public function testTextWithDefaultUseDefault() {
        unset($_REQUEST['test']);
        $result = \Request::TextwDefault('test', 'Default');
        $this->assertEquals('Default', $result);
    }

    // InArray() tests
    public function testInArray() {
        $_REQUEST['test'] = 'value1';
        $array = ['value1', 'value2', 'value3'];
        $this->assertEquals('value1', \Request::InArray('test', $array));
    }

    public function testInArrayNotSet() {
        unset($_REQUEST['test']);
        $array = ['value1', 'value2'];
        $this->assertFalse(\Request::InArray('test', $array));
    }

    public function testInArrayInvalid() {
        $_REQUEST['test'] = 'invalid';
        $array = ['value1', 'value2'];
        $this->expectException(Exception::class);
        \Request::InArray('test', $array);
    }

    public function testInArrayEmpty() {
        $_REQUEST['test'] = '';
        $array = ['value1', 'value2'];
        $this->assertFalse(\Request::InArray('test', $array));
    }

    // InArrayRequired() tests
    public function testInArrayRequired() {
        $_REQUEST['test'] = 'value1';
        $array = ['value1', 'value2'];
        $this->assertEquals('value1', \Request::InArrayRequired('test', $array));
    }

    public function testInArrayRequiredInvalid() {
        $_REQUEST['test'] = 'invalid';
        $array = ['value1', 'value2'];
        $this->expectException(Exception::class);
        \Request::InArrayRequired('test', $array);
    }

    public function testInArrayRequiredNotSet() {
        unset($_REQUEST['test']);
        $array = ['value1', 'value2'];
        $this->expectException(Exception::class);
        \Request::InArrayRequired('test', $array);
    }

    // Simpletext() tests
    public function testSimpletext() {
        $_REQUEST['test'] = 'valid_text-123';
        $this->assertEquals('valid_text-123', \Request::Simpletext('test'));
    }

    public function testSimpletextInvalid() {
        $_REQUEST['test'] = 'invalid text!';
        $this->expectException(Exception::class);
        \Request::Simpletext('test');
    }

    public function testSimpletextEmpty() {
        $_REQUEST['test'] = '';
        $this->assertEquals('', \Request::Simpletext('test'));
    }

    public function testSimpletextNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::Simpletext('test'));
    }

    // SimpletextwDefault() tests
    public function testSimpletextWithDefault() {
        $_REQUEST['test'] = 'valid-123';
        $this->assertEquals('valid-123', \Request::SimpletextwDefault('test', 'default'));
    }

    public function testSimpletextWithDefaultUseDefault() {
        unset($_REQUEST['test']);
        $this->assertEquals('default', \Request::SimpletextwDefault('test', 'default'));
    }

    public function testSimpletextWithDefaultInvalid() {
        $_REQUEST['test'] = 'invalid!';
        $this->expectException(Exception::class);
        \Request::SimpletextwDefault('test', 'default');
    }

    // SimpletextRequired() tests
    public function testSimpletextRequired() {
        $_REQUEST['test'] = 'required_text-123';
        $this->assertEquals('required_text-123', \Request::SimpletextRequired('test'));
    }

    public function testSimpletextRequiredInvalid() {
        $_REQUEST['test'] = 'invalid!';
        $this->expectException(Exception::class);
        \Request::SimpletextRequired('test');
    }

    public function testSimpletextRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::SimpletextRequired('test');
    }

    // Date() tests
    public function testDate() {
        $_REQUEST['test'] = '2023-01-01';
        $this->assertEquals('2023-01-01', \Request::Date('test'));
    }

    public function testDateEmpty() {
        $_REQUEST['test'] = '';
        $this->assertEquals('', \Request::Date('test'));
    }

    public function testDateNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::Date('test'));
    }

    public function testDateInvalidFormat() {
        $_REQUEST['test'] = '01/01/2023';
        $this->expectException(Exception::class);
        \Request::Date('test');
    }

    public function testDateInvalidMonth() {
        $_REQUEST['test'] = '2026-16-12';
        $this->expectException(Exception::class);
        \Request::Date('test');
    }

    public function testDateInvalidDay() {
        $_REQUEST['test'] = '2026-01-32';
        $this->expectException(Exception::class);
        \Request::Date('test');
    }

    public function testDateInvalidLeapYear() {
        $_REQUEST['test'] = '2023-02-29';
        $this->expectException(Exception::class);
        \Request::Date('test');
    }

    public function testDateValidLeapYear() {
        $_REQUEST['test'] = '2024-02-29';
        $this->assertEquals('2024-02-29', \Request::Date('test'));
    }

    public function testDateNotValidString() {
        $_REQUEST['test'] = 'not-a-date';
        $this->expectException(Exception::class);
        \Request::Date('test');
    }

    // DateRequired() tests
    public function testDateRequired() {
        $_REQUEST['test'] = '2023-01-01';
        $this->assertEquals('2023-01-01', \Request::DateRequired('test'));
    }

    public function testDateRequiredInvalidMonth() {
        $_REQUEST['test'] = '2026-16-12';
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    public function testDateRequiredInvalidDay() {
        $_REQUEST['test'] = '2026-01-32';
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    public function testDateRequiredInvalidFormat() {
        $_REQUEST['test'] = '01/01/2023';
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    public function testDateRequiredInvalidLeapYear() {
        $_REQUEST['test'] = '2023-02-29';
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    public function testDateRequiredValidLeapYear() {
        $_REQUEST['test'] = '2024-02-29';
        $this->assertEquals('2024-02-29', \Request::DateRequired('test'));
    }

    public function testDateRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    public function testDateRequiredInvalidString() {
        $_REQUEST['test'] = 'invalid-date';
        $this->expectException(Exception::class);
        \Request::DateRequired('test');
    }

    // DatewDefault() tests
    public function testDateWithDefault() {
        $_REQUEST['test'] = '2023-06-15';
        $this->assertEquals('2023-06-15', \Request::DatewDefault('test', '2020-01-01'));
    }

    public function testDateWithDefaultUseDefault() {
        unset($_REQUEST['test']);
        $result = \Request::DatewDefault('test', '2020-01-01');
        $this->assertEquals('2020-01-01', $result);
    }

    public function testDateWithDefaultInvalid() {
        $_REQUEST['test'] = 'invalid';
        $this->expectException(Exception::class);
        \Request::DatewDefault('test', '2020-01-01');
    }

    public function testDateWithDefaultInvalidMonth() {
        $_REQUEST['test'] = '2026-16-12';
        $this->expectException(Exception::class);
        \Request::DatewDefault('test', '2020-01-01');
    }

    public function testDateWithDefaultInvalidDay() {
        $_REQUEST['test'] = '2026-01-32';
        $this->expectException(Exception::class);
        \Request::DatewDefault('test', '2020-01-01');
    }

    public function testDateWithDefaultInvalidFormat() {
        $_REQUEST['test'] = '01/01/2023';
        $this->expectException(Exception::class);
        \Request::DatewDefault('test', '2020-01-01');
    }

    public function testDateWithDefaultInvalidLeapYear() {
        $_REQUEST['test'] = '2023-02-29';
        $this->expectException(Exception::class);
        \Request::DatewDefault('test', '2020-01-01');
    }

    public function testDateWithDefaultValidLeapYear() {
        $_REQUEST['test'] = '2024-02-29';
        $this->assertEquals('2024-02-29', \Request::DatewDefault('test', '2020-01-01'));
    }

    public function testDateWithDefaultEmpty() {
        $_REQUEST['test'] = '';
        $result = \Request::DatewDefault('test', '2020-01-01');
        $this->assertEquals('2020-01-01', $result);
    }

    // get() tests
    public function testGet() {
        $_REQUEST['simple'] = 'value';
        $this->assertEquals('value', \Request::get('simple'));
    }

    public function testGetNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::get('test'));
    }

    // Test nested array access
    public function testGetNestedArray() {
        $_REQUEST['church'] = ['lat' => '47.5', 'lng' => '19.0'];
        $this->assertEquals('47.5', \Request::get('church[lat]'));
        $this->assertEquals('19.0', \Request::get('church[lng]'));
    }

    public function testGetNestedArrayNotSet() {
        $_REQUEST['church'] = [];
        $this->assertFalse(\Request::get('church[lat]'));
    }

    // getwDefault() tests
    public function testGetwDefault() {
        $_REQUEST['test'] = 'exists';
        $this->assertEquals('exists', \Request::getwDefault('test', 'default'));
    }

    public function testGetwDefaultUseDefault() {
        unset($_REQUEST['test']);
        $this->assertEquals('default', \Request::getwDefault('test', 'default'));
    }

    // getRequired() tests
    public function testGetRequired() {
        $_REQUEST['test'] = 'required_value';
        $this->assertEquals('required_value', \Request::getRequired('test'));
    }

    public function testGetRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::getRequired('test');
    }

    // IntegerArray() tests
    public function testIntegerArray() {
        $_REQUEST['test'] = [1, 2, 3, 4, 5];
        $result = \Request::IntegerArray('test');
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testIntegerArrayWithStringNumbers() {
        $_REQUEST['test'] = ['1', '2', '3'];
        $result = \Request::IntegerArray('test');
        $this->assertEquals(['1', '2', '3'], $result);
    }

    public function testIntegerArrayInvalid() {
        $_REQUEST['test'] = [1, 2, 'invalid', 4];
        $this->expectException(Exception::class);
        \Request::IntegerArray('test');
    }

    public function testIntegerArrayNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::IntegerArray('test'));
    }

    public function testIntegerArrayEmpty() {
        $_REQUEST['test'] = '';
        $this->assertFalse(\Request::IntegerArray('test'));
    }

    public function testIntegerArrayNotAnArray() {
        $_REQUEST['test'] = 'not an array';
        $this->expectException(Exception::class);
        \Request::IntegerArray('test');
    }

    // IntegerArrayRequired() tests
    public function testIntegerArrayRequired() {
        $_REQUEST['test'] = [10, 20, 30];
        $result = \Request::IntegerArrayRequired('test');
        $this->assertEquals([10, 20, 30], $result);
    }

    public function testIntegerArrayRequiredInvalid() {
        $_REQUEST['test'] = [1, 2, 'invalid'];
        $this->expectException(Exception::class);
        \Request::IntegerArrayRequired('test');
    }

    public function testIntegerArrayRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::IntegerArrayRequired('test');
    }

    public function testIntegerArrayRequiredEmpty() {
        $_REQUEST['test'] = '';
        $this->expectException(Exception::class);
        \Request::IntegerArrayRequired('test');
    }

    public function testIntegerArrayRequiredNotAnArray() {
        $_REQUEST['test'] = 'not an array';
        $this->expectException(Exception::class);
        \Request::IntegerArrayRequired('test');
    }

    // StringArray() tests
    public function testStringArray() {
        $_REQUEST['test'] = ['hello', 'world', 'test'];
        $result = \Request::StringArray('test');
        $this->assertEquals(['hello', 'world', 'test'], $result);
    }

    public function testStringArrayWithNumbers() {
        $_REQUEST['test'] = ['123', 'abc', 'test'];
        $result = \Request::StringArray('test');
        $this->assertEquals(['123', 'abc', 'test'], $result);
    }

    public function testStringArrayInvalid() {
        $_REQUEST['test'] = ['hello', 123, 'world'];
        $this->expectException(Exception::class);
        \Request::StringArray('test');
    }

    public function testStringArrayNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::StringArray('test'));
    }

    public function testStringArrayEmpty() {
        $_REQUEST['test'] = '';
        $this->assertFalse(\Request::StringArray('test'));
    }

    public function testStringArrayNotAnArray() {
        $_REQUEST['test'] = 'not an array';
        $this->expectException(Exception::class);
        \Request::StringArray('test');
    }

    // StringArrayRequired() tests
    public function testStringArrayRequired() {
        $_REQUEST['test'] = ['required', 'array', 'test'];
        $result = \Request::StringArrayRequired('test');
        $this->assertEquals(['required', 'array', 'test'], $result);
    }

    public function testStringArrayRequiredInvalid() {
        $_REQUEST['test'] = ['hello', 456, 'world'];
        $this->expectException(Exception::class);
        \Request::StringArrayRequired('test');
    }

    public function testStringArrayRequiredNotSet() {
        unset($_REQUEST['test']);
        $this->expectException(Exception::class);
        \Request::StringArrayRequired('test');
    }

    public function testStringArrayRequiredEmpty() {
        $_REQUEST['test'] = '';
        $this->expectException(Exception::class);
        \Request::StringArrayRequired('test');
    }

    public function testStringArrayRequiredNotAnArray() {
         $_REQUEST['test'] = 'not an array';
         $this->expectException(Exception::class);
         \Request::StringArrayRequired('test');
     }

    // Boolean() tests
    public function testBoolean() {
        $_REQUEST['test'] = true;
        $this->assertTrue(\Request::Boolean('test'));
    }

    public function testBooleanString1() {
        $_REQUEST['test'] = '1';
        $this->assertTrue(\Request::Boolean('test'));
    }

    public function testBooleanString0() {
        $_REQUEST['test'] = '0';
        $this->assertFalse(\Request::Boolean('test'));
    }

    public function testBooleanInt1() {
        $_REQUEST['test'] = 1;
        $this->assertTrue(\Request::Boolean('test'));
    }

    public function testBooleanInt0() {
        $_REQUEST['test'] = 0;
        $this->assertFalse(\Request::Boolean('test'));
    }

    public function testBooleanFalse() {
        $_REQUEST['test'] = false;
        $this->assertFalse(\Request::Boolean('test'));
    }

    public function testBooleanNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::Boolean('test'));
    }

    public function testBooleanEmpty() {
        $_REQUEST['test'] = '';
        $this->assertFalse(\Request::Boolean('test'));
    }

    public function testBooleanInvalid() {
        $_REQUEST['test'] = 'invalid';
        $this->expectException(Exception::class);
        \Request::Boolean('test');
    }

    // ArrayArray() tests
    public function testArrayArray() {
        $_REQUEST['test'] = [[1, 2], [3, 4]];
        $result = \Request::ArrayArray('test');
        $this->assertEquals([[1, 2], [3, 4]], $result);
    }

    public function testArrayArrayEmpty() {
        $_REQUEST['test'] = [];
        $result = \Request::ArrayArray('test');
        $this->assertEquals([], $result);
    }

    public function testArrayArrayNotSet() {
        unset($_REQUEST['test']);
        $this->assertFalse(\Request::ArrayArray('test'));
    }

    public function testArrayArrayFalse() {
        $_REQUEST['test'] = false;
        $this->assertFalse(\Request::ArrayArray('test'));
    }

    public function testArrayArrayNotAnArray() {
        $_REQUEST['test'] = 'not an array';
        $this->expectException(Exception::class);
        \Request::ArrayArray('test');
    }

    public function testArrayArrayItemIsNotAnArray() {
        $_REQUEST['test'] = [[1, 2], 'not an array'];
        $this->expectException(Exception::class);
        \Request::ArrayArray('test');
    }

    // ArrayArraywDefault() tests
    public function testArrayArrayWithDefault() {
        $_REQUEST['test'] = [[1, 2], [3, 4]];
        $result = \Request::ArrayArraywDefault('test', [['default']]);
        $this->assertEquals([[1, 2], [3, 4]], $result);
    }

    public function testArrayArrayWithDefaultUseDefault() {
        unset($_REQUEST['test']);
        $result = \Request::ArrayArraywDefault('test', [['default1'], ['default2']]);
        $this->assertEquals([['default1'], ['default2']], $result);
    }

    public function testArrayArrayWithDefaultNotAnArray() {
        $_REQUEST['test'] = 'not an array';
        $this->expectException(Exception::class);
        \Request::ArrayArraywDefault('test', ['default']);
    }

    public function testArrayArrayWithDefaultNoDefault() {
        unset($_REQUEST['test']);
        $result = \Request::ArrayArraywDefault('test');
        $this->assertEquals([], $result);
    }

    public function testArrayArrayWithDefaultItemIsNotAnArray() {
        $_REQUEST['test'] = [[1, 2], 'not an array'];
        $this->expectException(Exception::class);
        \Request::ArrayArraywDefault('test', [['default']]);
    }
}
