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
        // O ID não tem semântica estável entre instalações. Sem o nome/código
        // semântico vindo do cadastro, a competição fica deliberadamente
        // desconhecida para não cair silenciosamente no mata-mata.
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
        if (in_array($value, ['mata-mata', 'mata mata', 'mata-mata (eliminatório)', 'mata-mata (eliminatória)', 'eliminatório', 'eliminatória', 'eliminatoria'], true)) {
            return self::MATA_MATA;
        }
        return null;
    }
}
