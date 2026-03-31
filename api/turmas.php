<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

$acao = isset($_GET['acao']) ? $_GET['acao'] : null;

switch ($method) {
    case 'GET':


        $id = isset($_GET['id_turma']) ? $_GET['id_turma'] : null;
        $id_interclasse = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;
        $ver_ranking = isset($_GET['ranking']) ? true : false;

        // Base da Query com todas as colunas necessárias, incluindo a nova 'pontos_turma'
        $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, turmas.cat_turma, 
               turmas.nome_fantasia_turma, turmas.pontuacao_turma, interclasses.nome_interclasse 
        FROM turmas 
        INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse";

        // Mantendo as opções de filtro anteriores
        if ($id) {
            $sql .= " WHERE turmas.id_turma = $id";
        } elseif ($id_interclasse) {
            $sql .= " WHERE turmas.interclasses_id_interclasse = $id_interclasse";
        }

        // Adicionando a nova opção de ordenação por ranking (maior pontuação primeiro)
        if ($ver_ranking) {
            $sql .= " ORDER BY turmas.pontuacao_turma DESC";
        }

        $res = $conn->query($sql);
        $turmas = [];   

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $turmas[] = $row;
            }
        }

        echo json_encode($turmas);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->interclasses_id_interclasse) || !isset($data->categorias_id_categoria) || !isset($data->nome_turma)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos interclasses_id_interclasse, categorias_id_categoria e nome_turma são obrigatórios."
            ]);
            break;
        }

        $id_interclasse = intval($data->interclasses_id_interclasse);
        $id_categoria = intval($data->categorias_id_categoria);

        $nome_turma = "'" . $conn->real_escape_string($data->nome_turma) . "'";
        $turno_turma = isset($data->turno_turma) ? "'" . $conn->real_escape_string($data->turno_turma) . "'" : "NULL";
        $cat_turma = isset($data->cat_turma) ? "'" . $conn->real_escape_string($data->cat_turma) . "'" : "NULL";
        $nome_fantasia_turma = isset($data->nome_fantasia_turma) ? "'" . $conn->real_escape_string($data->nome_fantasia_turma) . "'" : "NULL";
        $pontuacao_turma = isset($data->pontuacao_turma) ? intval($data->pontuacao_turma) : 0;

        $sql = "INSERT INTO turmas (interclasses_id_interclasse, nome_turma, turno_turma, cat_turma, nome_fantasia_turma, pontuacao_turma, categorias_id_categoria) 
                VALUES ($id_interclasse, $nome_turma, $turno_turma, $cat_turma, $nome_fantasia_turma, $pontuacao_turma, $id_categoria)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Turma cadastrada com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execução da query: " . $conn->error
            ]);
        }
        break;

    case 'PUT':
        if ($acao == 'pontuacao') {
            $data = json_decode(file_get_contents('php://input'));

            $id_turma = isset($data->id_turma) ? $data->id_turma : null;
            $pontos_ganhos = isset($data->pontos) ? intval($data->pontos) : 0;

            if (!$id_turma) {
                echo json_encode([
                    "success" => false,
                    "message" => "ID da turma não informado."
                ]);
                exit;
            }

            $sql = "UPDATE turmas SET pontuacao_turma = pontuacao_turma + $pontos_ganhos WHERE id_turma = $id_turma";

            if ($conn->query($sql)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Pontuação da turma atualizada com sucesso!",
                    "pontos_adicionados" => $pontos_ganhos
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Erro ao atualizar: " . $conn->error
                ]);
            }
        }
        break;

    case 'PATCH':
        break;
}
