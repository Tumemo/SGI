<?php

declare(strict_types=1);

/**
 * Chaveamento mata-mata: metadados compactos em nome_jogo (VARCHAR 45).
 * Formato: MM:{largura_fase}:{slot}:{N|B}
 * - largura_fase: 8,4,2,1 (equivalente ao fase_nivel pedido: oitavas→8 … final→1)
 * - slot: 0-based dentro da fase
 * - N = confronto normal; B = bye (uma equipe, jogo já concluído)
 */

function sgi_mm_tag(int $larguraFase, int $slot, string $kind): string
{
    return 'MM:' . $larguraFase . ':' . $slot . ':' . $kind;
}

/** @return array{largura:int, slot:int, kind:string, posicao?:int}|null */
function sgi_mm_parse(?string $nomeJogo): ?array
{
    if (!is_string($nomeJogo)) {
        return null;
    }
    if (preg_match('/^MM:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
        return ['largura' => (int) $m[1], 'slot' => (int) $m[2], 'kind' => $m[3]];
    }
    if (preg_match('/^POS:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
        return ['largura' => 0, 'slot' => (int) $m[2], 'kind' => $m[3], 'posicao' => (int) $m[1]];
    }
    return null;
}

function sgi_mm_proxima_largura(int $largura): int
{
    return (int) max(1, $largura / 2);
}

function sgi_mm_slot_pai(int $slot): int
{
    return (int) floor($slot / 2);
}

function sgi_mm_slot_irmao(int $slot): int
{
    return ($slot % 2 === 0) ? $slot + 1 : $slot - 1;
}

function sgi_mm_proximo_pow2(int $n): int
{
    if ($n <= 1) {
        return 2;
    }
    $p = 1;
    while ($p < $n) {
        $p *= 2;
    }
    return $p;
}

function sgi_mm_nome_fase_pt(int $largura): string
{
    return match ($largura) {
        16 => 'Oitavas de final',
        8 => 'Quartas de final',
        4 => 'Semifinal',
        2 => 'Final',
        1 => 'Campeão',
        default => 'Fase ' . $largura,
    };
}

function sgi_mm_resolver_id_local(mysqli $conn): int
{
    $q = $conn->query("SELECT id_local FROM locais WHERE status_local = '1' ORDER BY id_local ASC LIMIT 1");
    if ($q && ($r = $q->fetch_assoc())) {
        return (int) $r['id_local'];
    }
    $q2 = $conn->query('SELECT id_local FROM locais ORDER BY id_local ASC LIMIT 1');
    if ($q2 && ($r2 = $q2->fetch_assoc())) {
        return (int) $r2['id_local'];
    }
    throw new RuntimeException("Não há locais cadastrados na tabela 'locais'.");
}

/**
 * Equipes ativas com pelo menos um competidor ativo vinculado (elenco mínimo).
 *
 * @return list<array{id_equipe:int}>
 */
function sgi_mm_buscar_equipes_validadas(mysqli $conn, int $idModalidade): array
{
    $sql = 'SELECT DISTINCT e.id_equipe
            FROM equipes e
            INNER JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = e.id_equipe
            INNER JOIN usuarios u ON u.id_usuario = ehu.usuarios_id_usuario
            WHERE e.modalidades_id_modalidade = ?
              AND e.status_equipe = \'1\'
              AND u.status_usuario = \'1\'';
    $st = $conn->prepare($sql);
    if (!$st) {
        throw new RuntimeException($conn->error);
    }
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return array_map(static fn (array $r): array => ['id_equipe' => (int) $r['id_equipe']], $rows);
}

function sgi_mm_fase_inicial_existe(mysqli $conn, int $idModalidade): bool
{
    $st = $conn->prepare("SELECT 1 FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:%' LIMIT 1");
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $ok = $st->get_result()->num_rows > 0;
    $st->close();
    return $ok;
}

/**
 * @param list<array{id_equipe:int}> $equipes
 */
function sgi_mm_criar_chaveamento_inicial(mysqli $conn, int $idModalidade, array $equipes): array
{
    if (sgi_mm_fase_inicial_existe($conn, $idModalidade)) {
        throw new RuntimeException('O chaveamento desta modalidade já foi gerado (fase inicial MM).');
    }
    $n = count($equipes);
    if ($n < 2) {
        throw new RuntimeException('Equipes insuficientes (mínimo 2 com elenco validado).');
    }

    shuffle($equipes);
    $w = sgi_mm_proximo_pow2($n);
    $slots = array_fill(0, $w, null);
    $idxSlots = range(0, $w - 1);
    shuffle($idxSlots);
    for ($i = 0; $i < $n; $i++) {
        $slots[$idxSlots[$i]] = $equipes[$i]['id_equipe'];
    }

    $idLocal = sgi_mm_resolver_id_local($conn);
    $jogosCriados = 0;
    $byeJogos = [];
    $meio = (int) ($w / 2);

    for ($s = 0; $s < $meio; $s++) {
        $a = $slots[2 * $s];
        $b = $slots[2 * $s + 1];

        if ($a === null && $b === null) {
            continue;
        }
        if ($a !== null && $b !== null) {
            sgi_mm_inserir_jogo_dupla($conn, $idModalidade, $idLocal, $w, $s, 'N', $a, $b, 'Agendado');
            $jogosCriados++;
            continue;
        }
        $bye = $a ?? $b;
        $idBye = sgi_mm_inserir_jogo_bye($conn, $idModalidade, $idLocal, $w, $s, (int) $bye);
        $byeJogos[] = $idBye;
        $jogosCriados++;
    }

    return ['jogos_criados' => $jogosCriados, 'bye_jogos' => $byeJogos];
}

function sgi_mm_inserir_jogo_dupla(
    mysqli $conn,
    int $idModalidade,
    int $idLocal,
    int $largura,
    int $slot,
    string $kind,
    int $idA,
    int $idB,
    string $statusJogo
): int {
    $nome = sgi_mm_tag($largura, $slot, $kind);
    $st = $conn->prepare(
        "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
         VALUES (?, CURDATE(), '08:00:00', ?, ?, ?)"
    );
    $st->bind_param('ssii', $nome, $statusJogo, $idModalidade, $idLocal);
    $st->execute();
    $idJogo = (int) $conn->insert_id;
    $st->close();

    $stP = $conn->prepare(
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')"
    );
    $stP->bind_param('ii', $idJogo, $idA);
    $stP->execute();
    $stP->bind_param('ii', $idJogo, $idB);
    $stP->execute();
    $stP->close();

    return $idJogo;
}

function sgi_mm_inserir_jogo_bye(
    mysqli $conn,
    int $idModalidade,
    int $idLocal,
    int $largura,
    int $slot,
    int $idEquipe
): int {
    $nome = sgi_mm_tag($largura, $slot, 'B');
    $st = $conn->prepare(
        "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
         VALUES (?, CURDATE(), '08:00:00', 'Concluido', ?, ?)"
    );
    $st->bind_param('sii', $nome, $idModalidade, $idLocal);
    $st->execute();
    $idJogo = (int) $conn->insert_id;
    $st->close();

    $stP = $conn->prepare(
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 1, '1')"
    );
    $stP->bind_param('ii', $idJogo, $idEquipe);
    $stP->execute();
    $stP->close();

    return $idJogo;
}

function sgi_mm_jogo_esta_encerrado(string $status): bool
{
    return $status === 'Concluido' || $status === 'Finalizado';
}

/**
 * Vencedor por maior resultado_partida; empate → menor id_equipe.
 *
 * @param list<array{equipes_id_equipe:int, resultado_partida:int}> $partidas
 */
function sgi_mm_vencedor_de_partidas(array $partidas): ?int
{
    if ($partidas === []) {
        return null;
    }
    if (count($partidas) === 1) {
        return (int) $partidas[0]['equipes_id_equipe'];
    }
    usort($partidas, static function (array $x, array $y): int {
        if ((int) $x['resultado_partida'] !== (int) $y['resultado_partida']) {
            return (int) $y['resultado_partida'] <=> (int) $x['resultado_partida'];
        }
        return (int) $x['equipes_id_equipe'] <=> (int) $y['equipes_id_equipe'];
    });
    return (int) $partidas[0]['equipes_id_equipe'];
}

/** @return list<array{equipes_id_equipe:int, resultado_partida:int}> */
function sgi_mm_carregar_partidas_jogo(mysqli $conn, int $idJogo): array
{
    $st = $conn->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ?');
    $st->bind_param('i', $idJogo);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return array_map(static fn (array $r): array => [
        'equipes_id_equipe' => (int) $r['equipes_id_equipe'],
        'resultado_partida' => (int) $r['resultado_partida'],
    ], $rows);
}

function sgi_mm_vencedor_do_jogo(mysqli $conn, int $idJogo, string $statusJogo, string $kind): ?int
{
    if (!sgi_mm_jogo_esta_encerrado($statusJogo)) {
        return null;
    }
    $ps = sgi_mm_carregar_partidas_jogo($conn, $idJogo);
    if ($kind === 'B') {
        return $ps[0]['equipes_id_equipe'] ?? null;
    }
    return sgi_mm_vencedor_de_partidas($ps);
}

function sgi_mm_buscar_jogo_por_tag(mysqli $conn, int $idModalidade, string $tag): ?array
{
    $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = ? LIMIT 1');
    $st->bind_param('is', $idModalidade, $tag);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

/**
 * Filhos diretos do jogo-pai (largura_pai, slot_pai) na camada inferior (largura = 2 * pai).
 * Cada filho existente deve estar encerrado; filho inexistente = par vazio na semente.
 */
function sgi_mm_filhos_resolvidos_para_pai(mysqli $conn, int $idModalidade, int $larguraPai, int $slotPai): bool
{
    $lChild = $larguraPai * 2;
    if ($lChild < 2) {
        return true;
    }
    $s0 = 2 * $slotPai;
    $s1 = 2 * $slotPai + 1;
    foreach ([$s0, $s1] as $cs) {
        $j0 = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, sgi_mm_tag($lChild, $cs, 'N'))
            ?? sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, sgi_mm_tag($lChild, $cs, 'B'));
        if ($j0 === null) {
            continue;
        }
        if (!sgi_mm_jogo_esta_encerrado((string) $j0['status_jogo'])) {
            return false;
        }
    }
    return true;
}

