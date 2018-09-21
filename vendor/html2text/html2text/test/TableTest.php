<?php

namespace Html2Text;

class TableTest extends \PHPUnit_Framework_TestCase
{
    public function testTable()
    {
        $html =<<<'EOT'
<table>
  <tr>
    <th>Heading 1</th>
    <td>Data 1</td>
  </tr>
  <tr>
    <th>Heading 2</th>
    <td>Data 2</td>
  </tr>
</table>
EOT;

        $expected =<<<'EOT'
 		HEADING 1
 		Data 1

 		HEADING 2
 		Data 2


EOT;

        $html2text = new Html2Text($html);
        $this->assertEquals($expected, $html2text->getText());
    }
}
