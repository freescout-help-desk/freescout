<?php

namespace PHPAntiSpam\DecisionMatrix;

/**
 * Create decision matrix used by Fisher-Robinson chi-square method.
 * The chi-square algorithm's decision matrix is different from that
 * of Bayesian combination in that it includes all tokens within a
 * specific range of probability (usually 0.0 through 0.1 and 0.9
 * through 1.0) and doesn't require sorting.
 */
class FisherRobinsonDecisionMatrix extends DecisionMatrix
{
    public function getMostImportantLexemes()
    {
        $decisionMatrix = array();
        $processedWords = array();

        $lexemes = $this->corpus->getLexemes($this->words);

        foreach ($this->words as $word) {
            $word = trim($word);
            if (strlen($word) > 0 && !in_array($word, $processedWords)) {
                if (isset($lexemes[$word])) {
                    $isInRanges = $lexemes[$word]['probability'] <= 0.1 || $lexemes[$word]['probability'] >= 0.9;
                    if ($isInRanges) {
                        $decisionMatrix[$word]['probability'] = $lexemes[$word]['probability'];
                        $processedWords[] = $word;
                    }
                }
            }
        }

        return $decisionMatrix;
    }
}