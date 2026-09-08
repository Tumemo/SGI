<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

final class MysqliChaveamentoRepository
{
    public static function resolverIdLocal(\mysqli $conn): int
    {
        $q = $conn->query("SELECT id_local FROM locais WHERE status_local = '1' ORDER BY id_local ASC LIMIT 1");
        if ($q && $r = $q->fetch_assoc()) {
            return (int) $r['id_local'];
        }
        $q2 = $conn->query('SELECT id_local FROM locais ORDER BY id_local ASC LIMIT 1');
        if ($q2 && $r2 = $q2->fetch_assoc()) {
            return (int) $r2['id_local'];
        }
        throw new \RuntimeException("Não há locais cadastrados na tabela 'locais'.");
    }
    /**
     * Equipes ativas com pelo menos um competidor ativo vinculado (elenco mínimo).
     *
     * @return list<array{id_equipe:int}>
     */
    public static function buscarEquipesValidadas(\mysqli $conn, int $idModalidade): array
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
            throw new \RuntimeException($conn->error);
        }
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $rows = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        return \array_map(static fn (array $r): array => ['id_equipe' => (int) $r['id_equipe']], $rows);
    }
    public static function faseInicialExiste(\mysqli $conn, int $idModalidade): bool
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
    public static function criarChaveamentoInicial(\mysqli $conn, int $idModalidade, array $equipes): array
    {
        if (\App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::faseInicialExiste($conn, $idModalidade)) {
            throw new \RuntimeException('O chaveamento desta modalidade já foi gerado (fase inicial MM).');
        }
        $n = \count($equipes);
        if ($n < 2) {
            throw new \RuntimeException('Equipes insuficientes (mínimo 2 com elenco validado).');
        }
        \shuffle($equipes);
        $w = \App\Modules\Competicoes\Domain\ChaveamentoRules::proximoPow2($n);
        $slots = \array_fill(0, $w, \null);
        $idxSlots = \range(0, $w - 1);
        \shuffle($idxSlots);
        for ($i = 0; $i < $n; $i++) {
            $slots[$idxSlots[$i]] = $equipes[$i]['id_equipe'];
        }
        $idLocal = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::resolverIdLocal($conn);
        $jogosCriados = 0;
        $byeJogos = [];
        $meio = (int) ($w / 2);
        for ($s = 0; $s < $meio; $s++) {
            $a = $slots[2 * $s];
            $b = $slots[2 * $s + 1];
            if ($a === \null && $b === \null) {
                continue;
            }
            if ($a !== \null && $b !== \null) {
                \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::inserirJogoDupla($conn, $idModalidade, $idLocal, $w, $s, 'N', $a, $b, 'Agendado');
                $jogosCriados++;
                continue;
            }
            $bye = $a ?? $b;
            $idBye = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::inserirJogoBye($conn, $idModalidade, $idLocal, $w, $s, (int) $bye);
            $byeJogos[] = $idBye;
            $jogosCriados++;
        }
        return ['jogos_criados' => $jogosCriados, 'bye_jogos' => $byeJogos];
    }
    public static function inserirJogoDupla(\mysqli $conn, int $idModalidade, int $idLocal, int $largura, int $slot, string $kind, int $idA, int $idB, string $statusJogo): int
    {
        $nome = \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($largura, $slot, $kind);
        $st = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n         VALUES (?, CURDATE(), '08:00:00', ?, ?, ?)");
        $st->bind_param('ssii', $nome, $statusJogo, $idModalidade, $idLocal);
        $st->execute();
        $idJogo = (int) $conn->insert_id;
        $st->close();
        $stP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
        $stP->bind_param('ii', $idJogo, $idA);
        $stP->execute();
        $stP->bind_param('ii', $idJogo, $idB);
        $stP->execute();
        $stP->close();
        return $idJogo;
    }
    public static function inserirJogoBye(\mysqli $conn, int $idModalidade, int $idLocal, int $largura, int $slot, int $idEquipe): int
    {
        $nome = \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($largura, $slot, 'B');
        $st = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n         VALUES (?, CURDATE(), '08:00:00', 'Concluido', ?, ?)");
        $st->bind_param('sii', $nome, $idModalidade, $idLocal);
        $st->execute();
        $idJogo = (int) $conn->insert_id;
        $st->close();
        $stP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 1, '1')");
        $stP->bind_param('ii', $idJogo, $idEquipe);
        $stP->execute();
        $stP->close();
        return $idJogo;
    }
    /** @return list<array{equipes_id_equipe:int, resultado_partida:int}> */
    public static function carregarPartidasJogo(\mysqli $conn, int $idJogo): array
    {
        $st = $conn->prepare('SELECT equipes_id_equipe, resultado_partida FROM partidas WHERE jogos_id_jogo = ?');
        $st->bind_param('i', $idJogo);
        $st->execute();
        $rows = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        return \array_map(static fn (array $r): array => ['equipes_id_equipe' => (int) $r['equipes_id_equipe'], 'resultado_partida' => (int) $r['resultado_partida']], $rows);
    }
    public static function vencedorDoJogo(\mysqli $conn, int $idJogo, string $statusJogo, string $kind): ?int
    {
        if (!\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado($statusJogo)) {
            return \null;
        }
        $ps = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
        if ($kind === 'B') {
            return $ps[0]['equipes_id_equipe'] ?? \null;
        }
        return \App\Modules\Competicoes\Domain\ChaveamentoRules::vencedorDePartidas($ps);
    }
    public static function buscarJogoPorTag(\mysqli $conn, int $idModalidade, string $tag): ?array
    {
        $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = ? LIMIT 1');
        $st->bind_param('is', $idModalidade, $tag);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return $row ?: \null;
    }
    /**
     * Filhos diretos do jogo-pai (largura_pai, slot_pai) na camada inferior (largura = 2 * pai).
     * Cada filho existente deve estar encerrado; filho inexistente = par vazio na semente.
     */
    public static function filhosResolvidosParaPai(\mysqli $conn, int $idModalidade, int $larguraPai, int $slotPai): bool
    {
        $lChild = $larguraPai * 2;
        if ($lChild < 2) {
            return \true;
        }
        $s0 = 2 * $slotPai;
        $s1 = 2 * $slotPai + 1;
        foreach ([$s0, $s1] as $cs) {
            $j0 = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($lChild, $cs, 'N')) ?? \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($lChild, $cs, 'B'));
            if ($j0 === \null) {
                continue;
            }
            if (!\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado((string) $j0['status_jogo'])) {
                return \false;
            }
        }
        return \true;
    }
    public static function contarPartidas(\mysqli $conn, int $idJogo): int
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
    public static function tentarAutoconcluirPaiUmClube(\mysqli $conn, int $idModalidade, int $idJogoPai): void
    {
        $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE id_jogo = ? LIMIT 1');
        $st->bind_param('i', $idJogoPai);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) {
            return;
        }
        if (\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado((string) $row['status_jogo'])) {
            return;
        }
        $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($row['nome_jogo']);
        if ($meta === \null) {
            return;
        }
        if (\App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::contarPartidas($conn, $idJogoPai) !== 1) {
            return;
        }
        if (!\App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::filhosResolvidosParaPai($conn, $idModalidade, $meta['largura'], $meta['slot'])) {
            return;
        }
        $stU = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
        $stU->bind_param('i', $idJogoPai);
        $stU->execute();
        $stU->close();
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoProcessarAvanco($conn, $idJogoPai);
    }
    public static function garantirPartidaEquipe(\mysqli $conn, int $idJogo, int $idEquipe): void
    {
        $part = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
        $ids = \array_column($part, 'equipes_id_equipe');
        if (\in_array($idEquipe, $ids, \true)) {
            return;
        }
        $stP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
        $stP->bind_param('ii', $idJogo, $idEquipe);
        $stP->execute();
        $stP->close();
    }
    /**
     * Limpa todos os jogos MM nas fases posteriores à fase informada e reprocessa o avanço
     * a partir dos jogos concluídos daquela fase em diante. Usado quando o vencedor de um
     * jogo já concluído é alterado.
     */
    public static function chaveamentoRebuildFromRound(\mysqli $conn, int $idModalidade, int $larguraInicial): void
    {
        $st = $conn->prepare("SELECT id_jogo, nome_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:%' ORDER BY id_jogo ASC");
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $jogos = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        foreach ($jogos as $j) {
            $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($j['nome_jogo']);
            if ($meta === \null) {
                continue;
            }
            if ($meta['largura'] < $larguraInicial) {
                $jogoId = (int) $j['id_jogo'];
                $stD = $conn->prepare("DELETE FROM partidas WHERE jogos_id_jogo = ?");
                $stD->bind_param('i', $jogoId);
                $stD->execute();
                $stD->close();
                $stU = $conn->prepare("UPDATE jogos SET status_jogo = 'Agendado' WHERE id_jogo = ?");
                $stU->bind_param('i', $jogoId);
                $stU->execute();
                $stU->close();
            }
        }
        $stPOS = $conn->prepare("SELECT id_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'POS:%'");
        $stPOS->bind_param('i', $idModalidade);
        $stPOS->execute();
        $posGames = $stPOS->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stPOS->close();
        foreach ($posGames as $pg) {
            $posGameId = (int) $pg['id_jogo'];
            $stDP = $conn->prepare("DELETE FROM partidas WHERE jogos_id_jogo = ?");
            $stDP->bind_param('i', $posGameId);
            $stDP->execute();
            $stDP->close();
            $stDJ = $conn->prepare("DELETE FROM jogos WHERE id_jogo = ?");
            $stDJ->bind_param('i', $posGameId);
            $stDJ->execute();
            $stDJ->close();
        }
        $jogosPorRodada = [];
        foreach ($jogos as $j) {
            $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($j['nome_jogo']);
            if ($meta === \null) {
                continue;
            }
            $l = $meta['largura'];
            if ($l >= $larguraInicial) {
                $jogosPorRodada[$l][] = $j;
            }
        }
        \krsort($jogosPorRodada);
        foreach ($jogosPorRodada as $rodada) {
            foreach ($rodada as $j) {
                if (\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado((string) $j['status_jogo'])) {
                    \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoProcessarAvanco($conn, (int) $j['id_jogo']);
                }
            }
        }
    }
    /**
     * Após um jogo MM ser concluído: tenta formar o jogo da fase seguinte quando o par de chave estiver definido.
     */
    public static function chaveamentoProcessarAvanco(\mysqli $conn, int $idJogo): void
    {
        $st = $conn->prepare('SELECT id_jogo, nome_jogo, status_jogo, modalidades_id_modalidade FROM jogos WHERE id_jogo = ? LIMIT 1');
        $st->bind_param('i', $idJogo);
        $st->execute();
        $j = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$j) {
            return;
        }
        $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($j['nome_jogo']);
        if ($meta === \null) {
            return;
        }
        if (!\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado((string) $j['status_jogo'])) {
            return;
        }
        $largura = $meta['largura'];
        $slot = $meta['slot'];
        $kind = $meta['kind'];
        $idModalidade = (int) $j['modalidades_id_modalidade'];
        if ($largura <= 1) {
            return;
        }
        $irmao = \App\Modules\Competicoes\Domain\ChaveamentoRules::slotIrmao($slot);
        $tagIrmaoN = \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($largura, $irmao, 'N');
        $tagIrmaoB = \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($largura, $irmao, 'B');
        $ji = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, $tagIrmaoN) ?? \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, $tagIrmaoB);
        $w1 = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::vencedorDoJogo($conn, (int) $j['id_jogo'], (string) $j['status_jogo'], $kind);
        if ($w1 === \null) {
            return;
        }
        // A grande final é terminal. Seu vencedor é usado diretamente para
        // classificação/pontuação; não criar um jogo solo MM:1.
        if ($largura === 2) {
            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::verificarGerarDisputasPosicao($conn, $idModalidade, 2);
            return;
        }
        $lPai = \App\Modules\Competicoes\Domain\ChaveamentoRules::proximaLargura($largura);
        $slotPai = \App\Modules\Competicoes\Domain\ChaveamentoRules::slotPai($slot);
        $tagPai = \App\Modules\Competicoes\Domain\ChaveamentoRules::tag($lPai, $slotPai, 'N');
        $idLocal = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::resolverIdLocal($conn);
        if ($ji === \null) {
            $existente = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, $tagPai);
            if ($existente === \null) {
                $stIns = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n                 VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
                $stIns->bind_param('sii', $tagPai, $idModalidade, $idLocal);
                $stIns->execute();
                $idPai = (int) $conn->insert_id;
                $stIns->close();
                \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idPai, $w1);
            } else {
                $idPai = (int) $existente['id_jogo'];
                \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idPai, $w1);
            }
            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::tentarAutoconcluirPaiUmClube($conn, $idModalidade, $idPai);
            return;
        }
        if (!\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado((string) $ji['status_jogo'])) {
            return;
        }
        $kindIrmao = (\App\Modules\Competicoes\Domain\ChaveamentoRules::parse($ji['nome_jogo']) ?? ['kind' => 'N'])['kind'];
        $w2 = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::vencedorDoJogo($conn, (int) $ji['id_jogo'], (string) $ji['status_jogo'], $kindIrmao);
        if ($w2 === \null) {
            return;
        }
        $existente = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarJogoPorTag($conn, $idModalidade, $tagPai);
        if ($existente === \null) {
            $stIns = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n             VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
            $stIns->bind_param('sii', $tagPai, $idModalidade, $idLocal);
            $stIns->execute();
            $idNovo = (int) $conn->insert_id;
            $stIns->close();
            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idNovo, $w1);
            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idNovo, $w2);
            return;
        }
        $idPai = (int) $existente['id_jogo'];
        $stClean = $conn->prepare("DELETE FROM partidas WHERE jogos_id_jogo = ? AND equipes_id_equipe NOT IN (?, ?)");
        $stClean->bind_param('iii', $idPai, $w1, $w2);
        $stClean->execute();
        $stClean->close();
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idPai, $w1);
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::garantirPartidaEquipe($conn, $idPai, $w2);
    }
    /**
     * Monta árvore JSON com hierarquia para o front.
     *
     * @return array{success:bool, jogos:list<array<string,mixed>>}
     */
    public static function montarJsonArvore(\mysqli $conn, int $idModalidade): array
    {
        $sql = 'SELECT j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo, j.inicio_jogo,
                   p.id_partida, p.equipes_id_equipe, p.resultado_partida,
                   t.nome_turma, t.nome_fantasia_turma,
                   e.nome_equipe
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
                $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($row['nome_jogo']);
                $porJogo[$idJ] = ['id_jogo' => $idJ, 'nome_jogo' => $row['nome_jogo'], 'status_jogo' => $row['status_jogo'], 'data_jogo' => $row['data_jogo'], 'inicio_jogo' => $row['inicio_jogo'], 'meta' => $meta, 'partidas' => []];
            }
            $porJogo[$idJ]['partidas'][] = ['id_partida' => (int) $row['id_partida'], 'equipes_id_equipe' => (int) $row['equipes_id_equipe'], 'resultado_partida' => (int) $row['resultado_partida'], 'nome_turma' => $row['nome_turma'], 'nome_fantasia_turma' => $row['nome_fantasia_turma'], 'nome_equipe' => $row['nome_equipe']];
        }
        $mapaChave = [];
        foreach ($porJogo as $idJ => $bloco) {
            $m = $bloco['meta'];
            if ($m !== \null) {
                $mapaChave[$m['largura'] . ':' . $m['slot'] . ':' . $m['kind']] = $idJ;
            }
        }
        $saida = [];
        foreach ($porJogo as $bloco) {
            $m = $bloco['meta'];
            $faseNivel = $m['largura'] ?? \null;
            $slot = $m['slot'] ?? \null;
            $kind = $m['kind'] ?? 'N';
            $ehBye = $kind === 'B';
            $proximoId = \null;
            $ehDisputaPosicao = isset($m['posicao']);
            if ($faseNivel !== \null && $faseNivel > 1 && $slot !== \null) {
                $lp = \App\Modules\Competicoes\Domain\ChaveamentoRules::proximaLargura($faseNivel);
                $sp = \App\Modules\Competicoes\Domain\ChaveamentoRules::slotPai($slot);
                $chavePai = $lp . ':' . $sp . ':N';
                $proximoId = $mapaChave[$chavePai] ?? \null;
            }
            $venc = \null;
            if ($m !== \null) {
                $venc = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::vencedorDoJogo($conn, $bloco['id_jogo'], (string) $bloco['status_jogo'], $kind);
            }
            $vagaGarantida = $ehBye || $venc !== \null && $proximoId !== \null || $venc !== \null && (int) $faseNivel === 1;
            $posicaoNaChave = $slot !== \null ? $slot + 1 : \null;
            $nomeFase = $faseNivel ? \App\Modules\Competicoes\Domain\ChaveamentoRules::nomeFasePt($faseNivel) : \null;
            if ($ehDisputaPosicao) {
                $posicaoNum = $m['posicao'];
                $nomeFase = \App\Modules\Competicoes\Domain\ChaveamentoRules::nomeFasePosicao($posicaoNum);
            }
            $nomeDisplay = $nomeFase && $posicaoNaChave ? $nomeFase . ' — confronto ' . $posicaoNaChave . ($ehBye ? ' (bye)' : '') : $bloco['nome_jogo'];
            $equipesOut = [];
            foreach ($bloco['partidas'] as $p) {
                $equipesOut[] = ['id_partida' => $p['id_partida'], 'id_equipe' => $p['equipes_id_equipe'], 'nome_turma' => $p['nome_turma'], 'nome_fantasia' => $p['nome_fantasia_turma'], 'nome_equipe' => $p['nome_equipe'], 'gols' => $p['resultado_partida']];
            }
            $saida[] = ['id_jogo' => $bloco['id_jogo'], 'nome_jogo' => $bloco['nome_jogo'], 'nome_jogo_display' => $nomeDisplay, 'nome_fase' => $nomeFase, 'fase_nivel' => $faseNivel, 'posicao_na_chave' => $posicaoNaChave, 'eh_bye' => $ehBye, 'eh_disputa_posicao' => $ehDisputaPosicao, 'status_jogo' => $bloco['status_jogo'], 'data_jogo' => $bloco['data_jogo'], 'inicio_jogo' => $bloco['inicio_jogo'], 'equipes' => $equipesOut, 'equipe_vencedora_id' => $venc, 'proximo_jogo_id' => $proximoId, 'vaga_garantida' => $vagaGarantida];
        }
        \usort($saida, static function (array $a, array $b): int {
            $fa = (int) ($a['fase_nivel'] ?? 0);
            $fb = (int) ($b['fase_nivel'] ?? 0);
            if ($fa !== $fb) {
                return $fb <=> $fa;
            }
            return (int) ($a['posicao_na_chave'] ?? 0) <=> (int) ($b['posicao_na_chave'] ?? 0);
        });
        return ['success' => \true, 'jogos' => $saida];
    }
    public static function perdedorDoJogo(\mysqli $conn, int $idJogo, string $statusJogo, string $kind): ?int
    {
        if (!\App\Modules\Competicoes\Domain\ChaveamentoRules::jogoEstaEncerrado($statusJogo)) {
            return \null;
        }
        $ps = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::carregarPartidasJogo($conn, $idJogo);
        if ($kind === 'B' || \count($ps) < 2) {
            return \null;
        }
        return \App\Modules\Competicoes\Domain\ChaveamentoRules::perdedorDePartidas($ps);
    }
    public static function inserirJogoPosicao(\mysqli $conn, int $idModalidade, int $idLocal, int $posicao, int $idA, int $idB): int
    {
        $nome = 'POS:' . $posicao . ':0:N';
        $st = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n         VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
        $st->bind_param('sii', $nome, $idModalidade, $idLocal);
        $st->execute();
        $idJogo = (int) $conn->insert_id;
        $st->close();
        $stP = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
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
    public static function verificarGerarDisputasPosicao(\mysqli $conn, int $idModalidade, int $larguraFase): void
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
            \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::gerarDisputa3Lugar($conn, $idModalidade);
        }
    }
    public static function gerarDisputa3Lugar(\mysqli $conn, int $idModalidade): void
    {
        $existente = $conn->prepare("SELECT 1 FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = 'POS:3:0:N' LIMIT 1");
        $existente->bind_param('i', $idModalidade);
        $existente->execute();
        if ($existente->get_result()->num_rows > 0) {
            $existente->close();
            return;
        }
        $existente->close();
        // Os perdedores que disputam o 3º lugar são os das SEMIFINAIS (MM:4:%).
        // A verificação de gatilho continua sendo a conclusão da fase MM:2 (final),
        // momento em que ambas as semifinais já estão necessariamente encerradas.
        $st = $conn->prepare("SELECT id_jogo, status_jogo, nome_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:4:%'");
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $semifinals = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        if (\count($semifinals) < 2) {
            return;
        }
        $losers = [];
        foreach ($semifinals as $sf) {
            $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($sf['nome_jogo']);
            if ($meta === \null) {
                continue;
            }
            $loser = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::perdedorDoJogo($conn, (int) $sf['id_jogo'], (string) $sf['status_jogo'], $meta['kind']);
            if ($loser !== \null) {
                $losers[] = $loser;
            }
        }
        if (\count($losers) < 2) {
            return;
        }
        $idLocal = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::resolverIdLocal($conn);
        \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::inserirJogoPosicao($conn, $idModalidade, $idLocal, 3, $losers[0], $losers[1]);
    }
    /**
     * Verifica se o torneio da modalidade está totalmente concluído.
     */
    public static function torneioConcluido(\mysqli $conn, int $idModalidade): bool
    {
        $st = $conn->prepare("SELECT COUNT(*) AS c FROM jogos\r\n         WHERE modalidades_id_modalidade = ?\r\n           AND status_jogo NOT IN ('Concluido', 'Finalizado')");
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $pendentes = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
        $st->close();
        return $pendentes === 0;
    }
    /**
     * Monta o histórico completo do torneio: classificação final + todos os confrontos.
     */
    public static function montarHistorico(\mysqli $conn, int $idModalidade): array
    {
        $concluido = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::torneioConcluido($conn, $idModalidade);
        $stJ = $conn->prepare('SELECT j.id_jogo, j.nome_jogo, j.status_jogo, j.data_jogo,
                j.duracao_jogo, j.tempo_extra_jogo, j.inicio_jogo, j.termino_jogo,
                p.id_partida, p.equipes_id_equipe, p.resultado_partida,
                t.nome_turma, t.nome_fantasia_turma,
                e.nome_equipe
         FROM jogos j
         INNER JOIN partidas p ON j.id_jogo = p.jogos_id_jogo
         INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
         INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
         WHERE j.modalidades_id_modalidade = ?
         ORDER BY j.id_jogo ASC, p.resultado_partida DESC');
        $stJ->bind_param('i', $idModalidade);
        $stJ->execute();
        $rows = $stJ->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stJ->close();
        $porJogo = [];
        foreach ($rows as $row) {
            $idJ = (int) $row['id_jogo'];
            if (!isset($porJogo[$idJ])) {
                $meta = \App\Modules\Competicoes\Domain\ChaveamentoRules::parse($row['nome_jogo']);
                $porJogo[$idJ] = ['id_jogo' => $idJ, 'nome_jogo' => $row['nome_jogo'], 'meta' => $meta, 'status_jogo' => $row['status_jogo'], 'data_jogo' => $row['data_jogo'], 'duracao_jogo' => $row['duracao_jogo'], 'tempo_extra_jogo' => $row['tempo_extra_jogo'], 'inicio_jogo' => $row['inicio_jogo'], 'termino_jogo' => $row['termino_jogo'], 'partidas' => []];
            }
            $porJogo[$idJ]['partidas'][] = ['id_equipe' => (int) $row['equipes_id_equipe'], 'gols' => (int) $row['resultado_partida'], 'nome_turma' => $row['nome_turma'], 'nome_fantasia' => $row['nome_fantasia_turma'], 'nome_equipe' => $row['nome_equipe']];
        }
        $classificacao = [];
        $confrontos = [];
        foreach ($porJogo as $jogo) {
            $meta = $jogo['meta'];
            $ps = $jogo['partidas'];
            if ($meta === \null || \count($ps) < 2) {
                continue;
            }
            \usort($ps, static fn ($a, $b) => $b['gols'] <=> $a['gols']);
            $vencedor = $ps[0];
            $perdedor = $ps[1];
            $fase = $meta['largura'];
            $posicao = $meta['posicao'] ?? \null;
            if (isset($meta['posicao'])) {
                $nomeFase = \App\Modules\Competicoes\Domain\ChaveamentoRules::nomeFasePosicao($meta['posicao']);
                $posVencedor = $meta['posicao'];
                $posPerdedor = $meta['posicao'] + 1;
                $classificacao[$posVencedor] = $vencedor;
                $classificacao[$posPerdedor] = $perdedor;
            } elseif ($fase === 1) {
                $classificacao[1] = $vencedor;
                $classificacao[2] = $perdedor;
                $nomeFase = 'Final';
            } else {
                $nomeFase = \App\Modules\Competicoes\Domain\ChaveamentoRules::nomeFasePt($fase);
            }
            $tempoExtra = (int) ($jogo['tempo_extra_jogo'] ?? 0);
            $duracao = (int) ($jogo['duracao_jogo'] ?? 0);
            $duracaoMin = $duracao > 0 ? (int) \ceil($duracao / 60) : \null;
            $extraMin = $tempoExtra > 0 ? (int) \ceil($tempoExtra / 60) : \null;
            $totalMin = ($duracaoMin ?? 0) + ($extraMin ?? 0);
            $confrontos[] = ['fase' => $nomeFase, 'vencedor_nome' => ($vencedor['nome_equipe'] ?: $vencedor['nome_fantasia']) ?: $vencedor['nome_turma'], 'vencedor_gols' => $vencedor['gols'], 'perdedor_nome' => ($perdedor['nome_equipe'] ?: $perdedor['nome_fantasia']) ?: $perdedor['nome_turma'], 'perdedor_gols' => $perdedor['gols'], 'duracao_min' => $duracaoMin, 'tempo_extra_min' => $extraMin, 'total_min' => $totalMin > 0 ? $totalMin : \null];
        }
        if (!isset($classificacao[1]) && !isset($classificacao[2])) {
            \usort($confrontos, static function ($a, $b) {
                $order = ['Final' => 1, 'Semifinal' => 2, 'Disputa de 3º lugar' => 3, 'Quartas de final' => 4, 'Oitavas de final' => 5];
                $oa = $order[$a['fase']] ?? 99;
                $ob = $order[$b['fase']] ?? 99;
                return $oa <=> $ob;
            });
        }
        \ksort($classificacao);
        return ['success' => \true, 'concluido' => $concluido, 'classificacao' => \array_values($classificacao), 'confrontos' => $confrontos];
    }
}
