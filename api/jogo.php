<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = isset($_GET['id_jogo']) ? $_GET['id_jogo'] : null;
        $id_modalidade = isset($_GET['modalidades_id_modalidade']) ? $_GET['modalidades_id_modalidade'] : null;
        $id_local = isset($_GET['locais_id_local']) ? $_GET['locais_id_local'] : null;

        if ($id) {
            $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.id_jogo = $id";
        } elseif ($id_modalidade) {
            $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.modalidades_id_modalidade = $id_modalidade";
        } elseif ($id_local) {
            $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local 
    WHERE jogos.locais_id_local = $id_local";
        } else {
            $sql = "SELECT jogos.id_jogo, jogos.nome_jogo, jogos.data_jogo, jogos.inicio_jogo, jogos.terminno_jogo, jogos.status_jogo, modalidades.nome_modalidade, locais.nome_local 
    FROM jogos 
    INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
    INNER JOIN locais ON locais.id_local = jogos.locais_id_local";
        }

        $res = $conn->query($sql);
        $jogos = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $jogos[] = $row;
            }
        }

        echo json_encode($jogos);
        break;

    case 'POST':

        $json = json_decode(file_get_contents("php://input"));

        // Validando os campos obrigatórios conforme a imagem
        if (!isset($json->nome_jogo, $json->status_jogo, $json->modalidades_id_modalidade, $json->locais_id_local)) {
            echo json_encode(["success" => false, "message" => "Dados incompletos"]);
            exit;
        }

        $nome = $json->nome_jogo;
        $data_hoje = date('Y-m-d');
        $inicio = date('H:i:s');
        // Note o nome da coluna conforme a imagem: terminno_jogo (com dois 'n')
        $termino = "00:00:00";
        $status = $json->status_jogo;
        $modalidade = (int) $json->modalidades_id_modalidade;
        $local = (int) $json->locais_id_local;

        // SQL ajustado com os nomes exatos da imagem
        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, terminno_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) 
        VALUES ('$nome', '$data_hoje', '$inicio', '$termino', '$status', '$modalidade', '$local')";

        $res = $conn->query($sql);

        if ($res) {
            echo json_encode(["success" => true, "message" => "Cadastro de jogo realizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        break;

    case 'PUT':
        break;

    case 'PATCH':
        break;
}
