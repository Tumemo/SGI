<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retorna o chaveamento existente de uma modalidade
        $id_modalidade = isset($_GET['id_modalidade']) ? intval($_GET['id_modalidade']) : null;

        if (!$id_modalidade) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da modalidade é obrigatório."]);
            break;
        }

        $sql = "SELECT 
                    j.id_jogo, 
                    j.nome_jogo, 
                    j.status_jogo,
                    p.id_partida,
                    e.id_equipe,
                    t.nome_turma,
                    t.nome_fantasia_turma
                FROM jogos j
                INNER JOIN partidas p ON j.id_jogo = p.jogos_id_jogo
                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                WHERE j.modalidades_id_modalidade = ?
                ORDER BY j.id_jogo ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_modalidade);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $jogos = [];
        while ($row = $result->fetch_assoc()) {
            $id_jogo = $row['id_jogo'];
            if (!isset($jogos[$id_jogo])) {
                $jogos[$id_jogo] = [
                    "id_jogo" => $id_jogo,
                    "nome_jogo" => $row['nome_jogo'],
                    "status" => $row['status_jogo'],
                    "equipes" => []
                ];
            }
            $jogos[$id_jogo]['equipes'][] = [
                "id_equipe" => $row['id_equipe'],
                "nome_turma" => $row['nome_turma'],
                "nome_fantasia" => $row['nome_fantasia_turma']
            ];
        }

        echo json_encode(array_values($jogos));
        break;

    case 'POST':
        // Gera um novo chaveamento aleatório
        $data = json_decode(file_get_contents("php://input"));
        $id_modalidade = isset($data->id_modalidade) ? intval($data->id_modalidade) : null;

        if (!$id_modalidade) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Informe o ID da modalidade para gerar o chaveamento."]);
            break;
        }

        $conn->begin_transaction();

        try {
            // 1. Buscar equipes da modalidade
            $sqlEquipes = "SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = ? AND status_equipe = '1'";
            $stmtE = $conn->prepare($sqlEquipes);
            $stmtE->bind_param("i", $id_modalidade);
            $stmtE->execute();
            $resEquipes = $stmtE->get_result()->fetch_all(MYSQLI_ASSOC);

            if (count($resEquipes) < 2) {
                throw new Exception("Não há equipes suficientes (mínimo 2) para gerar um chaveamento.");
            }

            // 2. Embaralhar equipes
            shuffle($resEquipes);

            // 3. Buscar um local padrão (Obrigatório no seu SQL)
            $resLocal = $conn->query("SELECT id_local FROM locais WHERE status_local = '1' LIMIT 1");
            $local = $resLocal->fetch_assoc();
            $id_local = $local ? $local['id_local'] : 1;

            $confrontosCriados = 0;

            // 4. Criar jogos e partidas (Pares)
            for ($i = 0; $i < count($resEquipes); $i += 2) {
                // Se for ímpar, a última equipe sobra (Lógica de "Bye")
                if (!isset($resEquipes[$i + 1])) {
                    break; 
                }

                $nome_jogo = "Confronto " . ($confrontosCriados + 1);
                
                // Inserir Jogo
                $sqlNovoJogo = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) 
                                VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)";
                $stmtJ = $conn->prepare($sqlNovoJogo);
                $stmtJ->bind_param("sii", $nome_jogo, $id_modalidade, $id_local);
                $stmtJ->execute();
                $id_jogo = $conn->insert_id;

                // Inserir Partidas (Vínculo das 2 equipes)
                $sqlPartida = "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 0, '1')";
                $stmtP = $conn->prepare($sqlPartida);
                
                // Equipe A
                $stmtP->bind_param("ii", $id_jogo, $resEquipes[$i]['id_equipe']);
                $stmtP->execute();
                
                // Equipe B
                $stmtP->bind_param("ii", $id_jogo, $resEquipes[$i + 1]['id_equipe']);
                $stmtP->execute();

                $confrontosCriados++;
            }

            $conn->commit();
            echo json_encode([
                "success" => true, 
                "message" => "Chaveamento gerado com sucesso!", 
                "jogos_criados" => $confrontosCriados
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}