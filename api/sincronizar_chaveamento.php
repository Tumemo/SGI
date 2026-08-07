<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/mata_mata_engine.php';
require_once __DIR__ . '/includes/individual_engine.php';

header('Content-Type: application/json; charset=utf-8');

// Permite apenas o método POST para sincronização
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Utilize POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input ?: '{}');

$idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;
$tipoModalidade = $data->tipo_modalidade ?? null;

if ($idModalidade <= 0 || !$tipoModalidade) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Informe o ID e o tipo da modalidade.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Inicia a transação SQL atômica
$conn->begin_transaction();

try {
    if ($tipoModalidade === 'individual') {
        // --- PROCESSAMENTO MODALIDADE INDIVIDUAL ---
        $ranking = $data->ranking ?? null;

        if (!$ranking || !isset($ranking->primeiro, $ranking->segundo, $ranking->terceiro)) {
            throw new RuntimeException('Dados de ranking incompletos para modalidade individual.');
        }

        // Salva o ranking e aplica pontuações
        $resultado = sgi_ind_salvar_ranking($conn, $idModalidade, [
            'primeiro' => (int) $ranking->primeiro,
            'segundo' => (int) $ranking->segundo,
            'terceiro' => (int) $ranking->terceiro,
        ]);

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Sincronização de modalidade individual concluída com sucesso.',
            'detalhes' => $resultado,
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($tipoModalidade === 'mata_mata') {
        // --- PROCESSAMENTO MODALIDADE MATA-MATA ---
        $jogos = $data->jogos ?? [];

        if (empty($jogos) || !is_array($jogos)) {
            throw new RuntimeException('Nenhum jogo enviado para sincronização de mata-mata.');
        }

        $idLocal = sgi_mm_resolver_id_local($conn);
        $jogosProcessados = 0;

        foreach ($jogos as $j) {
            $nomeJogo = $j->nome_jogo ?? '';
            $statusJogo = $j->status_jogo ?? 'Agendado';
            $partidas = $j->partidas ?? [];

            if (empty($nomeJogo)) {
                continue;
            }

            // Verifica se o jogo já existe no banco
            $jogoExistente = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $nomeJogo);

            if ($jogoExistente) {
                $idJogo = (int) $jogoExistente['id_jogo'];
                // Atualiza status do jogo existente
                $stUpd = $conn->prepare("UPDATE jogos SET status_jogo = ? WHERE id_jogo = ?");
                $stUpd->bind_param('si', $statusJogo, $idJogo);
                $stUpd->execute();
                $stUpd->close();
            } else {
                // Insere novo jogo
                $stIns = $conn->prepare(
                    "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
                     VALUES (?, CURDATE(), '08:00:00', ?, ?, ?)"
                );
                $stIns->bind_param('ssii', $nomeJogo, $statusJogo, $idModalidade, $idLocal);
                $stIns->execute();
                $idJogo = (int) $conn->insert_id;
                $stIns->close();
            }

            // Atualiza/Insere partidas associadas ao jogo
            foreach ($partidas as $p) {
                $idEquipe = (int) ($p->id_equipe ?? 0);
                $resultadoPartida = (int) ($p->resultado ?? 0);

                if ($idEquipe <= 0) {
                    continue;
                }

                // Verifica se a partida/equipe já está associada a esse jogo
                $stCheck = $conn->prepare("SELECT id_partida FROM partidas WHERE jogos_id_jogo = ? AND equipes_id_equipe = ? LIMIT 1");
                $stCheck->bind_param('ii', $idJogo, $idEquipe);
                $stCheck->execute();
                $partidaExistente = $stCheck->get_result()->fetch_assoc();
                $stCheck->close();

                if ($partidaExistente) {
                    $stPUpd = $conn->prepare("UPDATE partidas SET resultado_partida = ? WHERE id_partida = ?");
                    $idPartida = (int) $partidaExistente['id_partida'];
                    $stPUpd->bind_param('ii', $resultadoPartida, $idPartida);
                    $stPUpd->execute();
                    $stPUpd->close();
                } else {
                    $stPIns = $conn->prepare(
                        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, ?, '1')"
                    );
                    $stPIns->bind_param('iii', $idJogo, $idEquipe, $resultadoPartida);
                    $stPIns->execute();
                    $stPIns->close();
                }
            }

            $jogosProcessados++;
        }

        // Confirma a transação
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Sincronização de chaveamento Mata-Mata realizada com sucesso.',
            'jogos_sincronizados' => $jogosProcessados,
        ], JSON_UNESCAPED_UNICODE);

    } else {
        throw new RuntimeException('Tipo de modalidade não suportado.');
    }

} catch (Throwable $e) {
    // Caso ocorra qualquer erro, desfaz todas as alterações
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erro durante a sincronização: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}