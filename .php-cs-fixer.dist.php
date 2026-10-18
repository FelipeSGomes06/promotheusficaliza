<?php

declare(strict_types=1);

// Estilo: PER Coding Style (sucessor do PSR-12), com tipos estritos declarados.
$finder = (new PhpCsFixer\Finder())->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/public', __DIR__ . '/scripts'])->append([__DIR__ . '/bin/fiscaliza']);

return (new PhpCsFixer\Config())
    ->setRules(['@PER-CS' => true, 'declare_strict_types' => true, 'no_unused_imports' => true])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
