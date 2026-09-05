<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once __DIR__ . '/includes/interclasse_helper.php';

use App\Shared\Http\SessionManager;

function iniciarSessao(): void
{
SessionManager::start();

// Quando acessado diretamente, este arquivo também atua como endpoint de
// diagnóstico da sessão usado pelo front-end. Durante require por outros
// endpoints a URI é diferente e apenas as funções de autorização abaixo são
// declaradas.
$authRequestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
if (str_ends_with('/' . ltrim($authRequestPath, '/'), '/api/auth.php')) {
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'usuario' => [
            'id' => (int) ($_SESSION['id_usuario'] ?? $_SESSION['id']),
            'nome' => (string) ($_SESSION['nome'] ?? ''),
            'nivel' => (int) ($_SESSION['nivel'] ?? -1),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
}

/** @param list<int> $niveisPermitidos */
function requerNivel(array $niveisPermitidos): void
{
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

function requerEscrita(): void
{
    requerNivel([0, 1]);
}

function requerOperacaoJogo(): void
{
    requerNivel([0, 1, 2]);
}

function requerExclusao(): void
{
    requerNivel([0]);
}

function requerAcessoTurma(mysqli $conn, int $idTurma, int $idInterclasse): void
{
    requerNivel([0, 1, 2, 3]);
    if ((int) ($_SESSION['nivel'] ?? -1) !== 3) {
        return;
    }

    $idUsuario = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
    $statement = $conn->prepare(
        'SELECT 1 FROM usuarios WHERE id_usuario = ? AND turmas_id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1',
    );
    $statement->bind_param('iii', $idUsuario, $idTurma, $idInterclasse);
    $statement->execute();
    $canAccess = $statement->get_result()->fetch_assoc() !== null;
    $statement->close();

    if ($canAccess) {
        return;
    }

    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem acesso ao histórico desta turma.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Garante que o mesário (nível 2) só opere enquanto houver um interclasse
 * ativo no momento. Admin e colaborador passam sem bloqueio.
 *
 * Retorna o id do interclasse ativo (ou null quando não há nenhum).
 */
function garantirInterclasseAtivo(mysqli $conn): ?int
{
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
