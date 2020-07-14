<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => [
        'src/',
        'vendor/dnoegel/php-xdg-base-dir/src/',
        'vendor/doctrine/instantiator/src/',
        'vendor/hoa/console/',
        'vendor/jakub-onderka/php-console-color/src/',
        'vendor/jakub-onderka/php-console-highlighter/src/',
        'vendor/nikic/php-parser/lib/',
        'vendor/phpdocumentor/reflection-docblock/',
        'vendor/symfony/console/',
        'vendor/symfony/filesystem/',
        'vendor/symfony/finder/',
        'vendor/symfony/var-dumper/',
    ],

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to both the `directory_list`
    //       and `exclude_analysis_directory_list` arrays.
    "exclude_analysis_directory_list" => [
        'vendor/'
    ],
];
