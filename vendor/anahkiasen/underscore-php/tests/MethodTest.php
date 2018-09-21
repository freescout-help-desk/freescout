<?php
namespace Underscore;

use Underscore\Types\Arrays;
use Underscore\Types\Strings;

class MethodTest extends UnderscoreTestCase
{
    public function testThrowsErrorIfIncorrectMethod()
    {
        $this->setExpectedException('BadMethodCallException');

        Underscore::invalid('foo');
    }

    public function testHasAccessToOriginalPhpFunctions()
    {
        $array = Arrays::from($this->array);
        $array = $array->intersect(['foo' => 'bar', 'kal' => 'mon']);

        $this->assertEquals(['foo' => 'bar'], $array->obtain());

        $string = Strings::repeat('foo', 2);
        $this->assertEquals('foofoo', $string);

        $string = Strings::from('   foo  ')->trim();
        $this->assertEquals('foo', $string->obtain());
    }

    public function testCantChainCertainMethods()
    {
        $method = Method::isUnchainable('Arrays', 'range');

        $this->assertTrue($method);
    }

    public function testCanGetMethodsFromType()
    {
        $method = Method::getMethodsFromType('\Underscore\Types\Arrays');

        $this->assertEquals('\Underscore\Methods\ArraysMethods', $method);
    }

    public function testCanGetAliasesOfFunctions()
    {
        $method = Method::getAliasOf('select');

        $this->assertEquals('filter', $method);
    }

    public function testCanFindMethodsInClasses()
    {
        $method = Method::findInClasses('\Underscore\Underscore', 'range');

        $this->assertEquals('\Underscore\Types\\Arrays', $method);
    }

    public function testCanThrowExceptionAtUnknownMethods()
    {
        $this->setExpectedException('BadMethodCallException',
            'The method Underscore\Types\Arrays::fuck does not exist');

        $test = Arrays::fuck($this);
    }
}
