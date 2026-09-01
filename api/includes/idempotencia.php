<?php

declare(strict_types=1);

/**
 * Chave estável gerada pelo cliente para uma mutação. Ela permite devolver a
 * resposta originalmente confirmada se a conexão cair depois de o servidor já
 * ter gravado a alteração, evitando duplicar gols e ocorrências no reenvio.
 */
function sgi_chave_mutacao_atual(): ?string
{
    $chave = trim((string) ($_SERVER['HTTP_X_SGI_MUTATION_ID'] ?? ''));
    if ($chave === '' || !preg_match('/\A[a-zA-Z0-9._:-]{12,180}\z/D', $chave)) {
        return null;
    }
    return $chave;
}

/** @return array{status:int,payload:array<string,mixed>}|null */
function sgi_buscar_resposta_idempotente(mysqli $conn, string $rota): ?array
{
    $chave = sgi_chave_mutacao_atual();
    if ($chave === null) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT status_http, resposta_json
         FROM sincronizacoes_idempotentes
         WHERE rota = ? AND chave_mutacao = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $rota, $chave);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }

    $payload = json_decode((string) $row['resposta_json'], true);
    return [
        'status' => max(200, (int) ($row['status_http'] ?? 200)),
        'payload' => is_array($payload) ? $payload : ['success' => true],
    ];
}

/** @param array<string,mixed> $payload */
function sgi_guardar_resposta_idempotente(mysqli $conn, string $rota, int $statusHttp, array $payload): void
{
    $chave = sgi_chave_mutacao_atual();
    if ($chave === null) {
        return;
    }

    $resposta = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($resposta === false) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO sincronizacoes_idempotentes (rota, chave_mutacao, status_http, resposta_json)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE rota = VALUES(rota)'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ssis', $rota, $chave, $statusHttp, $resposta);
    $stmt->execute();
    $stmt->close();
}

/** @param array<string,mixed> $payload */
function sgi_enviar_resposta_idempotente(mysqli $conn, string $rota, int $statusHttp, array $payload): void
{
    sgi_guardar_resposta_idempotente($conn, $rota, $statusHttp, $payload);
    http_response_code($statusHttp);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}
