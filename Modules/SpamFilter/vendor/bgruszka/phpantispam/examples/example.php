<?php 

require_once 'vendor/autoload.php';

use PHPAntiSpam\Corpus\ArrayCorpus;
use PHPAntiSpam\Classifier;
use PHPAntiSpam\Tokenizer\WhitespaceTokenizer;

$messages = [
    ['category' => 'spam', 'content' => 'this is spam'],
    ['category' => 'nospam', 'content' => 'this is'],
];

$tokenizer = new WhitespaceTokenizer();
$corpus = new ArrayCorpus($messages, $tokenizer);

// Graham method
$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\GrahamMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Graham method:' . PHP_EOL;
echo sprintf('Spam probability: %s', $spamProbability) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability < 0.9 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// Burton method
$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\BurtonMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Burton method:' . PHP_EOL;
echo sprintf('Spam probability: %s', $spamProbability) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability < 0.9 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// Robinson Geometric Mean Test Method
$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\RobinsonGeometricMeanTestMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Robinson Geometric Mean Test method:' . PHP_EOL;
echo sprintf('Spam probability: [spamminess: %s; hamminess: %s; combined: %s]', $spamProbability['spamminess'], $spamProbability['hamminess'], $spamProbability['combined']) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability['combined'] <= 0.55 ? 'NO' : 'YES') . PHP_EOL . PHP_EOL;

// Fisher-Robinson Inverse Chi Square Method
$classifier = new Classifier($corpus);
$classifier->setMethod(new \PHPAntiSpam\Method\FisherRobinsonInverseChiSquareMethod($corpus));

$spamProbability = $classifier->isSpam('This is spam');

echo 'With Fisher-Robinson Inverse Chi Square method:' . PHP_EOL;
echo sprintf('Spam probability: [spamminess: %s; hamminess: %s; combined: %s]', $spamProbability['spamminess'], $spamProbability['hamminess'], $spamProbability['combined']) . PHP_EOL;
echo sprintf('Is spam: %s', $spamProbability['combined'] <= 0.55 ? 'NO' : 'YES') . PHP_EOL;