# PHPAntiSpam

[![Build Status](https://travis-ci.org/bgruszka/PHPAntiSpam.svg?branch=master)](https://travis-ci.org/bgruszka/PHPAntiSpam)
[![Code Climate](https://codeclimate.com/github/bgruszka/PHPAntiSpam/badges/gpa.svg)](https://codeclimate.com/github/bgruszka/PHPAntiSpam)

## PHPAntiSpam is a library that recognize if documents / messages / texts are spam or not. The library use statistical analysis.

## Explanation in 4 steps:
* Create tokenizer
* Create corpus (with lexemes) from historical messages
* Choose method to use in classification
* Classify message

## Implemented methods:
* Paul Graham method
* Brian Burton method
* Robinson Geometric Mean Test method
* Fisher-Robinson's Inverse Chi-Square Test method

## Installation
`composer require bgruszka/phpantispam "^0.2"`

## Examples

```php

<?php

// First add autoloader and all necessary classes
require_once 'vendor/autoload.php';

use PHPAntiSpam\Corpus\ArrayCorpus;
use PHPAntiSpam\Classifier;
use PHPAntiSpam\Tokenizer\WhitespaceTokenizer;

// Let's decleare our example training set
$messages = [
    ['category' => 'spam', 'content' => 'this is spam'],
    ['category' => 'nospam', 'content' => 'this is'],
];

// As tokenizer we can use the simplest one - WhitespaceTokenizer (but of course you can also use RegexpTokenizer
// or create new one)
$tokenizer = new WhitespaceTokenizer();

// Let's define our corpus - collection of text documents
$corpus = new ArrayCorpus($messages, $tokenizer);

// For classifying text we can use different methods

// ------------------------------------------------------------------------------------
// Graham method

$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\GrahamMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Graham method:' . PHP_EOL;
echo sprintf('Spam probability: %s', $spamProbability) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability < 0.9 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// ------------------------------------------------------------------------------------
// Burton method

$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\BurtonMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Burton method:' . PHP_EOL;
echo sprintf('Spam probability: %s', $spamProbability) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability < 0.9 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// ------------------------------------------------------------------------------------
// Robinson Geometric Mean Test Method

$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\RobinsonGeometricMeanTestMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Robinson Geometric Mean Test method:' . PHP_EOL;
echo sprintf(
    'Spam probability: [spamminess: %s; hamminess: %s; combined: %s]', 
    $spamProbability['spamminess'], 
    $spamProbability['hamminess'], 
    $spamProbability['combined']
) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability['combined'] <= 0.55 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// ------------------------------------------------------------------------------------
// Fisher-Robinson Inverse Chi Square Method

$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\FisherRobinsonInverseChiSquareMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Fisher-Robinson Inverse Chi Square method:' . PHP_EOL;
echo sprintf(
    'Spam probability: [spamminess: %s; hamminess: %s; combined: %s]', 
    $spamProbability['spamminess'], 
    $spamProbability['hamminess'], 
    $spamProbability['combined']
) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability['combined'] <= 0.55 ? 'NO' : 'YES') . PHP_EOL;
```