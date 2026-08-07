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
$id_equipes = isset($data->id_equipes) && is_array($data->id_equipes) ? $data->id_equipes : [];

if ($id_interclasse <= 0 || empty($id_equipes)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "id_interclasse e id_equipes são obrigatórios."]);
    exit();
}

if (count($id_equipes) > 3) {
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

    $id_equipes = array_values(array_unique(array_map('intval', $id_equipes)));
    $id_equipes = array_values(array_filter($id_equipes, fn($id) => $id > 0));

    if (empty($id_equipes)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nenhuma equipe válida informada."]);
        exit();
    }

    // Modalidades em que o usuário já está inscrito NESTE interclasse
    $sqlJa = "SELECT m.id_modalidade, e.id_equipe
              FROM equipes_has_usuarios eu
              INNER JOIN equipes e ON eu.equipes_id_equipe = e.id_equipe
              INNER JOIN modalidades m ON e.modalidades_id_modalidade = m.id_modalidade
              WHERE eu.usuarios_id_usuario = ? AND e.status_equipe = '1' AND m.interclasses_id_interclasse = ?";
    $stmtJa = $conn->prepare($sqlJa);
    if (!$stmtJa) throw new RuntimeException('Erro ao preparar verificação de inscrições: ' . $conn->error);
    $stmtJa->bind_param('ii', $id_usuario, $id_interclasse);
    $stmtJa->execute();
    $resJa = $stmtJa->get_result();
    $idsJaInscritos = [];
    while ($rowJa = $resJa->fetch_assoc()) {
        $idsJaInscritos[(int) $rowJa['id_modalidade']] = true;
    }
    $stmtJa->close();

    // Valida cada equipe: ativa, da turma do usuário e do mesmo interclasse
    $equipesValidas = [];
    $erros = [];
    foreach ($id_equipes as $id_equipe) {
        $sqlEquipe = "SELECT e.id_equipe, e.turmas_id_turma, e.modalidades_id_modalidade, m.interclasses_id_interclasse
                      FROM equipes e
                      INNER JOIN modalidades m ON e.modalidades_id_modalidade = m.id_modalidade
                      WHERE e.id_equipe = ? AND e.status_equipe = '1'";
        $stmtEquipe = $conn->prepare($sqlEquipe);
        if (!$stmtEquipe) {
            $erros[] = "Erro ao validar equipe $id_equipe";
            continue;
        }
        $stmtEquipe->bind_param('i', $id_equipe);
        $stmtEquipe->execute();
        $resEquipe = $stmtEquipe->get_result();
        $dadosEquipe = $resEquipe->fetch_assoc();
        $stmtEquipe->close();

        if (!$dadosEquipe) {
            $erros[] = "Equipe $id_equipe não encontrada.";
            continue;
        }
        if ((int) $dadosEquipe['turmas_id_turma'] !== $id_turma) {
            $erros[] = "Você só pode se inscrever em equipes da sua turma.";
            continue;
        }
        if ((int) $dadosEquipe['interclasses_id_interclasse'] !== $id_interclasse) {
            $erros[] = "Equipe $id_equipe não pertence a este interclasse.";
            continue;
        }

        $equipesValidas[] = [
            'id_equipe' => (int) $dadosEquipe['id_equipe'],
            'id_modalidade' => (int) $dadosEquipe['modalidades_id_modalidade']
        ];
    }

    if (empty($equipesValidas)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nenhuma equipe válida informada." . (empty($erros) ? '' : ' ' . implode(' ', array_unique($erros)))]);
        exit();
    }

    $modalidadesEscolhidas = array_map(fn($e) => $e['id_modalidade'], $equipesValidas);
    $modalidadesEscolhidas = array_unique($modalidadesEscolhidas);
    $novasModalidades = array_values(array_filter(
        $modalidadesEscolhidas,
        fn($id) => !isset($idsJaInscritos[$id])
    ));

    if (count($idsJaInscritos) + count($novasModalidades) > 3) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Máximo de 3 modalidades permitidas por aluno."]);
        exit();
    }

    $insercoes = 0;
    $jaExistentes = 0;

    foreach ($equipesValidas as $equipe) {
        $id_equipe = $equipe['id_equipe'];

        $sqlCheckVinculo = "SELECT 1 FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?";
        $stmtCheckVinculo = $conn->prepare($sqlCheckVinculo);
        if (!$stmtCheckVinculo) {
            $erros[] = "Erro ao preparar verificação de vínculo para equipe $id_equipe";
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
            $erros[] = "Erro ao preparar vínculo para equipe $id_equipe";
            continue;
        }
        $stmtInsertVinculo->bind_param('ii', $id_equipe, $id_usuario);
        if ($stmtInsertVinculo->execute()) {
            $insercoes++;
        } else {
            $erros[] = "Erro ao inscrever na equipe $id_equipe: " . $stmtInsertVinculo->error;
        }
        $stmtInsertVinculo->close();
    }

    $success = $insercoes > 0 || $jaExistentes > 0;
    $message = $insercoes > 0 ? "Inscrição realizada com sucesso!" : "Nenhuma inscrição nova foi necessária.";
    if ($jaExistentes > 0) {
        $message .= " Você já estava inscrito em $jaExistentes equipe(s).";
    }
    if (!empty($erros)) {
        $message .= " " . implode(' ', array_unique($erros));
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
