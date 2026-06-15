<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido."]);
    exit();
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sessão expirada ou usuário não autenticado."]);
    exit();
}

$id_usuario = (int) $_SESSION['id'];
$data = json_decode(file_get_contents("php://input"));

$id_interclasse = isset($data->id_interclasse) ? (int) $data->id_interclasse : 0;
$id_modalidades = isset($data->id_modalidades) && is_array($data->id_modalidades) ? $data->id_modalidades : [];

if ($id_interclasse <= 0 || empty($id_modalidades)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "id_interclasse e id_modalidades são obrigatórios."]);
    exit();
}

if (count($id_modalidades) > 3) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Máximo de 3 modalidades permitidas."]);
    exit();
}

try {
    $sqlUser = "SELECT turmas_id_turma, interclasses_id_interclasse FROM usuarios WHERE id_usuario = ?";
    $stmtUser = $conn->prepare($sqlUser);
    if (!$stmtUser) throw new RuntimeException('Erro ao preparar consulta de usuário: ' . $conn->error);
    $stmtUser->bind_param('i', $id_usuario);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();
    if ($userResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Usuário não encontrado."]);
        exit();
    }
    $userData = $userResult->fetch_assoc();
    $stmtUser->close();

    $id_turma = (int) $userData['turmas_id_turma'];
    if ($id_turma <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Usuário não possui turma vinculada."]);
        exit();
    }

    $insercoes = 0;
    $jaExistentes = 0;
    $erros = [];

    foreach ($id_modalidades as $id_modalidade) {
        $id_modalidade = (int) $id_modalidade;
        if ($id_modalidade <= 0) continue;

        $sqlEquipe = "SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? LIMIT 1";
        $stmtEquipe = $conn->prepare($sqlEquipe);
        if (!$stmtEquipe) {
            $erros[] = "Erro ao preparar consulta de equipe para modalidade $id_modalidade";
            continue;
        }
        $stmtEquipe->bind_param('ii', $id_modalidade, $id_turma);
        $stmtEquipe->execute();
        $stmtEquipe->store_result();

        if ($stmtEquipe->num_rows > 0) {
            $stmtEquipe->bind_result($id_equipe);
            $stmtEquipe->fetch();
            $stmtEquipe->close();
        } else {
            $stmtEquipe->close();
            $sqlInsertEquipe = "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma) VALUES ('1', ?, ?)";
            $stmtInsertEquipe = $conn->prepare($sqlInsertEquipe);
            if (!$stmtInsertEquipe) {
                $erros[] = "Erro ao criar equipe para modalidade $id_modalidade";
                continue;
            }
            $stmtInsertEquipe->bind_param('ii', $id_modalidade, $id_turma);
            if (!$stmtInsertEquipe->execute()) {
                $erros[] = "Erro ao inserir equipe para modalidade $id_modalidade: " . $stmtInsertEquipe->error;
                $stmtInsertEquipe->close();
                continue;
            }
            $id_equipe = $stmtInsertEquipe->insert_id;
            $stmtInsertEquipe->close();
        }

        $sqlCheckVinculo = "SELECT 1 FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?";
        $stmtCheckVinculo = $conn->prepare($sqlCheckVinculo);
        if (!$stmtCheckVinculo) {
            $erros[] = "Erro ao preparar verificação de vínculo para modalidade $id_modalidade";
            continue;
        }
        $stmtCheckVinculo->bind_param('ii', $id_equipe, $id_usuario);
        $stmtCheckVinculo->execute();
        $stmtCheckVinculo->store_result();

        if ($stmtCheckVinculo->num_rows > 0) {
            $jaExistentes++;
            $stmtCheckVinculo->close();
            continue;
        }
        $stmtCheckVinculo->close();

        $sqlInsertVinculo = "INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)";
        $stmtInsertVinculo = $conn->prepare($sqlInsertVinculo);
        if (!$stmtInsertVinculo) {
            $erros[] = "Erro ao preparar vínculo para modalidade $id_modalidade";
            continue;
        }
        $stmtInsertVinculo->bind_param('ii', $id_equipe, $id_usuario);
        if ($stmtInsertVinculo->execute()) {
            $insercoes++;
        } else {
            $erros[] = "Erro ao inscrever na modalidade $id_modalidade: " . $stmtInsertVinculo->error;
        }
        $stmtInsertVinculo->close();
    }

    $success = $insercoes > 0 || $jaExistentes > 0;
    $message = $insercoes > 0 ? "Inscrição realizada com sucesso em $insercoes modalidade(s)!" : "Nenhuma inscrição nova foi necessária.";
    if ($jaExistentes > 0) {
        $message .= " Você já estava inscrito em $jaExistentes modalidade(s).";
    }

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "insercoes" => $insercoes,
        "ja_existentes" => $jaExistentes,
        "erros" => empty($erros) ? null : $erros
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
