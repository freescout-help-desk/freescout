<?php

namespace tokenizer;

use PHPAntiSpam\Tokenizer\WhitespaceTokenizer;

class WhitespaceTokenizerTest extends \PHPUnit_Framework_TestCase
{
    public function testTokenize()
    {
        $text = '  test example text  ';
        $tokenizer = new WhitespaceTokenizer();

        $this->assertEquals(['test', 'example', 'text'], $tokenizer->tokenize($text));
    }
}
