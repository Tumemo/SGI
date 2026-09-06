<?php

declare (strict_types=1);
require_once dirname(__DIR__) . '/config/db.php';
use App\Modules\Competicoes\Application\PlacarInvalidoException;
use App\Modules\Competicoes\Application\PlacarService;
header('Content-Type: application/json; charset=utf-8');
$placarService = new PlacarService();
$data = json_decode(file_get_contents('php://input') ?: '{}');
if (!isset($data->id_jogo, $data->resultados)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes.'], JSON_UNESCAPED_UNICODE);
    exit;
}
\App\Modules\Acesso\Presentation\Http\LegacyAccess::requerOperacaoJogo();
\App\Modules\Acesso\Presentation\Http\LegacyAccess::garantirInterclasseAtivo($conn);
$respostaAnterior = \App\Modules\Sincronizacao\Presentation\Http\IdempotencyResponder::buscarRespostaIdempotente($conn, 'lancar_resultado');
if ($respostaAnterior !== null) {
    http_response_code($respostaAnterior['status']);
    echo json_encode($respostaAnterior['payload'], JSON_UNESCAPED_UNICODE);
    exit;
}
$idJogo = (int) $data->id_jogo;
$nomeJogo = $data->nome_jogo ?? null;
$idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;
\App\Shared\Database\Transaction::begin($conn);
try {
    // Resolução de partidas derivadas geradas offline com ID temporário negativo
    if ($idJogo <= 0) {
        if ($idModalidade <= 0 && !empty($data->resultados) && is_array($data->resultados)) {
            $firstEq = (int) ($data->resultados[0]->id_equipe ?? 0);
            if ($firstEq > 0) {
                $stMod = $conn->prepare("SELECT modalidades_id_modalidade FROM equipes WHERE id_equipe = ? LIMIT 1");
                $stMod->bind_param('i', $firstEq);
                $stMod->execute();
                $rowMod = $stMod->get_result()->fetch_assoc();
                $stMod->close();
                if ($rowMod && !empty($rowMod['modalidades_id_modalidade'])) {
                    $idModalidade = (int) $rowMod['modalidades_id_modalidade'];
                }
            }
        }
        if (!empty($nomeJogo) && $idModalidade > 0) {
            $jogoReal = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, (string) $nomeJogo);
            if ($jogoReal) {
                $idJogo = (int) $jogoReal['id_jogo'];
            }
        }
        if ($idJogo <= 0 && !empty($data->resultados) && is_array($data->resultados)) {
            $equipesIds = array_values(array_filter(array_map(static fn($r) => (int) ($r->id_equipe ?? 0), $data->resultados), static fn($id) => $id > 0));
            if (count($equipesIds) >= 2) {
                $eq1 = $equipesIds[0];
                $eq2 = $equipesIds[1];
                $stMatch = $conn->prepare("SELECT p1.jogos_id_jogo FROM partidas p1\n                     INNER JOIN partidas p2 ON p1.jogos_id_jogo = p2.jogos_id_jogo\n                     WHERE p1.equipes_id_equipe = ? AND p2.equipes_id_equipe = ?\n                     ORDER BY p1.jogos_id_jogo DESC LIMIT 1");
                $stMatch->bind_param('ii', $eq1, $eq2);
                $stMatch->execute();
                $rowMatch = $stMatch->get_result()->fetch_assoc();
                $stMatch->close();
                if ($rowMatch) {
                    $idJogo = (int) $rowMatch['jogos_id_jogo'];
                }
            }
        }
        // Se o jogo ainda não foi materializado no MySQL pela rodada anterior, cria-o sob demanda
        if ($idJogo <= 0 && !empty($nomeJogo) && $idModalidade > 0) {
            $idLocal = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::resolverIdLocal($conn);
            $stCreate = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\n                 VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
            $stCreate->bind_param('sii', $nomeJogo, $idModalidade, $idLocal);
            if ($stCreate->execute()) {
                $idJogo = (int) $conn->insert_id;
            }
            $stCreate->close();
        }
        if ($idJogo <= 0) {
            throw new RuntimeException("Não foi possível identificar o jogo no servidor para o ID provisório {$data->id_jogo}.");
        }
    }
    $stStatusAtual = $conn->prepare("SELECT status_jogo FROM jogos WHERE id_jogo = ?");
    $stStatusAtual->bind_param('i', $idJogo);
    $stStatusAtual->execute();
    $statusAtual = $stStatusAtual->get_result()->fetch_assoc();
    $stStatusAtual->close();
    if (!$statusAtual) {
        throw new RuntimeException('Jogo não encontrado.');
    }
    $jaConcluido = $statusAtual && ($statusAtual['status_jogo'] === 'Concluido' || $statusAtual['status_jogo'] === 'Finalizado');
    $winnerAntigo = null;
    if ($jaConcluido) {
        $partidasAntigas = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
        $winnerAntigo = \App\Modules\Competicoes\Domain\ChaveamentoRules::vencedorDePartidas($partidasAntigas);
    }
    foreach ($data->resultados as $res) {
        $idEquipe = (int) $res->id_equipe;
        $gols = (int) $res->gols;
        if ($idEquipe <= 0) {
            continue;
        }
        // Garante que a partida exista no jogo para a equipe
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idJogo, $idEquipe);
        $stmt = $conn->prepare('UPDATE partidas SET resultado_partida = ? WHERE jogos_id_jogo = ? AND equipes_id_equipe = ?');
        $stmt->bind_param('iii', $gols, $idJogo, $idEquipe);
        $stmt->execute();
        $stmt->close();
    }
    if (!$jaConcluido) {
        $golsArray = [];
        foreach ($data->resultados as $res) {
            $g = (int) $res->gols;
            $golsArray[] = $g;
        }
        try {
            $placarService->validarFinalizacao($golsArray);
        } catch (PlacarInvalidoException $e) {
            \App\Shared\Database\Transaction::rollback($conn);
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmtStatus = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
        $stmtStatus->bind_param('i', $idJogo);
        $stmtStatus->execute();
        $stmtStatus->close();
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoProcessarAvanco($conn, $idJogo);
        $stF = $conn->prepare('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
        $stF->bind_param('i', $idJogo);
        $stF->execute();
        $jogoInfo = $stF->get_result()->fetch_assoc();
        $stF->close();
        if ($jogoInfo) {
            $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($jogoInfo['nome_jogo'] ?? '');
            $idModalidade = (int) $jogoInfo['modalidades_id_modalidade'];
            $stM = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
            $stM->bind_param('i', $idModalidade);
            $stM->execute();
            $modRow = $stM->get_result()->fetch_assoc();
            $stM->close();
            if ($modRow) {
                $idInter = (int) $modRow['interclasses_id_interclasse'];
                $stI = $conn->prepare('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ? LIMIT 1');
                $stI->bind_param('i', $idInter);
                $stI->execute();
                $ptRow = $stI->get_result()->fetch_assoc();
                $stI->close();
                if ($ptRow) {
                    $p1 = (int) ($ptRow['ponto_1_lugar'] ?? 0);
                    $p2 = (int) ($ptRow['ponto_2_lugar'] ?? 0);
                    $p3 = (int) ($ptRow['ponto_3_lugar'] ?? 0);
                    $partidas = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
                    // Pontos de pódio só são aplicados em jogos decisivos:
                    //  - Grande Final (MM:2): vencedor = 1º lugar, perdedor = 2º lugar
                    //  - Disputa de 3º lugar (POS:3): vencedor = 3º lugar
                    // Semifinais, quartas e oitavas não pontuam pódio. A
                    // grande final (MM:2) já é o registro terminal; não há
                    // uma partida operacional separada para o campeão.
                    if ($meta !== null && count($partidas) >= 2) {
                        usort($partidas, static fn($a, $b) => $b['resultado_partida'] <=> $a['resultado_partida']);
                        $vencedorEquipe = (int) $partidas[0]['equipes_id_equipe'];
                        if (isset($meta['posicao'])) {
                            if ((int) $meta['posicao'] === 3 && $p3 > 0) {
                                $stT = $conn->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                                     WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1');
                                $stT->bind_param('ii', $p3, $vencedorEquipe);
                                $stT->execute();
                                $stT->close();
                            }
                        } elseif ($meta['largura'] === 2) {
                            $perdedorEquipe = (int) $partidas[1]['equipes_id_equipe'];
                            $stW = $conn->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                                 WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1');
                            $stW->bind_param('ii', $p1, $vencedorEquipe);
                            $stW->execute();
                            $stW->close();
                            $stL = $conn->prepare('UPDATE turmas SET pontuacao_turma = pontuacao_turma + ?
                                 WHERE id_turma = (SELECT turmas_id_turma FROM equipes WHERE id_equipe = ? LIMIT 1) LIMIT 1');
                            $stL->bind_param('ii', $p2, $perdedorEquipe);
                            $stL->execute();
                            $stL->close();
                        }
                    }
                }
            }
        }
    } else {
        $golsArray2 = [];
        foreach ($data->resultados as $res) {
            $g = (int) $res->gols;
            $golsArray2[] = $g;
        }
        try {
            $placarService->validarAlteracao($golsArray2);
        } catch (PlacarInvalidoException $e) {
            \App\Shared\Database\Transaction::rollback($conn);
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $partidasNovas = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
        $winnerNovo = \App\Modules\Competicoes\Domain\ChaveamentoRules::vencedorDePartidas($partidasNovas);
        if ($winnerAntigo !== null && $winnerNovo !== null && (int) $winnerAntigo !== (int) $winnerNovo) {
            $stG = $conn->prepare('SELECT nome_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
            $stG->bind_param('i', $idJogo);
            $stG->execute();
            $jogoInfo = $stG->get_result()->fetch_assoc();
            $stG->close();
            if ($jogoInfo) {
                $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($jogoInfo['nome_jogo'] ?? '');
                $idModalidade = (int) $jogoInfo['modalidades_id_modalidade'];
                if ($meta && $meta['largura'] > 1) {
                    \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoRebuildFromRound($conn, $idModalidade, $meta['largura']);
                }
            }
        }
    }
    \App\Shared\Database\Transaction::commit($conn);
    \App\Modules\Sincronizacao\Presentation\Http\IdempotencyResponder::enviarRespostaIdempotente($conn, 'lancar_resultado', 200, ['success' => true, 'message' => 'Resultado lançado!']);
} catch (Throwable $e) {
    \App\Shared\Database\Transaction::rollback($conn);
    http_response_code($e instanceof RuntimeException && $e->getMessage() === 'Jogo não encontrado.' ? 404 : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
