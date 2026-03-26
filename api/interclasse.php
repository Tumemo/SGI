<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = isset($_GET['id_interclasse']) ? $_GET['id_interclasse'] : null;
        // teste asdad
        if ($id) {
            $sql = "SELECT interclasses.id_interclasse, interclasses.nome_interclasse, interclasses.ano_interclasse 
    FROM interclasses 
    WHERE interclasses.id_interclasse = $id";
        } else {
            $sql = "SELECT interclasses.id_interclasse, interclasses.nome_interclasse, interclasses.ano_interclasse 
    FROM interclasses";
        }

        $res = $conn->query($sql);
        $interclasse = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $interclasse[] = $row;
            }
        }

        echo json_encode($interclasse);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_interclasse) || !isset($data->ano_interclasse)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos nome_interclasse e ano_interclasse são obrigatórios."
            ]);
            break;
        }

        $nome_interclasse = "'" . $conn->real_escape_string($data->nome_interclasse) . "'";
        $ano_interclasse = "'" . $conn->real_escape_string($data->ano_interclasse) . "'";

        $sql = "INSERT INTO interclasses (nome_interclasse, ano_interclasse) VALUES ($nome_interclasse, $ano_interclasse)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Interclasse cadastrado com sucesso"
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
}
