<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
        $id_modalidade = isset($_GET['id_modalidade']) ? intval($_GET['id_modalidade']) : null;

        // Query com nomes completos das tabelas
        $sql = "SELECT 
                    usuarios.nome_usuario, 
                    SUM(artilheiros.num_gol) AS total_gols, 
                    modalidades.nome_modalidade
                FROM artilheiros
                INNER JOIN usuarios ON artilheiros.usuarios_id_usuario = usuarios.id_usuario
                INNER JOIN jogos ON artilheiros.jogos_id_jogo = jogos.id_jogo
                INNER JOIN modalidades ON jogos.modalidades_id_modalidade = modalidades.id_modalidade
                WHERE YEAR(jogos.data_jogo) = ?";

        if ($id_modalidade) {
            $sql .= " AND modalidades.id_modalidade = ?";
        }

        $sql .= " GROUP BY usuarios.id_usuario, modalidades.id_modalidade ORDER BY total_gols DESC";

        $stmt = $conn->prepare($sql);
        
        if ($id_modalidade) {
            $stmt->bind_param("ii", $ano, $id_modalidade);
        } else {
            $stmt->bind_param("i", $ano);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $artilharia = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode($artilharia);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->usuarios_id_usuario) || !isset($data->jogos_id_jogo) || !isset($data->num_gol)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $id_usuario = intval($data->usuarios_id_usuario);
        $id_jogo = intval($data->jogos_id_jogo);
        $num_gol = intval($data->num_gol);

        // Volta para o INSERT simples que você tinha
        $sql = "INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, num_gol) VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $id_usuario, $id_jogo, $num_gol);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Dados cadastrados com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Erro ao cadastrar: " . $conn->error
            ]);
        }
        break;
}