<?php
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

function requerExclusao() {
    requerNivel([0]);
}
