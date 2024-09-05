<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\CssSelector\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\CssSelector\CssSelectorConverter;

class CssSelectorConverterTest extends TestCase
{
    public function testCssToXPath()
    {
        $converter = new CssSelectorConverter();

        $this->assertEquals('descendant-or-self::*', $converter->toXPath(''));
        $this->assertEquals('descendant-or-self::h1', $converter->toXPath('h1'));
        $this->assertEquals("descendant-or-self::h1[@id = 'foo']", $converter->toXPath('h1#foo'));
        $this->assertEquals("descendant-or-self::h1[@class and contains(concat(' ', normalize-space(@class), ' '), ' foo ')]", $converter->toXPath('h1.foo'));
        $this->assertEquals('descendant-or-self::foo:h1', $converter->toXPath('foo|h1'));
        $this->assertEquals('descendant-or-self::h1', $converter->toXPath('H1'));
    }

    public function testCssToXPathXml()
    {
        $converter = new CssSelectorConverter(false);

        $this->assertEquals('descendant-or-self::H1', $converter->toXPath('H1'));
    }

    /**
     * @expectedException \Symfony\Component\CssSelector\Exception\ParseException
     * @expectedExceptionMessage Expected identifier, but <eof at 3> found.
     */
    public function testParseExceptions()
    {
        $converter = new CssSelectorConverter();
        $converter->toXPath('h1:');
    }

    /** @dataProvider getCssToXPathWithoutPrefixTestData */
    public function testCssToXPathWithoutPrefix($css, $xpath)
    {
        $converter = new CssSelectorConverter();

        $this->assertEquals($xpath, $converter->toXPath($css, ''), '->parse() parses an input string and returns a node');
    }

    public function getCssToXPathWithoutPrefixTestData()
    {
        return array(
            array('h1', 'h1'),
            array('foo|h1', 'foo:h1'),
            array('h1, h2, h3', 'h1 | h2 | h3'),
            array('h1:nth-child(3n+1)', "*/*[(name() = 'h1') and (position() - 1 >= 0 and (position() - 1) mod 3 = 0)]"),
            array('h1 > p', 'h1/p'),
            array('h1#foo', "h1[@id = 'foo']"),
            array('h1.foo', "h1[@class and contains(concat(' ', normalize-space(@class), ' '), ' foo ')]"),
            array('h1[class*="foo bar"]', "h1[@class and contains(@class, 'foo bar')]"),
            array('h1[foo|class*="foo bar"]', "h1[@foo:class and contains(@foo:class, 'foo bar')]"),
            array('h1[class]', 'h1[@class]'),
            array('h1 .foo', "h1/descendant-or-self::*/*[@class and contains(concat(' ', normalize-space(@class), ' '), ' foo ')]"),
            array('h1 #foo', "h1/descendant-or-self::*/*[@id = 'foo']"),
            array('h1 [class*=foo]', "h1/descendant-or-self::*/*[@class and contains(@class, 'foo')]"),
            array('div>.foo', "div/*[@class and contains(concat(' ', normalize-space(@class), ' '), ' foo ')]"),
            array('div > .foo', "div/*[@class and contains(concat(' ', normalize-space(@class), ' '), ' foo ')]"),
        );
    }
}