function sgi_mm_contar_partidas(mysqli $conn, int $idJogo): int
{
    $st = $conn->prepare('SELECT COUNT(*) AS c FROM partidas WHERE jogos_id_jogo = ?');
    $st->bind_param('i', $idJogo);
    $st->execute();
    $c = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    return $c;
}

/**
 * Quando o pai fica com um único competidor e ambos os lados da chave já foram resolvidos (bye implícito).
 */
function sgi_mm_tentar_autoconcluir_pai_um_clube(mysqli $conn, int $idModalidade, int $idJogoPai): void
{
    $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE id_jogo = ? LIMIT 1');
    $st->bind_param('i', $idJogoPai);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        return;
    }
    if (sgi_mm_jogo_esta_encerrado((string) $row['status_jogo'])) {
        return;
    }
    $meta = sgi_mm_parse($row['nome_jogo']);
    if ($meta === null) {
        return;
    }
    if (sgi_mm_contar_partidas($conn, $idJogoPai) !== 1) {
        return;
    }
    if (!sgi_mm_filhos_resolvidos_para_pai($conn, $idModalidade, $meta['largura'], $meta['slot'])) {
        return;
    }
    $stU = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
    $stU->bind_param('i', $idJogoPai);
    $stU->execute();
    $stU->close();
    sgi_chaveamento_processar_avanco($conn, $idJogoPai);
}

