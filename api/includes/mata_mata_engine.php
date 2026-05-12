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

/** @return array{largura:int, slot:int, kind:string}|null */
function sgi_mm_parse(?string $nomeJogo): ?array
{
    if (!is_string($nomeJogo) || !preg_match('/^MM:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
        return null;
    }
    return ['largura' => (int) $m[1], 'slot' => (int) $m[2], 'kind' => $m[3]];
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
        8 => 'Oitavas de final',
        4 => 'Quartas de final',
        2 => 'Semifinal',
        1 => 'Final',
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
              AND u.status_usuario = \'1\'
              AND u.competidor_usuario = \'1\'';
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
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 0, '1')"
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
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 1, '1')"
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
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida) VALUES (?, ?, 0, '1')"
    );
    $stP->bind_param('ii', $idJogo, $idEquipe);
    $stP->execute();
    $stP->close();
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
