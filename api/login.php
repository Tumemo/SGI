<?php

declare(strict_types=1);

session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/usuario_validacao.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}');

$matriculaBruta = trim((string) ($data->matricula_usuario ?? $data->rm ?? $data->ra ?? ''));
$senhaDigitada = (string) ($data->senha_usuario ?? '');
$dataNascBruta = $data->data_nasc_usuario ?? null;
$dataNascYmd = is_string($dataNascBruta) ? sgi_parse_data_nascimento($dataNascBruta) : null;

$ra = sgi_normalizar_ra($matriculaBruta);

if ($ra === '' && $matriculaBruta === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível validar os dados informados.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // RF05: competidor valida com RA normalizado + data de nascimento (sem consulta só por data).
    if ($dataNascYmd !== null && $ra !== '') {
        $competidor = sgi_buscar_competidor_por_ra_e_data($conn, $ra, $dataNascYmd);
        if ($competidor !== null) {
            $_SESSION['id_usuario'] = (int) $competidor['id_usuario'];
            $_SESSION['nivel'] = $competidor['nivel_usuario'];
            $_SESSION['logado'] = true;

            echo json_encode([
                'sucesso' => true,
                'permissoes' => [
                    'admin' => (int) $competidor['nivel_usuario'] > 0,
                    'mesario' => (int) $competidor['mesario_usuario'] === 1,
                    'competidor' => (int) $competidor['competidor_usuario'] === 1,
                ],
                'dados' => [
                    'nome' => $competidor['nome_usuario'],
                    'sigla' => $competidor['sigla_usuario'],
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Colaboradores / perfis com senha: matricula como cadastrada + senha (sem expor se a matrícula existe).
    if ($matriculaBruta === '' || $senhaDigitada === '') {
        echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível validar os dados informados.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT id_usuario, nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, mesario_usuario, competidor_usuario, sigla_usuario
         FROM usuarios
         WHERE matricula_usuario = ? AND status_usuario = '1'
         LIMIT 1"
    );
    if (!$stmt) {
        throw new RuntimeException('prepare');
    }
    $stmt->bind_param('s', $matriculaBruta);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($senhaDigitada, $usuario['senha_usuario'])) {
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['nivel'] = $usuario['nivel_usuario'];
        $_SESSION['logado'] = true;

        echo json_encode([
            'sucesso' => true,
            'permissoes' => [
                'admin' => (int) $usuario['nivel_usuario'] > 0,
                'mesario' => (int) $usuario['mesario_usuario'] === 1,
                'competidor' => (int) $usuario['competidor_usuario'] === 1,
            ],
            'dados' => [
                'nome' => $usuario['nome_usuario'],
                'sigla' => $usuario['sigla_usuario'],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível validar os dados informados.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no servidor.'], JSON_UNESCAPED_UNICODE);
}