function sgi_mm_garantir_partida_equipe(mysqli $conn, int $idJogo, int $idEquipe): void
{
    $part = sgi_mm_carregar_partidas_jogo($conn, $idJogo);
    $ids = array_column($part, 'equipes_id_equipe');
    if (in_array($idEquipe, $ids, true)) {
        return;
    }
    $stP = $conn->prepare(
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')"
    );
    $stP->bind_param('ii', $idJogo, $idEquipe);
    $stP->execute();
    $stP->close();
}

/**
 * Limpa todos os jogos MM nas fases posteriores à fase informada e reprocessa o avanço
 * a partir dos jogos concluídos daquela fase em diante. Usado quando o vencedor de um
 * jogo já concluído é alterado.
 */
function sgi_chaveamento_rebuild_from_round(mysqli $conn, int $idModalidade, int $larguraInicial): void
{
    $st = $conn->prepare(
        "SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:%' ORDER BY id_jogo ASC"
    );
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $jogos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    foreach ($jogos as $j) {
        $meta = sgi_mm_parse($j['nome_jogo']);
        if ($meta === null) {
            continue;
        }
        if ($meta['largura'] < $larguraInicial) {
            $stD = $conn->prepare("DELETE FROM partidas WHERE jogos_id_jogo = ?");
            $stD->bind_param('i', (int) $j['id_jogo']);
            $stD->execute();
            $stD->close();

            $stU = $conn->prepare("UPDATE jogos SET status_jogo = 'Agendado' WHERE id_jogo = ?");
            $stU->bind_param('i', (int) $j['id_jogo']);
            $stU->execute();
            $stU->close();
        }
    }

    $stPOS = $conn->prepare("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'POS:%'");
    $stPOS->bind_param('i', $idModalidade);
    $stPOS->execute();
    $posGames = $stPOS->get_result()->fetch_all(MYSQLI_ASSOC);
    $stPOS->close();
    foreach ($posGames as $pg) {
        $stDP = $conn->prepare("DELETE FROM partidas WHERE jogos_id_jogo = ?");
        $stDP->bind_param('i', (int) $pg['id_jogo']);
        $stDP->execute();
        $stDP->close();
        $stDJ = $conn->prepare("DELETE FROM jogos WHERE id_jogo = ?");
        $stDJ->bind_param('i', (int) $pg['id_jogo']);
        $stDJ->execute();
        $stDJ->close();
    }

    $jogosPorRodada = [];
    foreach ($jogos as $j) {
        $meta = sgi_mm_parse($j['nome_jogo']);
        if ($meta === null) {
            continue;
        }
        $l = $meta['largura'];
        if ($l >= $larguraInicial) {
            $jogosPorRodada[$l][] = $j;
        }
    }
    krsort($jogosPorRodada);

    foreach ($jogosPorRodada as $rodada) {
        foreach ($rodada as $j) {
            if (sgi_mm_jogo_esta_encerrado((string) $j['status_jogo'])) {
                sgi_chaveamento_processar_avanco($conn, (int) $j['id_jogo']);
            }
        }
    }
}

