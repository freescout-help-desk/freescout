<?php

namespace PHPAntiSpam\Tokenizer;

interface TokenizerInterface
{
    public function tokenize($text);
}