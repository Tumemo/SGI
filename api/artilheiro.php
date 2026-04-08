<?php
require_once '../config/db.php';
require_once 'filtros.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':

        $filtro = aplicarFiltrosArtilharia();

        $sql = "SELECT 
                    usuarios.nome_usuario, 
                    SUM(artilheiros.num_gol) AS total_gols, 
                    modalidades.nome_modalidade,
                    turmas.nome_turma,
                    turmas.nome_fantasia_turma
                FROM artilheiros
                INNER JOIN usuarios ON artilheiros.usuarios_id_usuario = usuarios.id_usuario
                INNER JOIN turmas ON usuarios.turmas_id_turma = turmas.id_turma
                INNER JOIN jogos ON artilheiros.jogos_id_jogo = jogos.id_jogo
                INNER JOIN modalidades ON jogos.modalidades_id_modalidade = modalidades.id_modalidade
                INNER JOIN categorias ON modalidades.categorias_id_categoria = categorias.id_categoria
                WHERE 1=1" . $filtro['sql'];

        $sql .= " GROUP BY usuarios.id_usuario, modalidades.id_modalidade 
                  ORDER BY total_gols DESC";

        $stmt = $conn->prepare($sql);

        // Aplica os parâmetros se existirem
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $artilharia = $res->fetch_all(MYSQLI_ASSOC);

        echo json_encode($artilharia);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->usuarios_id_usuario, $data->jogos_id_jogo, $data->num_gol)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $sql = "INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, num_gol) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $data->usuarios_id_usuario, $data->jogos_id_jogo, $data->num_gol);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Gols registrados com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao salvar: " . $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não suportado."]);
        break;
}