/**
 * Após um jogo MM ser concluído: tenta formar o jogo da fase seguinte quando o par de chave estiver definido.
 */
function sgi_chaveamento_processar_avanco(mysqli $conn, int $idJogo): void
{
    $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
    $st->bind_param('i', $idJogo);
    $st->execute();
    $j = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$j) {
        return;
    }

    $meta = sgi_mm_parse($j['nome_jogo']);
    if ($meta === null) {
        return;
    }

    if (!sgi_mm_jogo_esta_encerrado((string) $j['status_jogo'])) {
        return;
    }

    $largura = $meta['largura'];
    $slot = $meta['slot'];
    $kind = $meta['kind'];
    $idModalidade = (int) $j['modalidades_id_modalidade'];

    if ($largura <= 1) {
        return;
    }

    $irmao = sgi_mm_slot_irmao($slot);
    $tagIrmaoN = sgi_mm_tag($largura, $irmao, 'N');
    $tagIrmaoB = sgi_mm_tag($largura, $irmao, 'B');
    $ji = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $tagIrmaoN)
        ?? sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $tagIrmaoB);

    $w1 = sgi_mm_vencedor_do_jogo($conn, (int) $j['id_jogo'], (string) $j['status_jogo'], $kind);
    if ($w1 === null) {
        return;
    }

    $lPai = sgi_mm_proxima_largura($largura);
    $slotPai = sgi_mm_slot_pai($slot);
    $tagPai = sgi_mm_tag($lPai, $slotPai, 'N');
    $idLocal = sgi_mm_resolver_id_local($conn);

    if ($ji === null) {
        $existente = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $tagPai);
        if ($existente === null) {
            $stIns = $conn->prepare(
                "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
                 VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)"
            );
            $stIns->bind_param('sii', $tagPai, $idModalidade, $idLocal);
            $stIns->execute();
            $idPai = (int) $conn->insert_id;
            $stIns->close();
            sgi_mm_garantir_partida_equipe($conn, $idPai, $w1);
        } else {
            $idPai = (int) $existente['id_jogo'];
            sgi_mm_garantir_partida_equipe($conn, $idPai, $w1);
        }
        sgi_mm_tentar_autoconcluir_pai_um_clube($conn, $idModalidade, $idPai);

        if ($largura === 2) {
            sgi_mm_verificar_gerar_disputas_posicao($conn, $idModalidade, 2);
        }

        return;
    }

    if (!sgi_mm_jogo_esta_encerrado((string) $ji['status_jogo'])) {
        return;
    }

    $kindIrmao = (sgi_mm_parse($ji['nome_jogo']) ?? ['kind' => 'N'])['kind'];
    $w2 = sgi_mm_vencedor_do_jogo($conn, (int) $ji['id_jogo'], (string) $ji['status_jogo'], $kindIrmao);
    if ($w2 === null) {
        return;
    }

    $existente = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $tagPai);

    if ($existente === null) {
        $stIns = $conn->prepare(
            "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
             VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)"
        );
        $stIns->bind_param('sii', $tagPai, $idModalidade, $idLocal);
        $stIns->execute();
        $idNovo = (int) $conn->insert_id;
        $stIns->close();

        sgi_mm_garantir_partida_equipe($conn, $idNovo, $w1);
        sgi_mm_garantir_partida_equipe($conn, $idNovo, $w2);
        return;
    }

    $idPai = (int) $existente['id_jogo'];
    sgi_mm_garantir_partida_equipe($conn, $idPai, $w1);
    sgi_mm_garantir_partida_equipe($conn, $idPai, $w2);

    if ($largura === 2) {
        sgi_mm_verificar_gerar_disputas_posicao($conn, $idModalidade, 2);
    }
}

