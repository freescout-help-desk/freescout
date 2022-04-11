<?php

namespace PHPAntiSpam\Method;

use PHPAntiSpam\Corpus\CorpusInterface;
use PHPAntiSpam\DecisionMatrix\DecisionMatrixInterface;
use PHPAntiSpam\Math;

abstract class Method extends Math implements MethodInterface
{
    protected $bias = true;

    protected $text;

    /** @var DecisionMatrixInterface */
    protected $decisionMatrix;

    /** @var  CorpusInterface */
    protected $corpus;

    public function __construct(CorpusInterface $corpus)
    {
        $this->corpus = $corpus;
    }

    public function setBias($bias)
    {
        $this->bias = $bias;
    }


    /**
     * Calculate lexeme value with Paul Graham method.
     *
     * @link http://www.paulgraham.com/spam.html
     *
     * @param $wordSpamCount
     * @param $wordNoSpamCount
     * @param $spamMessagesCount
     * @param $noSpamMessagesCount
     * @return float
     */
    protected function calculateGrahamWordValue(
        $wordSpamCount,
        $wordNoSpamCount,
        $spamMessagesCount,
        $noSpamMessagesCount
    ) {
        if ($spamMessagesCount === 0 || $noSpamMessagesCount === 0) {
            return DecisionMatrixInterface::NEUTRAL;
        }

        $multiplier = 1;

        if ($this->bias) {
            $multiplier = 2;
        }

        $value = ($wordSpamCount / $spamMessagesCount) / (($wordSpamCount / $spamMessagesCount) + (($multiplier * $wordNoSpamCount) / $noSpamMessagesCount));

        return $value;
    }

    protected function setLexemesProbability()
    {
        $lexemes = $this->corpus->getLexemes($this->decisionMatrix->getWords());

        foreach ($lexemes as $word => $value) {
            $probability = $this->calculateGrahamWordValue(
                $value['spam'],
                $value['nospam'],
                $this->corpus->messagesCount['spam'],
                $this->corpus->messagesCount['nospam']
            );

            $probability = $this->calculateRobinsonWordValue($value['spam'] + $value['nospam'], $probability);

            $lexemes[$word]['probability'] = $probability;

            $this->corpus->setLexemes($lexemes);
        }
    }

    protected function getWordsFromText($text)
    {
        $words = array_map(
            function ($word) {
                return strtolower($word);
            },
            $this->corpus->getTokenizer()->tokenize($text)
        );

        return $words;
    }

    abstract protected function setDecisionMatrix($text);
}