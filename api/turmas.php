<?php
require_once '../config/database.php';
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
               turmas.nome_fantasia_turma, turmas.pontos_turma, interclasses.nome_interclasse 
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
            $sql .= " ORDER BY turmas.pontos_turma DESC";
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

        $data_json = json_decode(file_get_contents("php://input"), true);

        if (!isset($data_json['nome'], $data_json['turno'], $data_json['cat'], $data_json['nome_fantasia'])) {
            echo json_encode(["success" => false, "message" => "Dados incompletos"]);
            exit;
        }

        $nome = $data_json["nome"];
        $turno = $data_json["turno"];
        $cat = $data_json["cat"];
        $nome_fantasia = $data_json["nome_fantasia"];
        $interclase_id = isset($data_json["id_interclasse"]) ? $data_json["id_interclasse"] : null;


        $sql = "INSERT INTO turmas (nome_turma, turno_turma, cat_turma, nome_fantasia_turma, interclasses_id_interclasse) 
        VALUES ('$nome', '$turno', '$cat', '$nome_fantasia', '$interclase_id')";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode(["success" => true, "message" => "Turma cadastrada com sucesso"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao cadastrar turma: " . $conn->error]);
        }

        $conn->close();
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

            $sql = "UPDATE turmas SET pontos_turma = pontos_turma + $pontos_ganhos WHERE id_turma = $id_turma";

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
