<?php
require_once '../config/database.php';
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

    case "POST":
        $data_json = json_decode(file_get_contents("php://input"), true);

        // Verificamos se o campo existe dentro do array
        if (!isset($data_json['nome_interclasse'])) {
            echo json_encode(["success" => false, "message" => "Nenhum JSON recebido"]);
            exit;
        }

        $nome = $data_json['nome_interclasse'];
        $ano = date('Y-m-d H:i:s');

        // Usei $nome e $ano para evitar confusão com a variável do JSON
        $sql = "INSERT INTO interclasses(nome_interclasse, ano_interclasse) 
        VALUES('$nome', '$ano')";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode(["success" => true, "message" => "Cadastro de interclasse realizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }

    case 'PUT':
        
}
