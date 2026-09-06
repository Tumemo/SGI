<?php

declare (strict_types=1);
require_once '../config/db.php';
use App\Modules\Competicoes\Application\PartidaService;
use App\Modules\Competicoes\Application\PlacarInvalidoException;
use App\Modules\Competicoes\Application\PlacarService;
use App\Modules\Competicoes\Infrastructure\MysqliPartidaRepository;
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$partidaService = new PartidaService(new MysqliPartidaRepository($conn));
$placarService = new PlacarService();
\App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0, 1, 2, 3]);
switch ($method) {
    case 'GET':
        $filtro = \App\Shared\Database\SqlFilters::aplicarFiltrosPartidas($_GET);
        // SQL robusto que traz os nomes das equipes e turmas envolvidas
        $sql = "SELECT\n                    p.id_partida,\n                    p.equipes_id_equipe,\n                    p.resultado_partida,\n                    j.id_jogo,\n                    j.nome_jogo,\n                    j.status_jogo,\n                    j.data_jogo,\n                    j.inicio_jogo,\n                    j.termino_jogo,\n                    j.modalidades_id_modalidade,\n                    t.id_turma,\n                    t.nome_turma,\n                    t.nome_fantasia_turma,\n                    m.nome_modalidade,\n                    m.categorias_id_categoria,\n                    c.nome_categoria,\n                    l.nome_local\n                FROM partidas p\n                INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo\n                INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe\n                INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma\n                INNER JOIN modalidades m ON j.modalidades_id_modalidade = m.id_modalidade\n                INNER JOIN categorias c ON c.id_categoria = m.categorias_id_categoria\n                LEFT JOIN locais l ON l.id_local = j.locais_id_local\n                WHERE 1=1" . $filtro['sql'];
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
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerOperacaoJogo();
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::garantirInterclasseAtivo($conn);
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id_partida) || !is_numeric($data->id_partida) || (int) $data->id_partida <= 0) {
            echo json_encode(["success" => true, "offline" => true, "message" => "Partida temporária sincronizada"]);
            break;
        }
        if (!isset($data->resultado_final)) {
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
                $partidasAntigasPost = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogoPart);
                $winnerAntigoPost = \App\Modules\Competicoes\Domain\ChaveamentoRules::vencedorDePartidas($partidasAntigasPost);
            }
            // 3. Atualiza o placar desta partida específica
            $sqlPlacar = "UPDATE partidas SET resultado_partida = ? WHERE id_partida = ?";
            $stmt1 = $conn->prepare($sqlPlacar);
            $stmt1->bind_param("ii", $golsPost, $idPartidaPost);
            $stmt1->execute();
            $stmt1->close();
            // 4. Validações (espelham lancar_resultado.php)
            $partidasJogoPost = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogoPart);
            $totalGolsPost = 0;
            $golsArrayPost = [];
            foreach ($partidasJogoPost as $pj) {
                $totalGolsPost += $pj['resultado_partida'];
                $golsArrayPost[] = $pj['resultado_partida'];
            }
            if (!$jaConcluidoPost) {
                try {
                    $placarService->validarFinalizacao($golsArrayPost);
                } catch (PlacarInvalidoException $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                // Primeira finalização: marca Concluido e avança o chaveamento
                $stmtStatusUpd = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
                $stmtStatusUpd->bind_param('i', $idJogoPart);
                $stmtStatusUpd->execute();
                $stmtStatusUpd->close();
                \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoProcessarAvanco($conn, $idJogoPart);
            } else {
                // Já estava concluído: valida novo placar e detecta mudança de vencedor
                try {
                    $placarService->validarAlteracao($golsArrayPost);
                } catch (PlacarInvalidoException $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $winnerNovoPost = \App\Modules\Competicoes\Domain\ChaveamentoRules::vencedorDePartidas($partidasJogoPost);
                if ($winnerAntigoPost !== null && $winnerNovoPost !== null && (int) $winnerAntigoPost !== (int) $winnerNovoPost) {
                    $stGPost = $conn->prepare('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
                    $stGPost->bind_param('i', $idJogoPart);
                    $stGPost->execute();
                    $jogoInfoPost = $stGPost->get_result()->fetch_assoc();
                    $stGPost->close();
                    if ($jogoInfoPost) {
                        $metaPost = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($jogoInfoPost['nome_jogo'] ?? '');
                        $idModalidadePost = (int) $jogoInfoPost['modalidades_id_modalidade'];
                        if ($metaPost && $metaPost['largura'] > 1) {
                            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoRebuildFromRound($conn, $idModalidadePost, $metaPost['largura']);
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
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerOperacaoJogo();
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::garantirInterclasseAtivo($conn);
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id_partida) || !is_numeric($data->id_partida) || (int) $data->id_partida <= 0) {
            echo json_encode(["success" => true, "offline" => true, "message" => "Partida temporária sincronizada"]);
            break;
        }
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($payload)) {
            $payload = [];
        }
        try {
            $partidaService->atualizar($payload);
            echo json_encode(["success" => true, "message" => "Partida atualizada com sucesso!"]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}
