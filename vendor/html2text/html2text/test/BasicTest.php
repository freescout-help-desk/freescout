<?php

namespace Html2Text;

class BasicTest extends \PHPUnit_Framework_TestCase
{
    public function basicDataProvider() {
        return array(
            'Readme usage' => array(
                'html'      => 'Hello, &quot;<b>world</b>&quot;',
                'expected'  => 'Hello, "WORLD"',
            ),
            'No stripslashes on HTML content' => array(
                // HTML content does not escape slashes, therefore nor should we.
                'html'      => 'Hello, \"<b>world</b>\"',
                'expected'  => 'Hello, \"WORLD\"',
            ),
            'Zero is not empty' => array(
                'html'      => '0',
                'expected'  => '0',
            ),
            'Paragraph with whitespace wrapping it' => array(
                'html'      => 'Foo <p>Bar</p> Baz',
                'expected'  => "Foo\nBar\nBaz",
            ),
            'Paragraph text with linebreak flat' => array(
                'html'      => "<p>Foo<br/>Bar</p>",
                'expected'  => <<<EOT
Foo
Bar

EOT
            ),
            'Paragraph text with linebreak formatted with newline' => array(
                'html'      => <<<EOT
<p>
    Foo<br/>
    Bar
</p>
EOT
                ,
                'expected'  => <<<EOT
Foo
Bar

EOT
            ),
            'Paragraph text with linebreak formatted whth newline, but without whitespace' => array(
                'html'      => <<<EOT
<p>Foo<br/>
Bar</p>
EOT
                ,
                'expected'  => <<<EOT
Foo
Bar

EOT
            ),
            'Paragraph text with linebreak formatted with indentation' => array(
                'html'      => <<<EOT
<p>
    Foo<br/>Bar
</p>
EOT
                ,
                'expected'  => <<<EOT
Foo
Bar

EOT
            ),
        );
    }

    /**
     * @dataProvider basicDataProvider
     */
    public function testBasic($html, $expected)
    {
        $html2Text = new Html2Text($html);
        $this->assertEquals($expected, $html2Text->getText());
        $this->assertEquals($html, $html2Text->getHtml());
    }
}