/**
 * Monta árvore JSON com hierarquia para o front.
 *
 * @return array{success:bool, jogos:list<array<string,mixed>>}
 */
function sgi_mm_montar_json_arvore(mysqli $conn, int $idModalidade): array
{
    $sql = 'SELECT j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo, j.inicio_jogo,
                   p.id_partida, p.equipes_id_equipe, p.resultado_partida,
                   t.nome_turma, t.nome_fantasia_turma
            FROM jogos j
            INNER JOIN partidas p ON j.id_jogo = p.jogos_id_jogo
            INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
            INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
            WHERE j.modalidades_id_modalidade = ?
            ORDER BY j.id_jogo ASC, p.id_partida ASC';

    $st = $conn->prepare($sql);
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $res = $st->get_result();
    $st->close();

    $porJogo = [];
    while ($row = $res->fetch_assoc()) {
        $idJ = (int) $row['id_jogo'];
        if (!isset($porJogo[$idJ])) {
            $meta = sgi_mm_parse($row['nome_jogo']);
            $porJogo[$idJ] = [
                'id_jogo' => $idJ,
                'nome_jogo' => $row['nome_jogo'],
                'status_jogo' => $row['status_jogo'],
                'data_jogo' => $row['data_jogo'],
                'inicio_jogo' => $row['inicio_jogo'],
                'meta' => $meta,
                'partidas' => [],
            ];
        }
        $porJogo[$idJ]['partidas'][] = [
            'id_partida' => (int) $row['id_partida'],
            'equipes_id_equipe' => (int) $row['equipes_id_equipe'],
            'resultado_partida' => (int) $row['resultado_partida'],
            'nome_turma' => $row['nome_turma'],
            'nome_fantasia_turma' => $row['nome_fantasia_turma'],
        ];
    }

    $mapaChave = [];
    foreach ($porJogo as $idJ => $bloco) {
        $m = $bloco['meta'];
        if ($m !== null) {
            $mapaChave[$m['largura'] . ':' . $m['slot'] . ':' . $m['kind']] = $idJ;
        }
    }

    $saida = [];
    foreach ($porJogo as $bloco) {
        $m = $bloco['meta'];
        $faseNivel = $m['largura'] ?? null;
        $slot = $m['slot'] ?? null;
        $kind = $m['kind'] ?? 'N';
        $ehBye = $kind === 'B';

        $proximoId = null;
        $ehDisputaPosicao = isset($m['posicao']);

        if ($faseNivel !== null && $faseNivel > 1 && $slot !== null) {
            $lp = sgi_mm_proxima_largura($faseNivel);
            $sp = sgi_mm_slot_pai($slot);
            $chavePai = $lp . ':' . $sp . ':N';
            $proximoId = $mapaChave[$chavePai] ?? null;
        }

        $venc = null;
        if ($m !== null) {
            $venc = sgi_mm_vencedor_do_jogo(
                $conn,
                $bloco['id_jogo'],
                (string) $bloco['status_jogo'],
                $kind
            );
        }

        $vagaGarantida = $ehBye
            || ($venc !== null && $proximoId !== null)
            || ($venc !== null && (int) $faseNivel === 1);

        $posicaoNaChave = $slot !== null ? $slot + 1 : null;
        $nomeFase = $faseNivel ? sgi_mm_nome_fase_pt($faseNivel) : null;
        if ($ehDisputaPosicao) {
            $posicaoNum = $m['posicao'] ?? 3;
            $nomeFase = sgi_mm_nome_fase_posicao($posicaoNum);
        }
        $nomeDisplay = $nomeFase && $posicaoNaChave
            ? $nomeFase . ' — confronto ' . $posicaoNaChave . ($ehBye ? ' (bye)' : '')
            : $bloco['nome_jogo'];

        $equipesOut = [];
        foreach ($bloco['partidas'] as $p) {
            $equipesOut[] = [
                'id_partida' => $p['id_partida'],
                'id_equipe' => $p['equipes_id_equipe'],
                'nome_turma' => $p['nome_turma'],
                'nome_fantasia' => $p['nome_fantasia_turma'],
                'gols' => $p['resultado_partida'],
            ];
        }

        $saida[] = [
            'id_jogo' => $bloco['id_jogo'],
            'nome_jogo' => $bloco['nome_jogo'],
            'nome_jogo_display' => $nomeDisplay,
            'nome_fase' => $nomeFase,
            'fase_nivel' => $faseNivel,
            'posicao_na_chave' => $posicaoNaChave,
            'eh_bye' => $ehBye,
            'eh_disputa_posicao' => $ehDisputaPosicao,
            'status_jogo' => $bloco['status_jogo'],
            'data_jogo' => $bloco['data_jogo'],
            'inicio_jogo' => $bloco['inicio_jogo'],
            'equipes' => $equipesOut,
            'equipe_vencedora_id' => $venc,
            'proximo_jogo_id' => $proximoId,
            'vaga_garantida' => $vagaGarantida,
        ];
    }

    usort($saida, static function (array $a, array $b): int {
        $fa = (int) ($a['fase_nivel'] ?? 0);
        $fb = (int) ($b['fase_nivel'] ?? 0);
        if ($fa !== $fb) {
            return $fb <=> $fa;
        }
        return ((int) ($a['posicao_na_chave'] ?? 0)) <=> ((int) ($b['posicao_na_chave'] ?? 0));
    });

    return ['success' => true, 'jogos' => $saida];
}


