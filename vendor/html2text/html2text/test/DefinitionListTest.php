<?php

namespace Html2Text;

class DefinitionListTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinitionList()
    {
        $html =<<< EOT
<dl>
  <dt>Definition Term:</dt>
  <dd>Definition Description<dd>
</dl>
EOT;
        $expected =<<<EOT
 	* Definition Term: Definition Description 


EOT;

        $html2text = new Html2Text($html);
        $output = $html2text->getText();

        $this->assertEquals($expected, $output);
    }
}
