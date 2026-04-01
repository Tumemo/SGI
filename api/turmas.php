<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // PUT removido pois a pontuação agora é relacional
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

// Lida com requisições preflight do CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$acao = isset($_GET['acao']) ? $_GET['acao'] : null;

switch ($method) {
    case 'GET':
        // Segurança: forçar os IDs a serem inteiros
        $id = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : null;
        $id_interclasse = isset($_GET['id_interclasse']) ? intval($_GET['id_interclasse']) : null;
        $categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;
        $ver_ranking = isset($_GET['ranking']) ? true : false;

        // A coluna 'pontuacao_turma' foi removida do SELECT, pois a pontuação agora vem de outras tabelas
        $sql = "SELECT turmas.id_turma, turmas.nome_turma, turmas.turno_turma, 
                       turmas.nome_fantasia_turma, interclasses.nome_interclasse 
                FROM turmas 
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse";

        // Lógica para construir a query dinamicamente
        $condicoes = [];
        if ($id) {
            $condicoes[] = "turmas.id_turma = $id";
        }
        if ($id_interclasse) {
            $condicoes[] = "turmas.interclasses_id_interclasse = $id_interclasse";
        }
        if ($categoria) {
            $condicoes[] = "turmas.categorias_id_categoria = $categoria";
        }

        if (count($condicoes) > 0) {
            $sql .= " WHERE " . implode(" AND ", $condicoes);
        }

        if ($ver_ranking) {
            // TODO: Como a pontuação não fica mais na tabela turmas, o ranking precisará ser 
            // calculado agrupando/somando (SUM) os valores das tabelas `partidas` e `pontuacoes`.
            // Para não quebrar a API, a ordenação simples foi temporariamente inibida aqui.
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
        $nome_fantasia_turma = isset($data->nome_fantasia_turma) ? "'" . $conn->real_escape_string($data->nome_fantasia_turma) . "'" : "NULL";

        // A coluna 'pontuacao_turma' foi removida do INSERT
        $sql = "INSERT INTO turmas (interclasses_id_interclasse, nome_turma, turno_turma, nome_fantasia_turma, categorias_id_categoria) 
                VALUES ($id_interclasse, $nome_turma, $turno_turma, $nome_fantasia_turma, $id_categoria)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(201); 
            echo json_encode([
                "success" => true,
                "message" => "Turma cadastrada com sucesso",
                "id_turma" => $conn->insert_id 
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execução da query: " . $conn->error
            ]);
        }
        break;

    default:
        // O método PUT foi removido deste arquivo.
        http_response_code(405); 
        echo json_encode(["success" => false, "message" => "Método HTTP não permitido."]);
        break;
}