/* ==========================================================================
   DISPUTAS DE POSIÇÃO (3º, 4º lugares)
   ========================================================================== */

function sgi_mm_nome_fase_posicao(int $posicao): string
{
    return match ($posicao) {
        3 => 'Disputa de 3º lugar',
        5 => 'Disputa de 5º lugar',
        default => 'Disputa de ' . $posicao . 'º lugar',
    };
}

/** @param list<array{equipes_id_equipe:int, resultado_partida:int}> $partidas */
function sgi_mm_perdedor_de_partidas(array $partidas): ?int
{
    if (count($partidas) < 2) {
        return null;
    }
    usort($partidas, static function (array $x, array $y): int {
        if ((int) $x['resultado_partida'] !== (int) $y['resultado_partida']) {
            return (int) $y['resultado_partida'] <=> (int) $x['resultado_partida'];
        }
        return (int) $x['equipes_id_equipe'] <=> (int) $y['equipes_id_equipe'];
    });
    return (int) $partidas[1]['equipes_id_equipe'];
}

function sgi_mm_perdedor_do_jogo(mysqli $conn, int $idJogo, string $statusJogo, string $kind): ?int
{
    if (!sgi_mm_jogo_esta_encerrado($statusJogo)) {
        return null;
    }
    $ps = sgi_mm_carregar_partidas_jogo($conn, $idJogo);
    if ($kind === 'B' || count($ps) < 2) {
        return null;
    }
    return sgi_mm_perdedor_de_partidas($ps);
}

