<?php

declare(strict_types=1);

return new PhpCsFixer\Config()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'modernize_strpos' => true,
        'heredoc_indentation' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'declare_strict_types' => true,
        'void_return' => true,
        'multiline_promoted_properties' => ['minimum_number_of_parameters' => 1],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
    ])
    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder(
        new PhpCsFixer\Finder()
            ->in([
                __DIR__.'/src',
                __DIR__.'/tests',
            ])
            ->append(glob(__DIR__.'/*.php'))
            ->append([__FILE__])
            ->exclude(['vendor', 'var'])
    )
;
