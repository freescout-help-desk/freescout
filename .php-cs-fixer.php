<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/Modules')
    ->in(__DIR__ . '/database')
    ->name('*.php')
    ->exclude(['vendor', 'storage', 'bootstrap/cache']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => false,
        'ordered_imports' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);

