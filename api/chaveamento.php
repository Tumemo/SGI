<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
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
                    p.resultado_partida,
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
                "nome_fantasia" => $row['nome_fantasia_turma'],
                "gols" => $row['resultado_partida']
            ];
        }

        echo json_encode(array_values($jogos));
        break;

 case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $id_modalidade = isset($data->id_modalidade) ? intval($data->id_modalidade) : null;

        if (!$id_modalidade) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Informe o ID da modalidade."]);
            break;
        }

        $conn->begin_transaction();

        try {
            // Verifica se já existe chaveamento para não duplicar
            $check = $conn->query("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = $id_modalidade LIMIT 1");
            if ($check->num_rows > 0) {
                throw new Exception("O chaveamento desta modalidade já foi gerado.");
            }

            // Busca equipes ativas
            $sqlEquipes = "SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = ? AND status_equipe = '1'";
            $stmtE = $conn->prepare($sqlEquipes);
            $stmtE->bind_param("i", $id_modalidade);
            $stmtE->execute();
            $resEquipes = $stmtE->get_result()->fetch_all(MYSQLI_ASSOC);

            if (count($resEquipes) < 2) {
                throw new Exception("Equipes insuficientes (mínimo 2).");
            }

            shuffle($resEquipes);

            // --- SOLUÇÃO PARA O ERRO DE FOREIGN KEY ---
            // Buscamos o ID do local diretamente da sua tabela locais
            $resLocal = $conn->query("SELECT id_local FROM locais LIMIT 1");
            $local = $resLocal->fetch_assoc();
            
            // Se a tabela estiver vazia, retornamos erro em vez de tentar usar ID 1
            if (!$local) {
                throw new Exception("Erro Crítico: Não há locais cadastrados na tabela 'locais'.");
            }
            $id_local = $local['id_local']; // No seu caso, o PHP vai pegar o valor 4

            $confrontosCriados = 0;

            for ($i = 0; $i < count($resEquipes); $i += 2) {
                if (!isset($resEquipes[$i + 1])) break; 

                $nome_jogo = "Oitavas de Final - Jogo " . ($confrontosCriados + 1);
                
                // Inserindo o jogo com o id_local validado
                $sqlNovoJogo = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) 
                                VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)";
                $stmtJ = $conn->prepare($sqlNovoJogo);
                $stmtJ->bind_param("sii", $nome_jogo, $id_modalidade, $id_local);
                $stmtJ->execute();
                $id_jogo = $conn->insert_id;

                $sqlPartida = "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 0, '1')";
                $stmtP = $conn->prepare($sqlPartida);
                
                $stmtP->bind_param("ii", $id_jogo, $resEquipes[$i]['id_equipe']);
                $stmtP->execute();
                
                $stmtP->bind_param("ii", $id_jogo, $resEquipes[$i + 1]['id_equipe']);
                $stmtP->execute();

                $confrontosCriados++;
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Chaveamento gerado!", "jogos_criados" => $confrontosCriados]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}