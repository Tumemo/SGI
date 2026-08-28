<?php
require_once '../config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';
require_once 'filtros.php';
require_once 'auth.php';
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
                    j.data_jogo,
                    j.inicio_jogo,
                    j.termino_jogo,
                    j.modalidades_id_modalidade,
                    t.id_turma,
                    t.nome_turma,
                    t.nome_fantasia_turma,
                    m.nome_modalidade,
                    m.categorias_id_categoria,
                    c.nome_categoria,
                    l.nome_local
                FROM partidas p
                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
                INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade
                INNER JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
                LEFT JOIN locais l ON l.id_local = j.locais_id_local
                WHERE 1=1" . $filtro['sql'];

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            break;
        }

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            break;
        }
        $res = $stmt->get_result();
        if (!$res) {
            echo json_encode(["success" => false, "message" => "Erro ao obter resultados."]);
            break;
        }
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

case 'POST':
        // Permite Master, Admin e Mesário (níveis 0, 1 e 2)
        requerOperacaoJogo();
        garantirInterclasseAtivo($conn);
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_partida, $data->resultado_final)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $idPartidaPost = (int) $data->id_partida;
        $golsPost = (int) $data->resultado_final;

        $conn->begin_transaction();

        try {
            // 1. Descobre o jogo associado a esta partida
            $stJ = $conn->prepare('SELECT jogos_id_jogo FROM partidas WHERE id_partida = ? LIMIT 1');
            $stJ->bind_param('i', $idPartidaPost);
            $stJ->execute();
            $rowJ = $stJ->get_result()->fetch_assoc();
            $stJ->close();
            $idJogoPart = (int) ($rowJ['jogos_id_jogo'] ?? 0);

            if ($idJogoPart <= 0) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Partida não associada a nenhum jogo."]);
                break;
            }

            // 2. Verifica se o jogo já estava concluído (para detectar mudança de vencedor)
            $stStatus = $conn->prepare("SELECT status_jogo FROM jogos WHERE id_jogo = ?");
            $stStatus->bind_param('i', $idJogoPart);
            $stStatus->execute();
            $rowStatus = $stStatus->get_result()->fetch_assoc();
            $stStatus->close();
            $jaConcluidoPost = $rowStatus && ($rowStatus['status_jogo'] === 'Concluido' || $rowStatus['status_jogo'] === 'Finalizado');

            $winnerAntigoPost = null;
            if ($jaConcluidoPost) {
                $partidasAntigasPost = sgi_mm_carregar_partidas_jogo($conn, $idJogoPart);
                $winnerAntigoPost = sgi_mm_vencedor_de_partidas($partidasAntigasPost);
            }

            // 3. Atualiza o placar desta partida específica
            $sqlPlacar = "UPDATE partidas SET resultado_partida = ? WHERE id_partida = ?";
            $stmt1 = $conn->prepare($sqlPlacar);
            $stmt1->bind_param("ii", $golsPost, $idPartidaPost);
            $stmt1->execute();
            $stmt1->close();

            // 4. Validações (espelham lancar_resultado.php)
            $partidasJogoPost = sgi_mm_carregar_partidas_jogo($conn, $idJogoPart);
            $totalGolsPost = 0;
            $golsArrayPost = [];
            foreach ($partidasJogoPost as $pj) {
                $totalGolsPost += $pj['resultado_partida'];
                $golsArrayPost[] = $pj['resultado_partida'];
            }

            if (!$jaConcluidoPost) {
                if ($totalGolsPost === 0) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Não é possível finalizar um jogo com placar 0x0. Registre o placar correto.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (count($golsArrayPost) >= 2 && $golsArrayPost[0] === $golsArrayPost[1]) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'O jogo não pode terminar empatado! Registre o placar correto.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Primeira finalização: marca Concluido e avança o chaveamento
                $stmtStatusUpd = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
                $stmtStatusUpd->bind_param('i', $idJogoPart);
                $stmtStatusUpd->execute();
                $stmtStatusUpd->close();

                sgi_chaveamento_processar_avanco($conn, $idJogoPart);
            } else {
                // Já estava concluído: valida novo placar e detecta mudança de vencedor
                if ($totalGolsPost === 0) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Não é possível alterar o placar de um jogo finalizado para 0x0.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (count($golsArrayPost) >= 2 && $golsArrayPost[0] === $golsArrayPost[1]) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'O jogo não pode terminar empatado! Registre o placar correto.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $winnerNovoPost = sgi_mm_vencedor_de_partidas($partidasJogoPost);

                if ($winnerAntigoPost !== null && $winnerNovoPost !== null && (int) $winnerAntigoPost !== (int) $winnerNovoPost) {
                    $stGPost = $conn->prepare('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
                    $stGPost->bind_param('i', $idJogoPart);
                    $stGPost->execute();
                    $jogoInfoPost = $stGPost->get_result()->fetch_assoc();
                    $stGPost->close();

                    if ($jogoInfoPost) {
                        $metaPost = sgi_mm_parse($jogoInfoPost['nome_jogo'] ?? '');
                        $idModalidadePost = (int) $jogoInfoPost['modalidades_id_modalidade'];
                        if ($metaPost && $metaPost['largura'] > 1) {
                            sgi_chaveamento_rebuild_from_round($conn, $idModalidadePost, $metaPost['largura']);
                        }
                    }
                }
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Resultado salvo e jogo finalizado!"]);
        } catch (Throwable $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao processar: " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Permite Master, Admin e Mesário (níveis 0, 1 e 2)
        requerOperacaoJogo();
        garantirInterclasseAtivo($conn);
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
        if (isset($data->status_partida)) {
            $campos[] = "status_partida = ?";
            $params[] = $data->status_partida;
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