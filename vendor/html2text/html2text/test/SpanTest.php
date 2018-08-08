<?php

namespace Html2Text;

class SpanTest extends \PHPUnit_Framework_TestCase
{

    public function testIgnoreSpans()
    {
    	$html =<<< EOT
Outside<span class="_html2text_ignore">Inside</span>
EOT;
        $expected =<<<EOT
Outside
EOT;

        $html2text = new Html2Text($html);
        $output = $html2text->getText();

        $this->assertEquals($expected, $output);
    }
}
