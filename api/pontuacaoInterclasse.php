<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT 
                    turmas.nome_turma,
                    modalidades.nome_modalidade,
                    SUM(pontuacao_interclasse.pontos) AS total_pontos
                FROM pontuacao_interclasse
                INNER JOIN turmas ON pontuacao_interclasse.turmas_id_turma = turmas.id_turma
                INNER JOIN modalidades ON pontuacao_interclasse.modalidades_id_modalidade = modalidades.id_modalidade
                GROUP BY turmas.id_turma, modalidades.id_modalidade
                ORDER BY total_pontos DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        $pontuacao = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode($pontuacao);
        break;

    case 'PUT':
        $dados = json_encode(file_get_contents("php://input"), true);

        if (!isset($dados['id_pontuacao'], $dados['pontos'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos (id_pontuacao e pontos são obrigatórios)."]);
            break;
        }

        $id = $dados['id_pontuacao'];
        $novosPontos = $dados['pontos'];

        $sql = "UPDATE pontuacao_interclasse SET pontos = ? WHERE id_pontuacao = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $novosPontos, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Pontuação atualizada com sucesso."]);
            } else {
                echo json_encode(["success" => false, "message" => "Nenhum registro encontrado ou nenhuma alteração feita."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao atualizar: " . $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Método não permitido."]);
        break;
}