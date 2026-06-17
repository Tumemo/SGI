<?php
require_once '../config/db.php';
require_once 'filtros.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['acao']) && $_GET['acao'] === 'listar_atletas') {
            $idJogo = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : 0;
            $idTurma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : 0;
            if ($idJogo <= 0 || $idTurma <= 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "id_jogo e id_turma são obrigatórios."]);
                break;
            }
            $likeJogo = '%[JOGO:' . $idJogo . ']%';
            $sql = "SELECT DISTINCT u.id_usuario, u.nome_usuario, u.matricula_usuario
                    FROM usuarios u
                    INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario
                    INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe
                    INNER JOIN partidas p ON p.equipes_id_equipe = e.id_equipe
                    WHERE p.jogos_id_jogo = ? AND e.turmas_id_turma = ?
                      AND u.status_usuario = '1' AND u.nivel_usuario = '3'
                      AND u.id_usuario NOT IN (
                        SELECT o2.usuarios_id_usuario
                        FROM ocorrencias o2
                        WHERE o2.titulo_ocorrencia = 'Suspensao'
                          AND o2.status_ocorrencia = '1'
                      )
                      AND u.id_usuario NOT IN (
                        SELECT o3.usuarios_id_usuario
                        FROM ocorrencias o3
                        WHERE o3.titulo_ocorrencia = 'Vermelho'
                          AND o3.descricao_ocorrencia LIKE ?
                          AND o3.status_ocorrencia = '1'
                      )
                    ORDER BY u.nome_usuario ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $idJogo, $idTurma, $likeJogo);
            $stmt->execute();
            $res = $stmt->get_result();
            echo json_encode(["success" => true, "atletas" => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        $filtro = aplicarFiltrosOcorrencias();

        $sql = "SELECT 
                    ocorrencias.id_ocorrencia, 
                    ocorrencias.titulo_ocorrencia, 
                    ocorrencias.descricao_ocorrencia, 
                    ocorrencias.data_ocorrencia, 
                    ocorrencias.hora_ocorrencia, 
                    ocorrencias.penalidade,
                    usuarios.nome_usuario,
                    usuarios.id_usuario
                FROM ocorrencias 
                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
                WHERE 1=1" . $filtro['sql'];

        if (!empty($_GET['id_jogo'])) {
            $buscaJogo = '%[JOGO:' . intval($_GET['id_jogo']) . ']%';
            $sql .= " AND ocorrencias.descricao_ocorrencia LIKE ?";
            if (!empty($filtro['params'])) {
                $filtro['types'] .= 's';
                $filtro['params'][] = $buscaJogo;
            } else {
                $filtro['types'] = 's';
                $filtro['params'] = [$buscaJogo];
            }
        }

        $sql .= " ORDER BY ocorrencias.data_ocorrencia DESC, ocorrencias.hora_ocorrencia DESC";

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

        if (!isset($data->titulo_ocorrencia, $data->descricao_ocorrencia, $data->data_ocorrencia, $data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $descricao = $data->descricao_ocorrencia;
        $idJogo = isset($data->id_jogo) ? intval($data->id_jogo) : 0;
        $idTurma = isset($data->id_turma) ? intval($data->id_turma) : 0;
        if ($idJogo > 0) {
            $descricao = '[JOGO:' . $idJogo . ']' . ($idTurma > 0 ? '[TURMA:' . $idTurma . ']' : '') . $data->descricao_ocorrencia;
        }

        $sql = "INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia, usuarios_id_usuario, penalidade) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $data->titulo_ocorrencia,
            $descricao,
            $data->data_ocorrencia,
            $data->usuarios_id_usuario,
            $penalidade
        );

        if ($stmt->execute()) {
            $evento = null;

            // Detecta segundo cartão amarelo do mesmo aluno nesta partida
            if ($data->titulo_ocorrencia === 'Amarelo' && $idJogo > 0) {
                $likePattern = '%[JOGO:' . $idJogo . ']%';
                $checkSql = "SELECT COUNT(*) as total FROM ocorrencias 
                             WHERE titulo_ocorrencia = 'Amarelo' 
                               AND usuarios_id_usuario = ? 
                               AND descricao_ocorrencia LIKE ? 
                               AND status_ocorrencia = '1'";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("is", $data->usuarios_id_usuario, $likePattern);
                $checkStmt->execute();
                $countResult = $checkStmt->get_result()->fetch_assoc();

                if ((int)$countResult['total'] >= 2) {
                    $evento = 'segundo_amarelo';

                    // Registra automaticamente o Cartão Vermelho
                    $redDesc = '[JOGO:' . $idJogo . ']' . ($idTurma > 0 ? '[TURMA:' . $idTurma . ']' : '')
                               . 'Segundo cartão amarelo — expulso automático';
                    $insertRed = "INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia, usuarios_id_usuario, penalidade) 
                                  VALUES ('Vermelho', ?, ?, ?, 1)";
                    $redStmt = $conn->prepare($insertRed);
                    $redStmt->bind_param("ssi", $redDesc, $data->data_ocorrencia, $data->usuarios_id_usuario);
                    $redStmt->execute();
                }
            }

            $response = ["success" => true, "message" => "Ocorrência registrada com sucesso!"];
            if ($evento) {
                $response['evento'] = $evento;
            }
            http_response_code(201);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        // Apenas o ID é estritamente obrigatório para localizar o registro
        if (!isset($data->id_ocorrencia)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da ocorrência é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        // Verificação dinâmica de cada campo
        if (isset($data->titulo_ocorrencia)) {
            $campos[] = "titulo_ocorrencia = ?";
            $params[] = $data->titulo_ocorrencia;
            $types .= "s"; // string
        }

        if (isset($data->descricao_ocorrencia)) {
            $campos[] = "descricao_ocorrencia = ?";
            $params[] = $data->descricao_ocorrencia;
            $types .= "s"; 
        }
        if (isset($data->status_ocorrencia)) {
            $campos[] = "status_ocorrencia = ?";
            $params[] = $data->status_ocorrencia;
            $types .= "s"; 
        }

        if (isset($data->penalidade)) {
            $campos[] = "penalidade = ?";
            $params[] = $data->penalidade;
            $types .= "i"; 
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado fornecido para atualização."]);
            break;
        }

        $sql = "UPDATE ocorrencias SET " . implode(", ", $campos) . " WHERE id_ocorrencia = ?";
        

        $params[] = $data->id_ocorrencia;
        $types .= "i";

        $stmt = $conn->prepare($sql);
    
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao atualizar: " . $conn->error]);
        }
        break;
}
