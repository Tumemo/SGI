<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Filtros opcionais via URL
        $id_artilheiro = isset($_GET['id_artilheiro']) ? intval($_GET['id_artilheiro']) : null;
        $id_jogo = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : null;
        $id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : null;

        // Query base com JOINs para trazer nomes em vez de apenas IDs
        $sql = "SELECT 
                    a.id_artilheiro, 
                    a.num_gol, 
                    u.nome_usuario, 
                    u.matricula_usuario,
                    j.nome_jogo, 
                    j.data_jogo
                FROM artilheiros a
                INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                INNER JOIN jogos j ON a.jogos_id_jogo = j.id_jogo";

        // Aplicação de filtros
        $conditions = [];
        if ($id_artilheiro) {
            $conditions[] = "a.id_artilheiro = $id_artilheiro";
        }
        if ($id_jogo) {
            $conditions[] = "a.jogos_id_jogo = $id_jogo";
        }
        if ($id_usuario) {
            $conditions[] = "a.usuarios_id_usuario = $id_usuario";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY a.num_gol DESC";

        $res = $conn->query($sql);
        $artilheiros = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $artilheiros[] = $row;
            }
            echo json_encode($artilheiros);
        } else {
            echo json_encode(["success" => false, "message" => "Erro na consulta: " . $conn->error]);
        }
        break;

    case 'POST':
        
        break;
}

$conn->close();