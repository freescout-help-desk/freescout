<?php

namespace PHPAntiSpam\Corpus;


interface CorpusInterface {
    public function getTokenizer();
    public function getLexemes(array $words);
    public function setLexemes(array $lexemes);
}