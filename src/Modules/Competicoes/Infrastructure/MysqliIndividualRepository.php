<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Resultados\Domain\PodioRules;
use App\Modules\Resultados\Infrastructure\MysqliPodioRepository;

final class MysqliIndividualRepository
{
    /**
     * Engine para modalidades individuais (corrida, natação, etc.)
     *
     * O admin seleciona participantes e registra 1º, 2º e 3º lugar.
     * Cada participante é um registro na tabela `partidas` com resultado_partida = posição (1, 2 ou 3).
     * Um "jogo" especial é criado com nome_jogo = "IND:{id_modalidade}" para agrupar o ranking.
     */
    /**
     * Busca o primeiro local ativo disponível.
     */
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
        return 1;
    }
    /**
     * Busca participantes disponíveis para uma modalidade individual.
     * Retorna alunos que são competidores e estão vinculados a equipes dessa modalidade.
     *
     * @return list<array{id_usuario:int, nome_usuario:string, nome_turma:string, nome_fantasia_turma:string, genero_usuario:string, id_equipe:int}>
     */
    public static function buscarParticipantes(\mysqli $conn, int $idModalidade): array
    {
        $sql = 'SELECT DISTINCT u.id_usuario, u.nome_usuario, u.genero_usuario,
                   t.nome_turma, t.nome_fantasia_turma,
                   e.id_equipe
            FROM usuarios u
            INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario
            INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe
            INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
            WHERE e.modalidades_id_modalidade = ?
              AND e.status_equipe = \'1\'
              AND u.status_usuario = \'1\'
            ORDER BY t.nome_turma, u.nome_usuario';
        $st = $conn->prepare($sql);
        if (!$st) {
            throw new \RuntimeException($conn->error);
        }
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $rows = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        return \array_map(static fn (array $r): array => ['id_usuario' => (int) $r['id_usuario'], 'nome_usuario' => $r['nome_usuario'], 'genero_usuario' => $r['genero_usuario'], 'nome_turma' => $r['nome_turma'], 'nome_fantasia_turma' => $r['nome_fantasia_turma'], 'id_equipe' => (int) $r['id_equipe']], $rows);
    }
    /**
     * Busca o jogo de ranking existente para uma modalidade individual.
     *
     * @return array{id_jogo:int, status_jogo:string}|null
     */
    public static function buscarJogoExistente(\mysqli $conn, int $idModalidade): ?array
    {
        $tag = \App\Modules\Competicoes\Domain\IndividualRules::tag($idModalidade);
        $st = $conn->prepare('SELECT id_jogo, status_jogo FROM jogos WHERE nome_jogo = ? LIMIT 1');
        $st->bind_param('s', $tag);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return $row ? ['id_jogo' => (int) $row['id_jogo'], 'status_jogo' => $row['status_jogo']] : \null;
    }
    /**
     * Cria o jogo de ranking para uma modalidade individual.
     */
    public static function criarJogo(\mysqli $conn, int $idModalidade): int
    {
        $tag = \App\Modules\Competicoes\Domain\IndividualRules::tag($idModalidade);
        $idLocal = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::resolverIdLocal($conn);
        $st = $conn->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)\r\n         VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)");
        $st->bind_param('sii', $tag, $idModalidade, $idLocal);
        $st->execute();
        $idJogo = (int) $conn->insert_id;
        $st->close();
        return $idJogo;
    }
    /**
     * Salva o ranking (1º, 2º, 3º lugar) para uma modalidade individual.
     * Na primeira finalização do jogo, soma os pontos (ponto_1/2/3_lugar do interclasse)
     * na `pontuacao_turma` das turmas dos 3 colocados.
     *
     * @param array{primeiro:int, segundo:int, terceiro:int} $ranking IDs dos usuários
     */
    public static function salvarRanking(\mysqli $conn, int $idModalidade, array $ranking): array
    {
        // Validações
        if (empty($ranking['primeiro']) || empty($ranking['segundo']) || empty($ranking['terceiro'])) {
            throw new \RuntimeException('É necessário informar o 1º, 2º e 3º lugar.');
        }
        // Verifica se os participantes são válidos
        $participantes = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarParticipantes($conn, $idModalidade);
        $idsValidos = \array_column($participantes, 'id_usuario');
        foreach (['primeiro', 'segundo', 'terceiro'] as $posicao) {
            if (!\in_array($ranking[$posicao], $idsValidos, \true)) {
                throw new \RuntimeException("O participante do {$posicao} lugar não é válido para esta modalidade.");
            }
        }
        // Verifica duplicados
        $idsUnicos = \array_unique([$ranking['primeiro'], $ranking['segundo'], $ranking['terceiro']]);
        if (\count($idsUnicos) !== 3) {
            throw new \RuntimeException('Os participantes do 1º, 2º e 3º lugar devem ser diferentes.');
        }
        $posicoes = [1 => (int) $ranking['primeiro'], 2 => (int) $ranking['segundo'], 3 => (int) $ranking['terceiro']];
        $equipesPorPosicao = [];
        foreach ($posicoes as $posicao => $idUsuario) {
            $equipe = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarEquipeUsuario($conn, $idUsuario, $idModalidade);
            if ($equipe === null) {
                throw new \RuntimeException("Usuário {$idUsuario} não possui equipe nesta modalidade.");
            }
            $equipesPorPosicao[$posicao] = $equipe;
        }
        // Busca ou cria o jogo
        $jogo = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarJogoExistente($conn, $idModalidade);
        $creditosAnteriores = null;
        if ($jogo === \null) {
            $idJogo = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogo($conn, $idModalidade);
            $jaConcluido = \false;
        } else {
            $idJogo = $jogo['id_jogo'];
            $jaConcluido = $jogo['status_jogo'] === 'Concluido' || $jogo['status_jogo'] === 'Finalizado';
            $editionStatement = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
            $editionStatement->bind_param('i', $idModalidade);
            $editionStatement->execute();
            $editionId = (int) $editionStatement->get_result()->fetch_column();
            $editionStatement->close();
            if ($editionId <= 0) {
                throw new \RuntimeException('Modalidade sem edição para reconciliação individual.');
            }
            $creditosAnteriores = (new MysqliPodioRepository($conn))->carregarBloqueados($editionId, $idModalidade);
            if ($jaConcluido && \count(\array_filter($creditosAnteriores, static fn (array $credito): bool => (int) ($credito['posicao'] ?? 0) <= 3)) < 3) {
                throw new \RuntimeException('Pódio individual legado sem origem conferida; adote os créditos antes de retificar.');
            }
            // Limpa partidas existentes
            $stDel = $conn->prepare('DELETE FROM partidas WHERE jogos_id_jogo = ?');
            $stDel->bind_param('i', $idJogo);
            $stDel->execute();
            $stDel->close();
        }
        // Insere as 3 posições
        $stIns = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, usuarios_id_usuario, resultado_partida, status_partida)\r\n         VALUES (?, ?, ?, ?, '1')");
        foreach ($posicoes as $posicao => $idUsuario) {
            $equipe = $equipesPorPosicao[$posicao];
            $stIns->bind_param('iiii', $idJogo, $equipe, $idUsuario, $posicao);
            $stIns->execute();
        }
        $stIns->close();
        // O crédito de pódio é uma fonte própria e só é aplicado como diferença.
        self::registrarCreditoPodio($conn, $idModalidade, $idJogo, $equipesPorPosicao, $posicoes, $jaConcluido, $creditosAnteriores);
        // Atualiza status do jogo para Concluído
        $stUpd = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
        $stUpd->bind_param('i', $idJogo);
        $stUpd->execute();
        $stUpd->close();
        return ['success' => \true, 'message' => 'Ranking registrado com sucesso.', 'id_jogo' => $idJogo];
    }
    /**
     * Soma os pontos dos 3 colocados à pontuação das turmas no ranking geral.
     * Espelha o comportamento de `lancar_resultado.php`: só roda na primeira finalização.
     *
     * @param array{1:int, 2:int, 3:int} $equipesPorPosicao id_equipe de cada posição
     */
    public static function aplicarPontos(\mysqli $conn, int $idModalidade, array $equipesPorPosicao): void
    {
        $jogo = self::buscarJogoExistente($conn, $idModalidade);
        if ($jogo !== null) {
            self::registrarCreditoPodio($conn, $idModalidade, (int) $jogo['id_jogo'], $equipesPorPosicao, [], false);
        }
    }

    /**
     * @param array<int, int> $equipesPorPosicao
     * @param array<int, int> $usuariosPorPosicao
     */
    /** @param list<array<string, mixed>>|null $creditosAnteriores */
    private static function registrarCreditoPodio(\mysqli $conn, int $idModalidade, int $idJogo, array $equipesPorPosicao, array $usuariosPorPosicao, bool $jaConcluido, ?array $creditosAnteriores = null): void
    {
        $modality = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $modality->bind_param('i', $idModalidade);
        $modality->execute();
        $editionId = (int) $modality->get_result()->fetch_column();
        $modality->close();
        if ($editionId <= 0) {
            throw new \RuntimeException('Modalidade sem edição para crédito de pódio.');
        }
        $repository = new MysqliPodioRepository($conn);
        $old = $creditosAnteriores ?? $repository->carregarBloqueados($editionId, $idModalidade);
        $oldByPosition = [];
        foreach ($old as $credit) {
            if ((int) $credit['posicao'] <= 3) {
                $oldByPosition[(int) $credit['posicao']] = $credit;
            }
        }
        if ($jaConcluido && count($oldByPosition) < 3) {
            throw new \RuntimeException('Pódio individual legado sem origem conferida; adote os créditos antes de retificar.');
        }
        $pointsStatement = $conn->prepare('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ? LIMIT 1');
        $pointsStatement->bind_param('i', $editionId);
        $pointsStatement->execute();
        $points = $pointsStatement->get_result()->fetch_assoc() ?: [];
        $pointsStatement->close();
        $new = [];
        foreach ([1, 2, 3] as $position) {
            $teamId = (int) ($equipesPorPosicao[$position] ?? 0);
            $classId = $repository->turmaDaEquipe($teamId, $idModalidade);
            if ($teamId <= 0 || $classId === null) {
                throw new \RuntimeException('Participante sem turma para crédito de pódio.');
            }
            $existing = $oldByPosition[$position] ?? null;
            $new[] = [
                'posicao' => $position,
                'id_turma' => $classId,
                'id_equipe' => $teamId,
                'id_usuario' => $usuariosPorPosicao[$position] ?? null,
                'id_jogo' => $idJogo,
                'pontos' => $existing !== null ? (int) $existing['pontos'] : (int) ($points['ponto_' . $position . '_lugar'] ?? 0),
                'ativo' => 1,
                'origem_registro' => $existing !== null ? (string) $existing['origem_registro'] : 'novo',
            ];
        }
        $deltas = PodioRules::deltas($old, $new);
        $repository->substituirPosicoes($editionId, $idModalidade, $new);
        $repository->aplicarDeltas($deltas);
    }
    /**
     * Busca a equipe de um usuário para uma modalidade específica.
     */
    public static function buscarEquipeUsuario(\mysqli $conn, int $idUsuario, int $idModalidade): ?int
    {
        $st = $conn->prepare('SELECT e.id_equipe
         FROM equipes e
         INNER JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = e.id_equipe
         WHERE ehu.usuarios_id_usuario = ?
           AND e.modalidades_id_modalidade = ?
           AND e.status_equipe = \'1\'
         LIMIT 1');
        $st->bind_param('ii', $idUsuario, $idModalidade);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return $row ? (int) $row['id_equipe'] : \null;
    }
    /**
     * Monta JSON com o ranking de uma modalidade individual para o frontend.
     *
     * @return array{success:bool, ranking:list<array{posicao:int, id_usuario:int, nome_usuario:string, nome_turma:string, nome_fantasia_turma:string}>, jogo:array|null}
     */
    public static function montarJsonRanking(\mysqli $conn, int $idModalidade): array
    {
        $jogo = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarJogoExistente($conn, $idModalidade);
        if ($jogo === \null) {
            return ['success' => \true, 'ranking' => [], 'jogo' => \null];
        }
        $sql = 'SELECT p.resultado_partida, p.equipes_id_equipe,
                   u.id_usuario, u.nome_usuario,
                   t.nome_turma, t.nome_fantasia_turma
            FROM partidas p
            INNER JOIN usuarios u ON u.id_usuario = p.usuarios_id_usuario
            INNER JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
            INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
            WHERE p.jogos_id_jogo = ?
              AND p.resultado_partida BETWEEN 1 AND 3
            ORDER BY p.resultado_partida ASC';
        $st = $conn->prepare($sql);
        $st->bind_param('i', $jogo['id_jogo']);
        $st->execute();
        $rows = $st->get_result()->fetch_all(\MYSQLI_ASSOC);
        $st->close();
        $ranking = [];
        foreach ($rows as $row) {
            $ranking[] = ['posicao' => (int) $row['resultado_partida'], 'id_usuario' => (int) $row['id_usuario'], 'nome_usuario' => $row['nome_usuario'], 'nome_turma' => $row['nome_turma'], 'nome_fantasia_turma' => $row['nome_fantasia_turma']];
        }
        $stJ = $conn->prepare('SELECT j.id_jogo, j.nome_jogo, j.data_jogo, j.inicio_jogo, j.termino_jogo,
                j.status_jogo, j.locais_id_local, j.modalidades_id_modalidade,
                m.nome_modalidade
         FROM jogos j
         LEFT JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade
         WHERE j.id_jogo = ? LIMIT 1');
        $stJ->bind_param('i', $jogo['id_jogo']);
        $stJ->execute();
        $jogoDetalhes = $stJ->get_result()->fetch_assoc();
        $stJ->close();
        return ['success' => \true, 'ranking' => $ranking, 'jogo' => $jogoDetalhes ? ['id_jogo' => (int) $jogoDetalhes['id_jogo'], 'nome_jogo' => $jogoDetalhes['nome_jogo'], 'data_jogo' => $jogoDetalhes['data_jogo'], 'inicio_jogo' => $jogoDetalhes['inicio_jogo'], 'termino_jogo' => $jogoDetalhes['termino_jogo'], 'status_jogo' => $jogoDetalhes['status_jogo'], 'locais_id_local' => $jogoDetalhes['locais_id_local'], 'nome_modalidade' => $jogoDetalhes['nome_modalidade']] : \null];
    }
    /**
     * Cria ou atualiza o jogo da modalidade individual para exibição na Agenda.
     * Vincula todas as equipes ativas dessa modalidade ao jogo na tabela `partidas`.
     *
     * @return array{success:bool, message:string, id_jogo:int, jogos_criados:int}
     */
    public static function criarJogoAgenda(\mysqli $conn, int $idModalidade): array
    {
        // Buscar equipes ativas desta modalidade
        $stEq = $conn->prepare("SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = ? AND status_equipe = '1'");
        if (!$stEq) {
            throw new \RuntimeException($conn->error);
        }
        $stEq->bind_param('i', $idModalidade);
        $stEq->execute();
        $rowsEq = $stEq->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stEq->close();
        if (empty($rowsEq)) {
            throw new \RuntimeException('É necessário ao menos uma equipe ativa cadastrada nesta modalidade.');
        }
        $equipeIds = \array_map(static fn (array $r): int => (int) $r['id_equipe'], $rowsEq);
        // Buscar ou criar o jogo de tag IND:{idModalidade}
        $jogo = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarJogoExistente($conn, $idModalidade);
        if ($jogo === \null) {
            $idJogo = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogo($conn, $idModalidade);
        } else {
            $idJogo = $jogo['id_jogo'];
        }
        // Buscar equipes já vinculadas em partidas deste jogo
        $stP = $conn->prepare('SELECT DISTINCT equipes_id_equipe FROM partidas WHERE jogos_id_jogo = ?');
        $stP->bind_param('i', $idJogo);
        $stP->execute();
        $rowsPart = $stP->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stP->close();
        $equipesExistentes = \array_map(static fn (array $r): int => (int) $r['equipes_id_equipe'], $rowsPart);
        $stIns = $conn->prepare("INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, '1')");
        foreach ($equipeIds as $idEquipe) {
            if (!\in_array($idEquipe, $equipesExistentes, \true)) {
                $stIns->bind_param('ii', $idJogo, $idEquipe);
                $stIns->execute();
            }
        }
        $stIns->close();
        return ['success' => \true, 'message' => 'Jogo de modalidade individual gerado para a agenda.', 'id_jogo' => $idJogo, 'jogos_criados' => 1];
    }
}
