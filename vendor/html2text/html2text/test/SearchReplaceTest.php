<?php

namespace Html2Text;

class SearchReplaceTest extends \PHPUnit_Framework_TestCase
{
    public function searchReplaceDataProvider() {
        return array(
            'Bold' => array(
                'html'      => 'Hello, &quot;<b>world</b>&quot;!',
                'expected'  => 'Hello, "WORLD"!',
            ),
            'Strong' => array(
                'html'      => 'Hello, &quot;<strong>world</strong>&quot;!',
                'expected'  => 'Hello, "WORLD"!',
            ),
            'Italic' => array(
                'html'      => 'Hello, &quot;<i>world</i>&quot;!',
                'expected'  => 'Hello, "_world_"!',
            ),
            'Header' => array(
                'html'      => '<h1>Hello, world!</h1>',
                'expected'  => "HELLO, WORLD!\n\n",
            ),
            'Table Header' => array(
                'html'      => '<th>Hello, World!</th>',
                'expected'  => "\t\tHELLO, WORLD!\n",
            ),
        );
    }

    /**
     * @dataProvider searchReplaceDataProvider
     */
    public function testSearchReplace($html, $expected)
    {
        $html = new Html2Text($html);
        $this->assertEquals($expected, $html->getText());
    }
}
