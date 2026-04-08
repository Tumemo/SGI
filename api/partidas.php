<?php
require_once '../config/db.php';
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

            // 2. Opcional: Atualiza o status do jogo para 'Finalizado'
            // Buscamos o ID do jogo através da partida
            $sqlStatus = "UPDATE jogos SET status_jogo = 'Finalizado' 
                          WHERE id_jogo = (SELECT jogos_id_jogo FROM partidas WHERE id_partida = ? LIMIT 1)";
            $stmt2 = $conn->prepare($sqlStatus);
            $stmt2->bind_param("i", $data->id_partida);
            $stmt2->execute();

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Resultado salvo e jogo finalizado!"]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao processar: " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Pode ser usado para trocar uma equipe de uma partida antes do jogo começar
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}