<?php

declare(strict_types=1);

namespace App\Shared\Database;

final class SqlScript
{
    /** @return list<string> */
    public static function statements(string $sql): array
    {
        $delimiter = ';';
        $buffer = '';
        $quote = null;
        $statements = [];
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $character = $sql[$i];
            if ($quote !== null) {
                $buffer .= $character;
                if ($character === '\\' && $i + 1 < $length) {
                    $buffer .= $sql[++$i];
                } elseif ($character === $quote) {
                    if (($sql[$i + 1] ?? '') === $quote) {
                        $buffer .= $sql[++$i];
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }
            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $buffer .= $character;
                continue;
            }
            if (substr($sql, $i, 2) === '--' || $character === '#') {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $length : $end;
                $buffer .= "\n";
                continue;
            }
            if (substr($sql, $i, 2) === '/*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                $buffer .= ' ';
                continue;
            }
            if (trim($buffer) === '' && preg_match('/\GDELIMITER\s+(\S+)[^\r\n]*/Ai', $sql, $match, 0, $i)) {
                $delimiter = $match[1];
                $i += strlen($match[0]) - 1;
                continue;
            }
            if (substr($sql, $i, strlen($delimiter)) === $delimiter) {
                if (trim($buffer) !== '') {
                    $statements[] = trim($buffer);
                }
                $buffer = '';
                $i += strlen($delimiter) - 1;
                continue;
            }
            $buffer .= $character;
        }
        if ($quote !== null) {
            throw new \InvalidArgumentException('Literal SQL não terminado.');
        }
        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }
        return $statements;
    }
}
