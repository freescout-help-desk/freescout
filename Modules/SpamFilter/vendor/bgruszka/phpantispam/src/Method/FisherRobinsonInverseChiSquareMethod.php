<?php

namespace PHPAntiSpam\Method;

use PHPAntiSpam\DecisionMatrix\FisherRobinsonDecisionMatrix;

class FisherRobinsonInverseChiSquareMethod extends Method
{
    public function calculate($text)
    {
        $this->setDecisionMatrix($text);
        $this->setLexemesProbability();

        return $this->fisherRobinsonsInverseChiSquareTest($this->decisionMatrix->getMostImportantLexemes());
    }

    protected function setDecisionMatrix($text)
    {
        $this->decisionMatrix = new FisherRobinsonDecisionMatrix(
            $this->getWordsFromText($text),
            $this->corpus
        );
    }
}