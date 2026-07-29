<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';

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

requerEscrita();
$data = json_decode(file_get_contents("php://input"));
$idInterclasse = isset($data->id_interclasse) ? (int) $data->id_interclasse : 0;

if ($idInterclasse <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "O ID do interclasse é obrigatório."]);
    exit();
}

try {
    // 1. Busca id, categoria e O NOME da turma
    $sqlTurmas = "SELECT id_turma, nome_turma, categorias_id_categoria FROM turmas WHERE status_turma = '1' AND interclasses_id_interclasse = ?";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    if (!$stmtTurmas) throw new RuntimeException('Erro ao preparar consulta de turmas: ' . $conn->error);
    $stmtTurmas->bind_param("i", $idInterclasse);
    $stmtTurmas->execute();
    $resultTurmas = $stmtTurmas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtTurmas->close();

    // 2. Busca id, categoria e O NOME da modalidade
    $sqlModalidades = "SELECT id_modalidade, nome_modalidade, categorias_id_categoria FROM modalidades WHERE status_modalidade = '1' AND interclasses_id_interclasse = ?";
    $stmtModalidades = $conn->prepare($sqlModalidades);
    if (!$stmtModalidades) throw new RuntimeException('Erro ao preparar consulta de modalidades: ' . $conn->error);
    $stmtModalidades->bind_param("i", $idInterclasse);
    $stmtModalidades->execute();
    $resultModalidades = $stmtModalidades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtModalidades->close();

    $equipesGeradas = 0;
    $erros = [];

    foreach ($resultTurmas as $turma) {
        foreach ($resultModalidades as $modalidade) {
            if ($turma['categorias_id_categoria'] != $modalidade['categorias_id_categoria']) {
                continue;
            }

            $idTurma = $turma['id_turma'];
            $idModalidade = $modalidade['id_modalidade'];
            $nomeTurma = $turma['nome_turma'];
            $nomeModalidade = $modalidade['nome_modalidade'];

            // 3. Verifica se já existe QUALQUER equipe para essa turma nesta modalidade
            $sqlCount = "SELECT COUNT(*) as total FROM equipes WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ?";
            $stmtCount = $conn->prepare($sqlCount);
            if (!$stmtCount) continue;
            $stmtCount->bind_param("ii", $idModalidade, $idTurma);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result()->fetch_assoc();
            $totalExistente = (int) ($resCount['total'] ?? 0);
            $stmtCount->close();

            // REGRA RÍGIDA: Se já existir pelo menos 1 equipe, ignora e pula para o próximo
            if ($totalExistente > 0) {
                continue;
            }

            // 4. Monta o nome da única equipe da turma/modalidade (Ex: "9º Ano A Xadrez Equipe 1")
            $nomeEquipe = trim("{$nomeTurma} {$nomeModalidade} Equipe 1");

            // 5. Insere a equipe com o nome preenchido
            $sqlInsert = "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES ('1', ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            if (!$stmtInsert) {
                $erros[] = "Erro ao preparar inserção para turma $idTurma / modalidade $idModalidade";
                continue;
            }
            $stmtInsert->bind_param("iis", $idModalidade, $idTurma, $nomeEquipe);
            
            if ($stmtInsert->execute()) {
                $equipesGeradas++;
            } else {
                $erros[] = "Erro ao inserir equipe para turma $idTurma / modalidade $idModalidade: " . $stmtInsert->error;
            }
            $stmtInsert->close();
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Processamento concluído.",
        "equipes_criadas" => $equipesGeradas,
        "erros" => $erros
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}