function sgi_mm_inserir_jogo_posicao(
    mysqli $conn,
    int $idModalidade,
    int $idLocal,
    int $posicao,
    int $idA,
    int $idB,
): int {
    $nome = 'POS:' . $posicao . ':0:N';
    $st = $conn->prepare(
        "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
         VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)"
    );
    $st->bind_param('sii', $nome, $idModalidade, $idLocal);
    $st->execute();
    $idJogo = (int) $conn->insert_id;
    $st->close();

    $stP = $conn->prepare(
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')"
    );
    $stP->bind_param('ii', $idJogo, $idA);
    $stP->execute();
    $stP->bind_param('ii', $idJogo, $idB);
    $stP->execute();
    $stP->close();

    return $idJogo;
}

/**
 * Verifica se todos os jogos de uma fase foram concluídos e gera disputas de posição.
 * Chamada ao final de sgi_chaveamento_processar_avanco().
 */
function sgi_mm_verificar_gerar_disputas_posicao(mysqli $conn, int $idModalidade, int $larguraFase): void
{
    $tagLike = 'MM:' . $larguraFase . ':%';

    $stT = $conn->prepare("SELECT COUNT(*) AS total FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE ?");
    $stT->bind_param('is', $idModalidade, $tagLike);
    $stT->execute();
    $total = (int) ($stT->get_result()->fetch_assoc()['total'] ?? 0);
    $stT->close();

    if ($total === 0) {
        return;
    }

    $stC = $conn->prepare("SELECT COUNT(*) AS c FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE ? AND (status_jogo = 'Concluido' OR status_jogo = 'Finalizado')");
    $stC->bind_param('is', $idModalidade, $tagLike);
    $stC->execute();
    $concluidos = (int) ($stC->get_result()->fetch_assoc()['c'] ?? 0);
    $stC->close();

    if ($concluidos < $total) {
        return;
    }

    if ($larguraFase === 2) {
        sgi_mm_gerar_disputa_3_lugar($conn, $idModalidade);
    }
}

function sgi_mm_gerar_disputa_3_lugar(mysqli $conn, int $idModalidade): void
{
    $existente = $conn->prepare("SELECT 1 FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = 'POS:3:0:N' LIMIT 1");
    $existente->bind_param('i', $idModalidade);
    $existente->execute();
    if ($existente->get_result()->num_rows > 0) {
        $existente->close();
        return;
    }
    $existente->close();

    $st = $conn->prepare("SELECT id_jogo, status_jogo, nome_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:2:%'");
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $semifinals = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    if (count($semifinals) < 2) {
        return;
    }

    $losers = [];
    foreach ($semifinals as $sf) {
        $meta = sgi_mm_parse($sf['nome_jogo']);
        if ($meta === null) {
            continue;
        }
        $loser = sgi_mm_perdedor_do_jogo($conn, (int) $sf['id_jogo'], (string) $sf['status_jogo'], $meta['kind']);
        if ($loser !== null) {
            $losers[] = $loser;
        }
    }

    if (count($losers) < 2) {
        return;
    }

    $idLocal = sgi_mm_resolver_id_local($conn);
    sgi_mm_inserir_jogo_posicao($conn, $idModalidade, $idLocal, 3, $losers[0], $losers[1]);
}

/**
 * Verifica se o torneio da modalidade está totalmente concluído.
 */
function sgi_mm_torneio_concluido(mysqli $conn, int $idModalidade): bool
{
    $st = $conn->prepare(
        "SELECT COUNT(*) AS c FROM jogos
         WHERE modalidades_id_modalidade = ?
           AND status_jogo NOT IN ('Concluido', 'Finalizado')"
    );
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $pendentes = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    return $pendentes === 0;
}

/**
 * Monta o histórico completo do torneio: classificação final + todos os confrontos.
 */
