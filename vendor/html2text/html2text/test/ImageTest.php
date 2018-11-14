<?php

namespace Html2Text;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    public function testImageDataProvider() {
        return array(
            'Without alt tag' => array(
                'html' => '<img src="http://example.com/example.jpg">',
                'expected'  => '',
            ),
            'Without alt tag, wrapped in text' => array(
                'html' => 'xx<img src="http://example.com/example.jpg">xx',
                'expected'  => 'xxxx',
            ),
            'With alt tag' => array(
                'html' => '<img src="http://example.com/example.jpg" alt="An example image">',
                'expected'  => '[An example image]',
            ),
            'With alt, and title tags' => array(
                'html' => '<img src="http://example.com/example.jpg" alt="An example image" title="Should be ignored">',
                'expected'  => '[An example image]',
            ),
            'With alt tag, wrapped in text' => array(
                'html' => 'xx<img src="http://example.com/example.jpg" alt="An example image">xx',
                'expected'  => 'xx[An example image]xx',
            ),
            'With italics' => array(
                'html' => '<img src="shrek.jpg" alt="the ogrelord" /> Blah <i>blah</i> blah',
                'expected' => '[the ogrelord] Blah _blah_ blah'
            )
        );
    }

    /**
     * @dataProvider testImageDataProvider
     */
    public function testImages($html, $expected)
    {
        $html2text = new Html2Text($html);
        $output = $html2text->getText();

        $this->assertEquals($expected, $output);
    }
}
