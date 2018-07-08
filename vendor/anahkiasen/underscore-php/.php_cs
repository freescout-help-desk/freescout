<?php
use Symfony\CS\Config\Config;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;
use Symfony\CS\FixerInterface;

$finder = DefaultFinder::create()->in(['config', 'src', 'tests']);

return Config::create()
             ->level(FixerInterface::SYMFONY_LEVEL)
             ->fixers([
                 '-yoda_conditions',
                 'ereg_to_preg',
                 'multiline_spaces_before_semicolon',
                 'no_blank_lines_before_namespace',
                 'ordered_use',
                 'phpdoc_order',
                 'phpdoc_var_to_type',
                 'short_array_syntax',
                 'strict',
                 'strict_param',
             ])
             ->setUsingCache(true)
             ->finder($finder);
