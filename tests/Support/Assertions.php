<?php
declare(strict_types=1);

namespace SGITests\Support;

class Assertions
{
    private static int $total = 0;
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $failures = [];

    public static function reset(): void
    {
        self::$total = 0;
        self::$passed = 0;
        self::$failed = 0;
        self::$failures = [];
    }

    public static function assert(string $testName, bool $condition, string $details = ''): void
    {
        self::$total++;
        if ($condition) {
            self::$passed++;
            echo "    \033[32m[PASS]\033[0m $testName\n";
        } else {
            self::$failed++;
            $msg = "$testName" . ($details !== '' ? " -> $details" : '');
            self::$failures[] = $msg;
            echo "    \033[31m[FAIL]\033[0m $msg\n";
        }
    }

    public static function assertStatus(string $testName, array $response, int $expectedStatus): void
    {
        $code = (int) ($response['code'] ?? 0);
        $ok = ($code === $expectedStatus);
        $details = "Esperado HTTP $expectedStatus, obtido $code. Resposta: " . mb_substr((string)($response['body'] ?? ''), 0, 200);
        self::assert($testName, $ok, $details);
    }

    public static function assertJsonSuccess(string $testName, array $response): void
    {
        $json = $response['json'] ?? [];
        $isOk = ($response['code'] >= 200 && $response['code'] < 300) &&
                (($json['success'] ?? false) === true || ($json['status'] ?? '') === 'sucesso' || ($json['status'] ?? '') === 'success');
        $details = "Código HTTP: {$response['code']}. Resposta: " . mb_substr((string)($response['body'] ?? ''), 0, 200);
        self::assert($testName, $isOk, $details);
    }

    public static function assertJsonCount(string $testName, array $response, string $key, int $minCount): void
    {
        $json = $response['json'] ?? [];
        $list = is_array($json) && isset($json[$key]) ? $json[$key] : (is_array($json) ? $json : []);
        $count = is_array($list) ? count($list) : 0;
        $ok = ($count >= $minCount);
        $details = "Esperado >= $minCount itens, encontrado $count em '{$key}'";
        self::assert($testName, $ok, $details);
    }

    public static function getStats(): array
    {
        return [
            'total' => self::$total,
            'passed' => self::$passed,
            'failed' => self::$failed,
            'failures' => self::$failures
        ];
    }
}
