<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

/** Resolve o formato da competição a partir do cadastro do tipo, nunca do esporte. */
final class TipoCompeticaoRules
{
    public const INDIVIDUAL = 'individual';
    public const MATA_MATA = 'mata_mata';

    /** @param array<string,mixed> $modality */
    public static function resolve(array $modality): ?string
    {
        $semantic = $modality['tipo_competicao'] ?? null;
        if (is_string($semantic)) {
            $semantic = self::normalize($semantic);
            if ($semantic !== null) {
                return $semantic;
            }
        }
        foreach (['nome_tipo_modalidade', 'tipo_modalidade'] as $field) {
            if (isset($modality[$field]) && is_string($modality[$field])) {
                $resolved = self::normalize($modality[$field]);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }
        // Compatibilidade com mocks/instalações antigas que ainda não retornam
        // o JOIN do tipo. As consultas de produção sempre expõem o nome.
        if ((int) ($modality['tipos_modalidades_id_tipo_modalidade'] ?? 0) === 2) {
            return self::INDIVIDUAL;
        }
        if ((int) ($modality['tipos_modalidades_id_tipo_modalidade'] ?? 0) === 1) {
            return self::MATA_MATA;
        }
        return null;
    }

    public static function isIndividual(array $modality): bool
    {
        return self::resolve($modality) === self::INDIVIDUAL;
    }

    private static function normalize(string $value): ?string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = str_replace(['–', '—', '_'], '-', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        if (in_array($value, ['individual', 'prova individual', 'individualizada'], true)) {
            return self::INDIVIDUAL;
        }
        if (in_array($value, ['mata-mata', 'mata mata', 'mata-mata (eliminatório)', 'eliminatório', 'eliminatoria'], true)) {
            return self::MATA_MATA;
        }
        return null;
    }
}
