<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

$idTurma = isset($_GET['id_turma']) ? (int) $_GET['id_turma'] : 0;
$idInter = isset($_GET['id_interclasse']) ? (int) $_GET['id_interclasse'] : 0;

if ($idTurma <= 0 || $idInter <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_turma e id_interclasse são obrigatórios.']);
    exit;
}

function sgi_hist_q(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $st = $conn->prepare($sql);
    if (!$st) {
        throw new RuntimeException('Erro SQL: ' . $conn->error);
    }
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $st->close();
    return $rows;
}

/** Espelha a lógica de parse do mata_mata_engine.php */
function sgi_hist_parse(?string $nome): ?array
{
    if (!is_string($nome)) {
        return null;
    }
    if (preg_match('/^MM:(\d+):(\d+):([NB])$/', $nome, $m)) {
        return ['largura' => (int) $m[1], 'slot' => (int) $m[2], 'kind' => $m[3], 'posicao' => null];
    }
    if (preg_match('/^POS:(\d+):(\d+):([NB])$/', $nome, $m)) {
        return ['largura' => 0, 'slot' => (int) $m[2], 'kind' => $m[3], 'posicao' => (int) $m[1]];
    }
    return null;
}

function sgi_hist_nome_fase(?int $largura): ?string
{
    if ($largura === null) {
        return null;
    }
    return match ($largura) {
        16 => 'Oitavas de final',
        8 => 'Quartas de final',
        4 => 'Semifinal',
        2 => 'Final',
        1 => 'Campeão',
        default => 'Fase ' . $largura,
    };
}

