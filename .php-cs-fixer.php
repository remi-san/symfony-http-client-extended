<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true);

return $config->setRules([
        '@PHP82Migration'          => true,
        '@Symfony'                 => true,
        '@Symfony:risky'           => true,
        'array_syntax'             => ['syntax' => 'short'],
        'combine_nested_dirname'   => true,
        'yoda_style'               => ['equal' => false, 'identical' => false, 'always_move_variable' => false, 'less_and_greater' => false],
        'binary_operator_spaces'   => ['default' => 'align_single_space'],
        'php_unit_method_casing'   => ['case' => 'snake_case'],
        'php_unit_test_annotation' => ['style' => 'annotation'],
        'declare_strict_types'     => true,
    ])
    ->setFinder($finder)
;
