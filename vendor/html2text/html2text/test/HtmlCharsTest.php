<?php

namespace Html2Text;

class HtmlCharsTest extends \PHPUnit_Framework_TestCase
{
    public function testLaquoAndRaquo()
    {
        $html = 'This library name is &laquo;Html2Text&raquo;';
        $expected = 'This library name is «Html2Text»';

        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }

    public function provideSymbols()
    {
        // A variety of symbols that either used to have special handling
        // or still does.
        return array(
            // Non-breaking space, not a regular one.
            array('&nbsp;', ' '),
            array('&gt;', '>'),
            array('&lt;', '<'),
            array('&copy;', '©'),
            array('&#169;', '©'),
            array('&trade;', '™'),
            // The TM symbol in Windows-1252, invalid in HTML...
            array('&#153;', '™'),
            // Correct TM symbol numeric code
            array('&#8482;', '™'),
            array('&reg;', '®'),
            array('&#174;', '®'),
            array('&mdash;', '—'),
            // The m-dash in Windows-1252, invalid in HTML...
            array('&#151;', '—'),
            // Correct m-dash numeric code
            array('&#8212;', '—'),
            array('&bull;', '•'),
            array('&pound;', '£'),
            array('&#163;', '£'),
            array('&euro;', '€'),
            array('&amp;', '&'),
        );
    }

    /**
     * @dataProvider provideSymbols
     */
    public function testSymbol($entity, $symbol)
    {
        $html = "$entity signs should be UTF-8 symbols";
        $expected = "$symbol signs should be UTF-8 symbols";

        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }
}
