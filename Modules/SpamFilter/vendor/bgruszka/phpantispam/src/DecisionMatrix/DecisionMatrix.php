<?php

namespace PHPAntiSpam\DecisionMatrix;

use PHPAntiSpam\Corpus\CorpusInterface;

abstract class DecisionMatrix implements DecisionMatrixInterface
{
    protected $matrix = [];
    protected $words = [];

    /** @var  \PHPAntiSpam\Corpus\CorpusInterface */
    protected $corpus;

    public function __construct(array $words, CorpusInterface $corpus, $window = null)
    {
        $this->words = $words;
        $this->corpus = $corpus;
        $this->window = $window;
    }

    /**
     * Add word in decision matrix
     *
     * @param $usefulnessArray
     * @param $word
     * @param $probability
     */
    protected function addWord(&$usefulnessArray, $word, $probability)
    {
        if(!isset($this->matrix[$word])) {
            $this->matrix[$word] = [];
        }

        $usefulness = abs(self::NEUTRAL - $probability);

        $this->matrix[$word]['probability'] = $probability;
        $this->matrix[$word]['usefulness'] = $usefulness;
        $usefulnessArray[$word] = $usefulness;
    }

    /**
     * Add double word in decision matrix
     *
     * @param array $usefulnessArray
     * @param string $word
     * @param float $probability
     */
    protected function addDoubleWord(array &$usefulnessArray, $word, $probability)
    {
        for ($i = 1; $i <= 2; $i++) {
            $word = $word . $i;
            $this->addWord($usefulnessArray, $word, $probability);
        }
    }

    public function getWords()
    {
        return $this->words;
    }
}