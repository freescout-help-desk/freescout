<?php

namespace PHPAntiSpam;

abstract class Math
{
    /**
     * Calculate bayes probability
     *
     * @param array $lexemes
     *
     * @return float
     */
    protected function bayes(array $lexemes)
    {
        $numerator = 1;
        $denominator = 1;
        foreach ($lexemes as $lexeme) {
            $numerator *= $lexeme['probability'];
            $denominator *= 1 - $lexeme['probability'];
        }

        $result = $numerator / ($numerator + $denominator);

        return $result;
    }

    /**
     * Calculate lexeme value with Gary Robinson method
     *
     * @param int $wordOccurrences Number of occurrences in corpus (in spam and nospam)
     * @param float $graham Word value calculated by Graham method
     *
     * @return float
     */
    public function calculateRobinsonWordValue($wordOccurrences, $wordGrahamValue)
    {
        $s = 1;
        $x = 0.5;

        $value = ($s * $x + $wordOccurrences * $wordGrahamValue) / ($s + $wordOccurrences);

        return $value;
    }

    /**
     * Ribinson's geometric mean test measures both the "spamminess" and "hamminess" of
     * the data in the decision matrix and also provides more granular results ranging
     * between 0 percen and 100 percent. Generally, a result of round 55 percent or
     * higher using Robinson's algorithm is an indicator of spam
     *
     * @param array $lexemes
     *
     * @return array
     */
    public function robinsonGeometricMeanTest(array $lexemes)
    {
        $spamminess = 1;
        $hamminess = 1;

        foreach ($lexemes as $lexeme) {
            $spamminess *= (1 - $lexeme['probability']);
            $hamminess *= $lexeme['probability'];
        }

        $spamminess = 1 - pow($spamminess, 1 / count($lexemes));
        $hamminess = 1 - pow($hamminess, 1 / count($lexemes));
        $combined = (1 + (($spamminess - $hamminess) / ($spamminess + $hamminess))) / 2;

        return array('spamminess' => $spamminess, 'hamminess' => $hamminess, 'combined' => $combined);
    }

    /**
     * The inverse chi-square statistic
     *
     * @param float $x
     * @param int $v
     *
     * @return float
     */
    private function chi2Q($x, $v)
    {
        $m = $x / 2;
        $s = exp(-$m);
        $t = $s;

        for ($i = 1; $i < ($v / 2); $i++) {
            $t *= $m / $i;
            $s += $t;
        }

        return ($s < 1) ? $s : 1;
    }

    /**
     * Calculate probability used Fisher's chi-square distribution of combining
     * individual probabilities. The chi-square algorithm provides the added
     * benefit of being very sensitive to uncertainty. It produces granular
     * results similar to Robinson's geometric mean test, in which the result
     * of calculation may fall within midrange of values to indicate a level
     * of uncertainty.
     * @link http://www.linuxjournal.com/article/6467
     *
     * @param array $lexemes
     *
     * @return array
     */
    public function fisherRobinsonsInverseChiSquareTest(array $lexemes)
    {
        $wordsProductProbability = 1;
        $wordsProductProbabilitySubstraction = 1;

        foreach ($lexemes as $lexeme) {
            $wordsProductProbability *= $lexeme['probability'];
            $wordsProductProbabilitySubstraction *= 1 - $lexeme['probability'];
        }

        $hamminess = $this->chi2Q(-2 * log($wordsProductProbability), 2 * count($lexemes));
        $spamminess = $this->chi2Q(-2 * log($wordsProductProbabilitySubstraction), 2 * count($lexemes));

        $combined = (1 + $hamminess - $spamminess) / 2;

        return array('spamminess' => $spamminess, 'hamminess' => $hamminess, 'combined' => $combined);
    }
}