<?php

namespace PHPAntiSpam\Tokenizer;

class WhitespaceTokenizer extends RegexpTokenizer
{
    public function __construct()
    {
        parent::__construct('/\s/');
    }
}