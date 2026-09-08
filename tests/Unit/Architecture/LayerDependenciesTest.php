<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class LayerDependenciesTest extends TestCase
{
    public function testBusinessLayersDoNotAccessHttpOrDatabaseDirectly(): void
    {
        $root = dirname(__DIR__, 3) . '/src/Modules';
        $count = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!$file->isFile() || $file->getExtension() !== 'php' || !preg_match('~/(Domain|Application)/~', $path)) {
                continue;
            }
            $count++;
            $code = (string) file_get_contents($path);
            foreach (self::imports($code) as $import) {
                self::assertDoesNotMatchRegularExpression(
                    '~^(?:App\\\\Shared\\\\Http|App\\\\Modules\\\\[^\\\\]+\\\\(?:Infrastructure|Presentation|Http))\\\\~',
                    $import,
                    $path,
                );
            }

            $tokens = token_get_all($code);
            foreach ($tokens as $index => $token) {
                if (!is_array($token)) {
                    continue;
                }

                [$type, $text] = $token;
                if ($type === T_VARIABLE && in_array($text, ['$_GET', '$_POST', '$_REQUEST', '$_SESSION', '$_SERVER'], true)) {
                    self::fail($path . ' acessa superglobal HTTP/sessão: ' . $text);
                }
                if ($type === T_STRING && in_array($text, ['mysqli', 'PDO', 'ConnectionFactory', 'header', 'http_response_code', 'session_start'], true)) {
                    self::fail($path . ' referencia API HTTP/banco proibida: ' . $text);
                }
                if ($type === T_CONSTANT_ENCAPSED_STRING && preg_match('/\b(?:SELECT|INSERT\\s+INTO|UPDATE\\s+\\w+\\s+SET|DELETE\\s+FROM)\\b/i', $text) === 1) {
                    self::fail($path . ' contém consulta SQL em camada de negócio.');
                }
                if ($type !== T_OBJECT_OPERATOR) {
                    continue;
                }

                $next = self::nextToken($tokens, $index + 1);
                if (is_array($next) && in_array($next[1], ['query', 'prepare', 'execute', 'begin_transaction', 'commit', 'rollback'], true)) {
                    self::fail($path . ' executa consulta/transação diretamente.');
                }
            }
        }
        self::assertGreaterThan(0, $count, 'Nenhuma regra de negócio foi verificada.');
    }

    /** @return list<string> */
    private static function imports(string $source): array
    {
        $tokens = token_get_all($source);
        $imports = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_USE) {
                continue;
            }
            $next = self::nextToken($tokens, $index + 1);
            if (is_array($next) && $next[0] === T_VARIABLE) {
                continue;
            }

            $statement = '';
            for ($cursor = $index + 1; isset($tokens[$cursor]); $cursor++) {
                $part = $tokens[$cursor];
                $statement .= is_array($part) ? $part[1] : $part;
                if ($part === ';') {
                    break;
                }
            }
            $statement = trim($statement, " \t\r\n;");
            $statement = preg_replace('/\s+as\s+[A-Za-z_][A-Za-z0-9_]*$/i', '', $statement) ?? $statement;
            if ($statement !== '' && !str_starts_with($statement, 'function ')) {
                $imports[] = $statement;
            }
        }

        return $imports;
    }

    /** @param list<array<int, mixed>|string> $tokens */
    private static function nextToken(array $tokens, int $start): array|string|null
    {
        for ($index = $start; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $token;
        }

        return null;
    }
}
