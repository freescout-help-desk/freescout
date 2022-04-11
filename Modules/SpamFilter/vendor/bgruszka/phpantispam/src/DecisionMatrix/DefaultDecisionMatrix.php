<?php

namespace PHPAntiSpam\DecisionMatrix;

class DefaultDecisionMatrix extends DecisionMatrix
{
    private $doubleWords = false;

    public function getMostImportantLexemes()
    {
        $usefulnessArray = array();
        $processedWords = array();

        foreach ($this->words as $key => $word) {
            $this->words[$key] = trim($word);
        }

        $lexemes = $this->corpus->getLexemes($this->words);

        foreach ($this->words as $word) {
            if (mb_strlen($word, 'utf-8') > 0 && !in_array($word, $processedWords)) {

                // first occurrence of lexeme (unit lexeme)
                if (!isset($lexemes[$word])) {
                    // set default / neutral lexeme probability
                    $probability = self::NEUTRAL;
                } else {
                    $probability = $lexemes[$word]['probability'];
                }

                if($this->doubleWords) {
                    $this->addDoubleWord($usefulnessArray, $word, $probability);
                } else {
                    $this->addWord($usefulnessArray, $word, $probability);
                }

                $processedWords[] = $word;
            }
        }

        // sort by usefulness
        array_multisort($usefulnessArray, SORT_DESC, $this->matrix);
        $mostImportantLexemes = array_slice($this->matrix, 0, $this->window);

        return $mostImportantLexemes;
    }

    public function setDoubleWords($value)
    {
        $this->doubleWords = $value;
    }
}