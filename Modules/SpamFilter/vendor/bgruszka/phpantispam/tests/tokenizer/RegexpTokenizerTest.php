<?php

namespace tokenizer;

use PHPAntiSpam\Tokenizer\RegexpTokenizer;

class RegexpTokenizerTest extends \PHPUnit_Framework_TestCase
{
    public function testTokenize()
    {
        $text = 'test example text';
        $tokenizer = new RegexpTokenizer('/\s/');

        $this->assertEquals(['test', 'example', 'text'], $tokenizer->tokenize($text));
    }
}