try {
    $turmaRows = sgi_hist_q(
        $conn,
        "SELECT t.*, c.nome_categoria
         FROM turmas t
         INNER JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
         WHERE t.id_turma = ? AND t.interclasses_id_interclasse = ? LIMIT 1",
        'ii',
        [$idTurma, $idInter]
    );
    if (!$turmaRows) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Turma não encontrada nesta edição.']);
        exit;
    }
    $turma = $turmaRows[0];

    $interRows = sgi_hist_q($conn, "SELECT * FROM interclasses WHERE id_interclasse = ? LIMIT 1", 'i', [$idInter]);
    $inter = $interRows[0] ?? [];

    $podio = [
        1 => (int) ($inter['ponto_1_lugar'] ?? 0),
        2 => (int) ($inter['ponto_2_lugar'] ?? 0),
        3 => (int) ($inter['ponto_3_lugar'] ?? 0),
    ];

    /* -------------------- ARRECADAÇÃO -------------------- */
    $arrecRows = sgi_hist_q(
        $conn,
        "SELECT h.*, u.nome_usuario AS registrado_por_nome
         FROM historico_arrecadacoes h
         LEFT JOIN usuarios u ON u.id_usuario = h.registrado_por
         WHERE h.id_turma = ? AND h.id_interclasse = ? AND h.status_historico = '1'
         ORDER BY h.data_registro DESC, h.id_historico DESC",
        'ii',
        [$idTurma, $idInter]
    );

    $arrecItens = 0.0;
    $arrecPontos = 0;
    $arrec = [];
    foreach ($arrecRows as $r) {
        $arrecItens += (float) $r['quantidade'];
        $arrecPontos += (int) $r['pontos_adicionados'];
        $arrec[] = [
            'id'             => (int) $r['id_historico'],
            'quantidade'     => (float) $r['quantidade'],
            'pontos'         => (int) $r['pontos_adicionados'],
            'data'           => $r['data_registro'],
            'registrado_por' => $r['registrado_por_nome'] ?? 'Sistema',
        ];
    }

    /* -------------------- ESPORTES -------------------- */
    $mods = sgi_hist_q(
        $conn,
        "SELECT m.id_modalidade, m.nome_modalidade, m.genero_modalidade,
                tm.nome_tipo_modalidade, c.nome_categoria
         FROM modalidades m
         INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
         INNER JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
         WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = '1'
         ORDER BY c.nome_categoria ASC, m.nome_modalidade ASC",
        'i',
        [$idInter]
    );

    $esportes = ['pontos_total' => 0, 'modalidades' => []];

    foreach ($mods as $mod) {
        $idMod = (int) $mod['id_modalidade'];

        $equipes = sgi_hist_q(
            $conn,
            "SELECT id_equipe, nome_equipe, status_equipe
             FROM equipes WHERE turmas_id_turma = ? AND modalidades_id_modalidade = ?",
            'ii',
            [$idTurma, $idMod]
        );
        if (!$equipes) {
            continue;
        }

        $eqIds = array_map('intval', array_column($equipes, 'id_equipe'));
        $inPlace = implode(',', array_fill(0, count($eqIds), '?'));
        $inTypes = str_repeat('i', count($eqIds));

        $entry = [
            'id_modalidade'  => $idMod,
            'nome_modalidade'=> $mod['nome_modalidade'],
            'nome_categoria' => $mod['nome_categoria'],
            'tipo'           => $mod['nome_tipo_modalidade'],
            'genero'         => $mod['genero_modalidade'],
            'colocacao'      => null,
            'pontos'         => 0,
            'itens'          => [],
            'alunos'         => [],
        ];

        $alunos = sgi_hist_q(
            $conn,
            "SELECT DISTINCT u.id_usuario, u.nome_usuario
             FROM equipes_has_usuarios ehu
             INNER JOIN usuarios u ON u.id_usuario = ehu.usuarios_id_usuario
             WHERE ehu.equipes_id_equipe IN ($inPlace)
             ORDER BY u.nome_usuario ASC",
            $inTypes,
            $eqIds
        );
        $entry['alunos'] = array_map(
            static fn (array $a): array => ['id_usuario' => (int) $a['id_usuario'], 'nome_usuario' => $a['nome_usuario']],
            $alunos
        );

        $ehIndividual = $mod['nome_tipo_modalidade'] === 'Individual';

        if ($ehIndividual) {
            /* Modalidade individual: partidas do jogo IND:{id} registram 1º/2º/3º por aluno */
            $games = sgi_hist_q(
                $conn,
                "SELECT p.resultado_partida, u.id_usuario, u.nome_usuario
                 FROM partidas p
                 INNER JOIN jogos j ON j.id_jogo = p.jogos_id_jogo
                 LEFT JOIN usuarios u ON u.id_usuario = p.usuarios_id_usuario
                 WHERE j.modalidades_id_modalidade = ? AND j.nome_jogo = ?
                   AND p.equipes_id_equipe IN ($inPlace)
                   AND p.resultado_partida BETWEEN 1 AND 3
                 ORDER BY p.resultado_partida ASC",
                'ii' . $inTypes,
                array_merge([$idMod, 'IND:' . $idMod], $eqIds)
            );

            foreach ($games as $g) {
                $pos = (int) $g['resultado_partida'];
                $pts = $podio[$pos] ?? 0;
                $entry['colocacao'] = $pos;
                $entry['pontos'] += $pts;
                $entry['itens'][] = [
                    'descricao' => $pos . 'º lugar',
                    'detalhe'   => $g['nome_usuario'] ?? '—',
                    'pontos'    => $pts,
                ];
            }
        } else {
            /* Modalidade coletiva (mata-mata): pontos espelham lancar_resultado.php */
            $gamesAll = sgi_hist_q(
                $conn,
                "SELECT j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo,
                        p.id_partida, p.equipes_id_equipe, p.resultado_partida
                 FROM jogos j
                 INNER JOIN partidas p ON p.jogos_id_jogo = j.id_jogo
                 WHERE j.modalidades_id_modalidade = ?
                   AND (j.status_jogo = 'Concluido' OR j.status_jogo = 'Finalizado')
                 ORDER BY j.id_jogo ASC, p.resultado_partida DESC",
                'i',
                [$idMod]
            );

            $eqNomes = [];
            foreach (sgi_hist_q(
                $conn,
                "SELECT e.id_equipe, e.nome_equipe, t.nome_turma, t.nome_fantasia_turma
                 FROM equipes e INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
                 WHERE e.modalidades_id_modalidade = ?",
                'i',
                [$idMod]
            ) as $ei) {
                $eqNomes[(int) $ei['id_equipe']] = $ei['nome_equipe'] ?: ($ei['nome_fantasia_turma'] ?: $ei['nome_turma']);
            }

            $porJogo = [];
            foreach ($gamesAll as $g) {
                $idJ = (int) $g['id_jogo'];
                if (!isset($porJogo[$idJ])) {
                    $meta = sgi_hist_parse($g['nome_jogo']);
                    $porJogo[$idJ] = [
                        'meta'     => $meta,
                        'nome'     => $g['nome_jogo'],
                        'data'     => $g['data_jogo'],
                        'partidas' => [],
                    ];
                }
                $porJogo[$idJ]['partidas'][] = [
                    'equipe' => (int) $g['equipes_id_equipe'],
                    'gols'   => (int) $g['resultado_partida'],
                ];
            }

            /* Colocações finais (1º, 2º, 3º...) a partir da final e disputas de posição */
            $classificacao = [];
            foreach ($porJogo as $gj) {
                $meta = $gj['meta'];
                if ($meta === null) {
                    continue;
                }
                $ps = $gj['partidas'];
                if (count($ps) < 2) {
                    continue;
                }
                usort($ps, static fn (array $a, array $b): int => $b['gols'] <=> $a['gols']);
                if ($meta['posicao'] !== null) {
                    $classificacao[$meta['posicao']]       = $ps[0]['equipe'];
                    $classificacao[$meta['posicao'] + 1]   = $ps[1]['equipe'];
                } elseif ($meta['largura'] === 2) {
                    $classificacao[1] = $ps[0]['equipe'];
                    $classificacao[2] = $ps[1]['equipe'];
                }
            }

            foreach ($eqIds as $eq) {
                foreach ($classificacao as $pos => $eqC) {
                    if ($eq === $eqC) {
                        $entry['colocacao'] = $pos;
                        break 2;
                    }
                }
            }

            /* Pontos de pódio só nos jogos decisivos:
               Grande Final (MM:2) → vencedor = ponto_1_lugar, perdedor = ponto_2_lugar
               Disputa de 3º lugar (POS:3) → vencedor = ponto_3_lugar
               Demais fases (MM:4 semis, MM:8 quartas, etc.) não pontuam pódio. */
            foreach ($porJogo as $gj) {
                $meta = $gj['meta'];
                if ($meta === null || $meta['kind'] === 'B') {
                    continue;
                }
                $ps = $gj['partidas'];
                if (count($ps) < 2) {
                    continue;
                }
                usort($ps, static fn (array $a, array $b): int => $b['gols'] <=> $a['gols']);
                $winId = $ps[0]['equipe'];
                $winGols = $ps[0]['gols'];
                $losId = $ps[1]['equipe'] ?? null;
                $losGols = $ps[1]['gols'] ?? null;

                $ptsWin = 0;
                $ptsLos = 0;
                $fase = 'Jogo';

                if ($meta['posicao'] !== null) {
                    if ((int) $meta['posicao'] === 3) {
                        $ptsWin = $podio[3] ?? 0;
                        $fase = 'Disputa de 3º lugar';
                    }
                } elseif ($meta['largura'] === 2) {
                    $ptsWin = $podio[1] ?? 0;
                    $ptsLos = $podio[2] ?? 0;
                    $fase = 'Final';
                } else {
                    continue;
                }

                if ($ptsWin <= 0) {
                    continue;
                }

                foreach ($eqIds as $eq) {
                    $ehVitoria = ($eq === $winId);
                    $ehDerrota = ($losId !== null && $eq === $losId);
                    if (!$ehVitoria && !$ehDerrota) {
                        continue;
                    }

                    $pts = $ehVitoria ? $ptsWin : $ptsLos;
                    if ($pts <= 0) {
                        continue;
                    }

                    $advId = $ehVitoria ? $losId : $winId;
                    $golsTurma = $ehVitoria ? $winGols : ($losGols ?? 0);
                    $golsAdv = $ehVitoria ? ($losGols ?? 0) : $winGols;
                    $nomeAdv = ($advId !== null) ? ($eqNomes[$advId] ?? 'adversário') : '';

                    $entry['pontos'] += $pts;
                    $entry['itens'][] = [
                        'descricao' => $ehVitoria ? 'Vitória' : 'Derrota',
                        'detalhe'   => $fase . ($nomeAdv ? ' contra ' . $nomeAdv : '') . ' · placar ' . $golsTurma . ' x ' . $golsAdv,
                        'pontos'    => $pts,
                    ];
                }
            }
        }

        if ($entry['pontos'] > 0 || $entry['colocacao'] !== null) {
            $esportes['pontos_total'] += $entry['pontos'];
            $esportes['modalidades'][] = $entry;
        }
    }

    usort($esportes['modalidades'], static fn (array $a, array $b): int => $b['pontos'] <=> $a['pontos']);

    /* -------------------- PENALIDADES -------------------- */
    $ocorTurma = sgi_hist_q(
        $conn,
        "SELECT ot.* FROM ocorrencias_turmas ot
         WHERE ot.turmas_id_turma = ?
         ORDER BY ot.data_ocorrencia DESC, ot.id_ocorrencia_turma DESC",
        'i',
        [$idTurma]
    );

    $ocorAluno = sgi_hist_q(
        $conn,
        "SELECT o.*, u.nome_usuario
         FROM ocorrencias o
         INNER JOIN usuarios u ON u.id_usuario = o.usuarios_id_usuario
         WHERE u.turmas_id_turma = ? AND o.status_ocorrencia = '1'
         ORDER BY o.data_ocorrencia DESC, o.id_ocorrencia DESC",
        'i',
        [$idTurma]
    );

    $penalidades = ['pontos_total' => 0, 'ocorrencias' => []];
    foreach ($ocorTurma as $o) {
        $pts = (int) $o['pontos_descontados'];
        $penalidades['pontos_total'] += $pts;
        $penalidades['ocorrencias'][] = [
            'tipo'      => 'turma',
            'titulo'    => $o['titulo_ocorrencia'],
            'descricao' => $o['descricao_ocorrencia'],
            'data'      => $o['data_ocorrencia'],
            'aluno'     => null,
            'pontos'    => $pts,
        ];
    }
    foreach ($ocorAluno as $o) {
        $pts = (int) $o['penalidade'];
        $penalidades['pontos_total'] += $pts;
        $penalidades['ocorrencias'][] = [
            'tipo'      => 'aluno',
            'titulo'    => $o['titulo_ocorrencia'],
            'descricao' => $o['descricao_ocorrencia'],
            'data'      => $o['data_ocorrencia'],
            'aluno'     => $o['nome_usuario'],
            'pontos'    => $pts,
        ];
    }

    echo json_encode([
        'success' => true,
        'turma' => [
            'id_turma'             => (int) $turma['id_turma'],
            'nome_turma'           => $turma['nome_turma'],
            'nome_fantasia_turma'  => $turma['nome_fantasia_turma'],
            'turno_turma'          => $turma['turno_turma'],
            'nome_categoria'       => $turma['nome_categoria'],
            'pontuacao_turma'      => (int) $turma['pontuacao_turma'],
            'qtd_itens_arrecadados'=> (float) $turma['qtd_itens_arrecadados'],
        ],
        'interclasse' => [
            'id_interclasse'       => (int) $inter['id_interclasse'],
            'nome_interclasse'     => $inter['nome_interclasse'] ?? '',
            'ponto_1_lugar'        => $podio[1],
            'ponto_2_lugar'        => $podio[2],
            'ponto_3_lugar'        => $podio[3],
            'valor_item_arrecadacao' => (int) ($inter['valor_item_arrecadacao'] ?? 0),
        ],
        'arrecadacao' => [
            'itens'    => round($arrecItens, 2),
            'pontos'   => $arrecPontos,
            'registros'=> $arrec,
        ],
        'esportes'   => $esportes,
        'penalidades'=> $penalidades,
        'resumo' => [
            'arrecadacao_pontos'  => $arrecPontos,
            'esportes_pontos'     => $esportes['pontos_total'],
            'penalidades_pontos'  => $penalidades['pontos_total'],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
