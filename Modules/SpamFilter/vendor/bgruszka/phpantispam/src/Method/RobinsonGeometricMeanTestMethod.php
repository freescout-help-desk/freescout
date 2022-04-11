<?php

namespace PHPAntiSpam\Method;

use PHPAntiSpam\DecisionMatrix\DefaultDecisionMatrix;

class RobinsonGeometricMeanTestMethod extends Method
{
    const WINDOW_SIZE = 15;

    public function calculate($text)
    {
        $this->setDecisionMatrix($text);
        $this->setLexemesProbability();

        return $this->robinsonGeometricMeanTest($this->decisionMatrix->getMostImportantLexemes());
    }

    protected function setDecisionMatrix($text)
    {
        $this->decisionMatrix = new DefaultDecisionMatrix(
            $this->getWordsFromText($text),
            $this->corpus,
            self::WINDOW_SIZE
        );
    }
}