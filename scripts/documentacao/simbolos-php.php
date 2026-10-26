<?php

declare(strict_types=1);

/**
 * Lista as classes, enums, interfaces e funções nomeadas dos arquivos PHP recebidos como argumentos, pelo tokenizer do
 * próprio PHP (não executa nada). Saída: uma linha JSON por símbolo, lida por inventario.mjs.
 *
 * Uso: php scripts/documentacao/simbolos-php.php src/Config.php src/Services.php …
 */
foreach (array_slice($argv, 1) as $file) {
    $tokens = token_get_all((string) file_get_contents($file));
    $class = null;
    $classDepth = -1;
    $depth = 0;
    $modifiers = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (is_string($t)) {
            if ($t === '{') {
                $depth++;
                $modifiers = [];
            } elseif ($t === '}') {
                $modifiers = [];
                $depth--;
                if ($depth === $classDepth) {
                    $class = null;
                    $classDepth = -1;
                }
            } elseif ($t === ';' || $t === '(' || $t === ')' || $t === ',') {
                // Também os parâmetros promovidos do construtor ("private readonly …") não contam para o método seguinte.
                $modifiers = [];
            }
            continue;
        }
        [$id, $text, $line] = $t;
        if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
            $depth++;
            continue;
        }
        if (in_array($id, [T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_ABSTRACT, T_FINAL, T_READONLY], true)) {
            $modifiers[] = strtolower($text);
            continue;
        }
        if (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            // "Foo::class" e classes anônimas (new class) não declaram um nome.
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], [T_DOUBLE_COLON, T_NEW], true)) {
                continue;
            }
            $j = $i + 1;
            while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $class = $tokens[$j][1];
                $classDepth = $depth;
                echo json_encode(['file' => $file, 'kind' => strtolower(token_name($id) === 'T_ENUM' ? 'enum' : substr(token_name($id), 2)), 'name' => $class, 'line' => $line, 'modifiers' => $modifiers], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
            }
            $modifiers = [];
            continue;
        }
        if ($id === T_FUNCTION) {
            $j = $i + 1;
            while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            if ($tokens[$j] === '&') {
                $j++;
            }
            while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                $modifiers = []; // função anônima: documentada no fluxo que a contém
                continue;
            }
            $name = $tokens[$j][1];
            // Assinatura até a abertura do corpo ou o ";" (métodos abstratos/interface).
            $signature = '';
            $parens = 0;
            for ($k = $j; $k < $count; $k++) {
                $piece = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                if ($piece === '(') {
                    $parens++;
                } elseif ($piece === ')') {
                    $parens--;
                }
                if ($parens === 0 && ($piece === '{' || $piece === ';')) {
                    break;
                }
                $signature .= $piece;
            }
            $visibility = array_values(array_intersect($modifiers, ['public', 'private', 'protected']))[0] ?? ($class === null ? 'função' : 'public');
            echo json_encode([
                'file' => $file,
                'kind' => $class === null ? 'function' : 'method',
                'class' => $class,
                'name' => $class === null ? $name : "{$class}::{$name}",
                'short' => $name,
                'visibility' => $visibility,
                'static' => in_array('static', $modifiers, true),
                'signature' => trim((string) preg_replace('/\s+/', ' ', $signature)),
                'line' => $tokens[$j][2],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
            $modifiers = [];
        }
    }
}
