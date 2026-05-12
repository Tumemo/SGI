<?php

declare(strict_types=1);

/**
 * Normaliza RA/RM para comparação com a base importada (apenas dígitos).
 */
function sgi_normalizar_ra(?string $rm): string
{
    return preg_replace('/[^0-9]/', '', (string) $rm);
}

/**
 * Converte data dd/mm/aaaa ou aaaa-mm-dd para Y-m-d; inválida retorna null.
 */
function sgi_parse_data_nascimento(?string $data): ?string
{
    $data = trim((string) $data);
    if ($data === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        return $dt && $dt->format('Y-m-d') === $data ? $data : null;
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
        if (!checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }
    return null;
}

/**
 * RF05: cruza RA e data de nascimento na base importada (competidor ativo).
 */
function sgi_buscar_competidor_por_ra_e_data(mysqli $conn, string $raNormalizado, string $dataYmd): ?array
{
    if ($raNormalizado === '') {
        return null;
    }
    $sql = 'SELECT id_usuario, nome_usuario, matricula_usuario, senha_usuario, nivel_usuario,
                   mesario_usuario, competidor_usuario, sigla_usuario, foto_usuario
            FROM usuarios
            WHERE matricula_usuario = ?
              AND data_nasc_usuario = ?
              AND competidor_usuario = \'1\'
              AND status_usuario = \'1\'
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $raNormalizado, $dataYmd);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
