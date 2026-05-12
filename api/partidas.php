<?php
require_once '../config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';
require_once 'filtros.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosPartidas();

        // SQL robusto que traz os nomes das equipes e turmas envolvidas
        $sql = "SELECT 
                    p.id_partida, 
                    p.equipes_id_equipe, 
                    p.resultado_partida,
                    j.id_jogo,
                    j.nome_jogo, 
                    j.status_jogo,
                    t.nome_turma,
                    t.nome_fantasia_turma,
                    m.nome_modalidade
                FROM partidas p
                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade
                WHERE 1=1" . $filtro['sql'];

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // No seu sistema, o POST parece ser usado para ATUALIZAR o resultado (encerrar partida)
        // Se for criar um novo vínculo de equipe no jogo, use um INSERT.
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_partida, $data->resultado_final)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        // Inicia uma transação para garantir que o resultado e o status do jogo mudem juntos
        $conn->begin_transaction();

        try {
            // 1. Atualiza o placar da partida específica
            $sqlPlacar = "UPDATE partidas SET resultado_partida = ? WHERE id_partida = ?";
            $stmt1 = $conn->prepare($sqlPlacar);
            $stmt1->bind_param("ii", $data->resultado_final, $data->id_partida);
            $stmt1->execute();

            // 2. Status do jogo (ENUM do schema: Concluido)
            $sqlJogo = 'SELECT jogos_id_jogo FROM partidas WHERE id_partida = ? LIMIT 1';
            $stJ = $conn->prepare($sqlJogo);
            $stJ->bind_param('i', $data->id_partida);
            $stJ->execute();
            $rowJ = $stJ->get_result()->fetch_assoc();
            $stJ->close();
            $idJogoPart = (int) ($rowJ['jogos_id_jogo'] ?? 0);

            $sqlStatus = "UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?";
            $stmt2 = $conn->prepare($sqlStatus);
            $stmt2->bind_param('i', $idJogoPart);
            $stmt2->execute();
            $stmt2->close();

            if ($idJogoPart > 0) {
                sgi_chaveamento_processar_avanco($conn, $idJogoPart);
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Resultado salvo e jogo finalizado!"]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao processar: " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_partida)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da partida é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->jogos_id_jogo)) {
            $campos[] = "jogos_id_jogo = ?";
            $params[] = $data->jogos_id_jogo;
            $types .= "i";
        }
        if (isset($data->equipes_id_equipe)) {
            $campos[] = "equipes_id_equipe = ?";
            $params[] = $data->equipes_id_equipe;
            $types .= "i";
        }
        if (isset($data->resultado_partida)) {
            $campos[] = "resultado_partida = ?";
            $params[] = $data->resultado_partida;
            $types .= "i";
        }
        if (isset($data->status_pardida)) {
            $campos[] = "status_pardida = ?";
            $params[] = $data->status_pardida;
            $types .= "s";
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado enviado para atualização."]);
            break;
        }

        $sql = "UPDATE partidas SET " . implode(", ", $campos) . " WHERE id_partida = ?";
        $params[] = $data->id_partida;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Partida atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}