<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Domain;

use InvalidArgumentException;

final class OcorrenciaDescricao
{
    /**
     * Separate the optional legacy prefix from user text. References are only
     * accepted at the beginning and in the canonical JOGO, TURMA order.
     *
     * @return array{gameId:int,classId:int,text:string}
     */
    public static function parseSubmitted(string $description, bool $allowLeadingReferences = false): array
    {
        $description = trim($description);
        $hasMarker = self::hasMarker($description);
        if (!$allowLeadingReferences) {
            if ($hasMarker) {
                throw new InvalidArgumentException('Marcadores de jogo e turma não podem ser enviados no texto livre.');
            }
            return ['gameId' => 0, 'classId' => 0, 'text' => $description];
        }

        preg_match('/^(?:\[JOGO:(-?\d+)\])?(?:\[TURMA:(\d+)\])?/', $description, $matches);
        $prefix = (string) ($matches[0] ?? '');
        $body = substr($description, strlen($prefix));
        if ($hasMarker && ($prefix === '' || self::hasMarker($body))) {
            throw new InvalidArgumentException('Os marcadores devem formar um único prefixo de referência.');
        }

        $gameId = isset($matches[1]) ? (int) $matches[1] : 0;
        $classId = isset($matches[2]) ? (int) $matches[2] : 0;
        if (($gameId === 0 && str_starts_with($prefix, '[JOGO:')) || ($classId === 0 && str_contains($prefix, '[TURMA:'))) {
            throw new InvalidArgumentException('As referências da ocorrência devem usar identificadores válidos.');
        }

        return ['gameId' => $gameId, 'classId' => $classId, 'text' => trim($body)];
    }

    /** @return array{gameId:int,classId:int} */
    public static function fromStored(string $description): array
    {
        $gameId = 0;
        $classId = 0;
        if (preg_match('/\[JOGO:(-?\d+)\]/', $description, $gameMatch) === 1) {
            $gameId = (int) $gameMatch[1];
        }
        if (preg_match('/\[TURMA:(\d+)\]/', $description, $classMatch) === 1) {
            $classId = (int) $classMatch[1];
        }
        return ['gameId' => $gameId, 'classId' => $classId];
    }

    public static function withReferences(int $gameId, int $classId, string $text): string
    {
        $prefix = $gameId !== 0 ? '[JOGO:' . $gameId . ']' : '';
        if ($classId > 0) {
            $prefix .= '[TURMA:' . $classId . ']';
        }
        return $prefix . trim($text);
    }

    private static function hasMarker(string $description): bool
    {
        return preg_match('/\[(?:JOGO|TURMA):/', $description) === 1;
    }
}
