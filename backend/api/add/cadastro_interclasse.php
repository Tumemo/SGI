<?php
require_once "../../config/db.php";
header("Content-Type: application/json");

// O 'true' transforma o JSON em Array Associativo
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
?>