<?php

namespace PHPAntiSpam\DecisionMatrix;

interface DecisionMatrixInterface
{
    const NEUTRAL = 0.5;

    public function getMostImportantLexemes();
    public function getWords();
}