function sgi_mm_montar_historico(mysqli $conn, int $idModalidade): array
{
    $concluido = sgi_mm_torneio_concluido($conn, $idModalidade);

    $stJ = $conn->prepare(
        'SELECT j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo,
                j.duracao_jogo, j.tempo_extra_jogo, j.inicio_jogo, j.termino_jogo,
                p.id_partida, p.equipes_id_equipe, p.resultado_partida,
                t.nome_turma, t.nome_fantasia_turma
         FROM jogos j
         INNER JOIN partidas p ON j.id_jogo = p.jogos_id_jogo
         INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
         INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
         WHERE j.modalidades_id_modalidade = ?
         ORDER BY j.id_jogo ASC, p.resultado_partida DESC'
    );
    $stJ->bind_param('i', $idModalidade);
    $stJ->execute();
    $rows = $stJ->get_result()->fetch_all(MYSQLI_ASSOC);
    $stJ->close();

    $porJogo = [];
    foreach ($rows as $row) {
        $idJ = (int) $row['id_jogo'];
        if (!isset($porJogo[$idJ])) {
            $meta = sgi_mm_parse($row['nome_jogo']);
            $porJogo[$idJ] = [
                'id_jogo' => $idJ,
                'nome_jogo' => $row['nome_jogo'],
                'meta' => $meta,
                'status_jogo' => $row['status_jogo'],
                'data_jogo' => $row['data_jogo'],
                'duracao_jogo' => $row['duracao_jogo'],
                'tempo_extra_jogo' => $row['tempo_extra_jogo'],
                'inicio_jogo' => $row['inicio_jogo'],
                'termino_jogo' => $row['termino_jogo'],
                'partidas' => [],
            ];
        }
        $porJogo[$idJ]['partidas'][] = [
            'id_equipe' => (int) $row['equipes_id_equipe'],
            'gols' => (int) $row['resultado_partida'],
            'nome_turma' => $row['nome_turma'],
            'nome_fantasia' => $row['nome_fantasia_turma'],
        ];
    }

    $classificacao = [];
    $confrontos = [];

    foreach ($porJogo as $jogo) {
        $meta = $jogo['meta'];
        $ps = $jogo['partidas'];
        if ($meta === null || count($ps) < 2) {
            continue;
        }

        usort($ps, static fn($a, $b) => $b['gols'] <=> $a['gols']);
        $vencedor = $ps[0];
        $perdedor = $ps[1];

        $fase = $meta['largura'] ?? 0;
        $posicao = $meta['posicao'] ?? null;

        if (isset($meta['posicao'])) {
            $nomeFase = sgi_mm_nome_fase_posicao($meta['posicao']);
            $posVencedor = $meta['posicao'];
            $posPerdedor = $meta['posicao'] + 1;
            $classificacao[$posVencedor] = $vencedor;
            $classificacao[$posPerdedor] = $perdedor;
        } elseif ($fase === 1) {
            $classificacao[1] = $vencedor;
            $classificacao[2] = $perdedor;
            $nomeFase = 'Final';
        } else {
            $nomeFase = sgi_mm_nome_fase_pt($fase);
        }

        $tempoExtra = (int) ($jogo['tempo_extra_jogo'] ?? 0);
        $duracao = (int) ($jogo['duracao_jogo'] ?? 0);
        $duracaoMin = $duracao > 0 ? (int) ceil($duracao / 60) : null;
        $extraMin = $tempoExtra > 0 ? (int) ceil($tempoExtra / 60) : null;
        $totalMin = ($duracaoMin ?? 0) + ($extraMin ?? 0);

        $confrontos[] = [
            'fase' => $nomeFase,
            'vencedor_nome' => $vencedor['nome_fantasia'] ?: $vencedor['nome_turma'],
            'vencedor_gols' => $vencedor['gols'],
            'perdedor_nome' => $perdedor['nome_fantasia'] ?: $perdedor['nome_turma'],
            'perdedor_gols' => $perdedor['gols'],
            'duracao_min' => $duracaoMin,
            'tempo_extra_min' => $extraMin,
            'total_min' => $totalMin > 0 ? $totalMin : null,
        ];
    }

    if (!isset($classificacao[1]) && !isset($classificacao[2])) {
        usort($confrontos, static function ($a, $b) {
            $order = ['Final' => 1, 'Semifinal' => 2, 'Disputa de 3º lugar' => 3, 'Quartas de final' => 4, 'Oitavas de final' => 5];
            $oa = $order[$a['fase']] ?? 99;
            $ob = $order[$b['fase']] ?? 99;
            return $oa <=> $ob;
        });
    }

    ksort($classificacao);

    return [
        'success' => true,
        'concluido' => $concluido,
        'classificacao' => array_values($classificacao),
        'confrontos' => $confrontos,
    ];
}
