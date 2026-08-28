<?php
require_once __DIR__ . '/includes/interclasse_helper.php';

function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function requerNivel(array $niveisPermitidos) {
    iniciarSessao();

    if (!isset($_SESSION['nivel'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Usuário não autenticado."]);
        exit;
    }

    if (!in_array((int)$_SESSION['nivel'], $niveisPermitidos, true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acesso não autorizado para este nível."]);
        exit;
    }
}

function requerEscrita() {
    requerNivel([0, 1]);
}

function requerOperacaoJogo() {
    requerNivel([0, 1, 2]);
}

function requerExclusao() {
    requerNivel([0]);
}

/**
 * Garante que o mesário (nível 2) só opere enquanto houver um interclasse
 * ativo no momento. Admin e colaborador passam sem bloqueio.
 *
 * Retorna o id do interclasse ativo (ou null quando não há nenhum).
 */
function garantirInterclasseAtivo(mysqli $conn): ?int {
    iniciarSessao();
    $nivel = (int)($_SESSION['nivel'] ?? -1);

    if ($nivel !== 2) {
        return buscarInterclasseAtivo($conn);
    }

    $ativo = buscarInterclasseAtivo($conn);
    if ($ativo === null) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Nenhuma edição de interclasse está ativa no momento. Entre em contato com o administrador."
        ]);
        exit;
    }

    $_SESSION['id_interclasse'] = $ativo;
    return $ativo;
}
