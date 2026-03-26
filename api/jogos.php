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
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo_ocorrecia) || !isset($data->descricao_ocorrecia) || !isset($data->data_ocorrecia) || !isset($data->hora_ocorrecia) || !isset($data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, hora_ocorrecia e usuarios_id_usuario son obrigatorios."
            ]);
            break;
        }

        $titulo_ocorrecia = "'" . $conn->real_escape_string($data->titulo_ocorrecia) . "'";
        $descricao_ocorrecia = "'" . $conn->real_escape_string($data->descricao_ocorrecia) . "'";
        $data_ocorrecia = "'" . $conn->real_escape_string($data->data_ocorrecia) . "'";
        $hora_ocorrecia = "'" . $conn->real_escape_string($data->hora_ocorrecia) . "'";

        $id_usuario = intval($data->usuarios_id_usuario);
        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $sql = "INSERT INTO ocorrencias (titulo_ocorrecia, descricao_ocorrecia, data_ocorrecia, hora_ocorrecia, usuarios_id_usuario, penalidade) 
                VALUES ($titulo_ocorrecia, $descricao_ocorrecia, $data_ocorrecia, $hora_ocorrecia, $id_usuario, $penalidade)";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Ocorrencia rexistrada con éxito"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro na execución da query: " . $conn->error
            ]);
        }
        break;
    case 'PUT':
        break;

    case 'PATCH':
        break;
}
