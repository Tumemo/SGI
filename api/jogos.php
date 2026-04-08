<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosJogos();

        $sql = "SELECT 
                    jogos.id_jogo, 
                    jogos.nome_jogo, 
                    jogos.data_jogo, 
                    jogos.inicio_jogo, 
                    jogos.terminno_jogo, 
                    jogos.status_jogo, 
                    modalidades.nome_modalidade, 
                    locais.nome_local,
                    categorias.nome_categoria
                FROM jogos 
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
                WHERE 1=1" . $filtro['sql'];

        $sql .= " ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_jogo, $data->data_jogo, $data->modalidades_id_modalidade, $data->locais_id_local)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $inicio = $data->inicio_jogo ?? '00:00:00';
        $termino = $data->terminno_jogo ?? '00:00:00';
        $status = $data->status_jogo ?? 'Agendado';

        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, terminno_jogo, modalidades_id_modalidade, locais_id_local, status_jogo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiis",
            $data->nome_jogo,
            $data->data_jogo,
            $inicio,
            $termino,
            $data->modalidades_id_modalidade,
            $data->locais_id_local,
            $status
        );

        try {
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Jogo cadastrado com sucesso!",
                    "id" => $conn->insert_id
                ]);
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(400); // Bad Request
            echo json_encode([
                "success" => false,
                "message" => "Erro de integridade: Verifique se o ID da Modalidade ou do Local existem.",
                "detalhes" => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        // Lógica de update aqui
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Metodo não permitido"]);
        break;
}