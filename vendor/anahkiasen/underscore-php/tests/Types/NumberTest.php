<?php
namespace Underscore\Types;

use Underscore\UnderscoreTestCase;

class NumberTest extends UnderscoreTestCase
{
    public function testCanCreateNewNumber()
    {
        $this->assertEquals(0, Number::create()->obtain());
    }

    public function testCanAccessStrPad()
    {
        $number = Number::pad(5, 3, 1, STR_PAD_BOTH);

        $this->assertEquals('151', $number);
    }

    public function testCanPadANumber()
    {
        $number = Number::padding(5, 3);

        $this->assertEquals('050', $number);
    }

    public function testCanPadANumberOnTheLeft()
    {
        $number = Number::paddingLeft(5, 3);

        $this->assertEquals('005', $number);
    }

    public function testCanPadANumberOnTheRight()
    {
        $number = Number::paddingRight(5, 3);

        $this->assertEquals('500', $number);
    }

    public function testCanUsePhpRoundingMethods()
    {
        $number = Number::round(5.33);
        $this->assertEquals(5, $number);

        $number = Number::ceil(5.33);
        $this->assertEquals(6, $number);

        $number = Number::floor(5.33);
        $this->assertEquals(5, $number);
    }
}
