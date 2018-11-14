<?php

namespace Html2Text;

class ListTest extends \PHPUnit_Framework_TestCase
{
    public function testList()
    {
        $html =<<<'EOT'
<ul>
  <li>Item 1</li>
  <li>Item 2</li>
  <li>Item 3</li>
</ul>
EOT;

        $expected =<<<'EOT'
 	* Item 1
 	* Item 2
 	* Item 3


EOT;

        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }

    public function testOrderedList()
    {
        $html =<<<'EOT'
<ol>
  <li>Item 1</li>
  <li>Item 2</li>
  <li>Item 3</li>
</ol>
EOT;

        $expected =<<<'EOT'
 	* Item 1
 	* Item 2
 	* Item 3


EOT;

        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }
}
