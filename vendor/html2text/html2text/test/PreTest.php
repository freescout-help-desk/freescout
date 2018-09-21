<?php

namespace Html2Text;

class PreTest extends \PHPUnit_Framework_TestCase
{
    public function preDataProvider()
    {
        return array(
            'Basic pre' => array(
                'html' => <<<EOT
<p>Before</p>
<pre>

Foo bar baz


HTML symbols &amp;

</pre>
<p>After</p>
EOT
                ,
                'expected' => <<<EOT
Before

Foo bar baz

HTML symbols &

After

EOT
                ,
            ),
            'br in pre' => array(
                'html' => <<<EOT
<pre>
some<br />  indented<br />  text<br />    on<br />    several<br />  lines<br />
</pre>
EOT
                ,
                'expected' => <<<EOT
some
  indented
  text
    on
    several
  lines


EOT
                ,
            ),
        );
    }

    /**
     * @dataProvider preDataProvider
     */
    public function testPre($html, $expected)
    {
        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }
}
