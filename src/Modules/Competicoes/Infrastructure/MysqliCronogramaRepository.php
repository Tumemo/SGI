<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\CronogramaRepository;
use App\Modules\Competicoes\Domain\CronogramaBracketPlanner;
use App\Modules\Competicoes\Domain\CronogramaRules;
use App\Shared\Database\Transaction;
use InvalidArgumentException;
use mysqli;
use RuntimeException;

final class MysqliCronogramaRepository implements CronogramaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function state(int $editionId): array
    {
        $edition = $this->one('SELECT i.id_interclasse, p.cronograma_status, p.inscricoes_status, p.cronograma_versao, p.inscricoes_abertura, p.inscricoes_encerramento FROM interclasses i INNER JOIN interclasse_planejamentos p ON p.id_interclasse = i.id_interclasse WHERE i.id_interclasse = ? LIMIT 1', 'i', [$editionId]);
        if ($edition === null) {
            throw new InvalidArgumentException('Edição não encontrada.');
        }
        $modalities = $this->all('SELECT m.id_modalidade, m.nome_modalidade, m.categorias_id_categoria, mp.equipes_planejadas, mp.min_inscritos_equipe, mp.max_inscritos_equipe, mp.formato_participacao, mp.duracao_prevista_min, mp.descanso_min, (SELECT COUNT(*) FROM equipes e WHERE e.modalidades_id_modalidade = m.id_modalidade AND e.status_equipe = \'1\') AS equipes_criadas FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' ORDER BY m.id_modalidade', 'i', [$editionId]);
        $commitments = $this->all('SELECT id_compromisso, id_modalidade, id_equipe, chave_tag, DATE_FORMAT(data_compromisso, \'%Y-%m-%d\') AS data_compromisso, TIME_FORMAT(inicio_compromisso, \'%H:%i:%s\') AS inicio_compromisso, TIME_FORMAT(termino_compromisso, \'%H:%i:%s\') AS termino_compromisso, id_local, condicional, cronograma_versao FROM cronograma_compromissos WHERE id_interclasse = ? AND cronograma_versao = ? ORDER BY data_compromisso, inicio_compromisso, id_local, id_compromisso', 'ii', [$editionId, (int) $edition['cronograma_versao']]);
        $nodes = $this->all('SELECT id_no, id_modalidade, id_turma, chave_tag, tipo_no, fase_largura, slot, origem_a_tag, origem_b_tag, id_equipe_a, id_equipe_b, cronograma_versao FROM cronograma_nos WHERE id_interclasse = ? AND cronograma_versao = ? ORDER BY id_modalidade, id_turma, fase_largura DESC, slot', 'ii', [$editionId, (int) $edition['cronograma_versao']]);
        $incomplete = $this->all('SELECT e.id_equipe, e.modalidades_id_modalidade AS id_modalidade, e.turmas_id_turma AS id_turma, ep.min_inscritos, COUNT(ehu.usuarios_id_usuario) AS inscritos FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe LEFT JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = e.id_equipe INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' AND e.status_equipe = \'1\' GROUP BY e.id_equipe, e.modalidades_id_modalidade, e.turmas_id_turma, ep.min_inscritos HAVING COUNT(ehu.usuarios_id_usuario) < ep.min_inscritos ORDER BY e.modalidades_id_modalidade, e.turmas_id_turma, e.id_equipe', 'i', [$editionId]);
        $edition['modalidades'] = $modalities;
        $edition['compromissos'] = $commitments;
        $edition['nos'] = $nodes;
        $edition['equipes_incompletas'] = $incomplete;
        $edition['operacao'] = [
            'versao_publicada' => (int) $edition['cronograma_versao'],
            'requer_repreparo_mesario' => (string) $edition['cronograma_status'] === CronogramaRules::REVISAO,
            'fila_offline_preservada' => true,
        ];
        return $edition;
    }

    public function prepareTeams(int $editionId, int $userId): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            if (!in_array((string) $edition['cronograma_status'], [CronogramaRules::RASCUNHO, CronogramaRules::REVISAO], true) || (string) $edition['inscricoes_status'] !== 'fechadas') {
                throw new InvalidArgumentException('As equipes planejadas só podem ser preparadas no rascunho fechado.');
            }
            $modalities = $this->all('SELECT m.id_modalidade, m.nome_modalidade, m.categorias_id_categoria, mp.equipes_planejadas, mp.min_inscritos_equipe, mp.max_inscritos_equipe FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' ORDER BY m.id_modalidade FOR UPDATE', 'i', [$editionId]);
            $created = 0;
            $existing = 0;
            foreach ($modalities as $modality) {
                $quantity = (int) ($modality['equipes_planejadas'] ?? 0);
                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Todas as modalidades ativas precisam de quantidade planejada antes da preparação.');
                }
                $classes = $this->all('SELECT id_turma, nome_turma FROM turmas WHERE interclasses_id_interclasse = ? AND categorias_id_categoria = ? AND status_turma = \'1\' ORDER BY id_turma FOR UPDATE', 'ii', [$editionId, (int) $modality['categorias_id_categoria']]);
                foreach ($classes as $class) {
                    $teams = $this->all('SELECT e.id_equipe, ep.ordem_planejada FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ? AND e.status_equipe = \'1\' ORDER BY ep.ordem_planejada, e.id_equipe FOR UPDATE', 'ii', [(int) $modality['id_modalidade'], (int) $class['id_turma']]);
                    $byOrder = [];
                    foreach ($teams as $team) {
                        $order = (int) ($team['ordem_planejada'] ?? 0);
                        if ($order > 0) {
                            $byOrder[$order] = true;
                        }
                        if ($order > $quantity) {
                            $linked = $this->one('SELECT 1 FROM equipes_has_usuarios WHERE equipes_id_equipe = ? LIMIT 1', 'i', [(int) $team['id_equipe']]);
                            if ($linked !== null) {
                                throw new InvalidArgumentException('A redução da quantidade não pode remover uma equipe com inscritos.');
                            }
                            $linked = $this->one('SELECT 1 FROM jogos j INNER JOIN partidas p ON p.jogos_id_jogo = j.id_jogo WHERE p.equipes_id_equipe = ? OR j.modalidades_id_modalidade = ? LIMIT 1', 'ii', [(int) $team['id_equipe'], (int) $modality['id_modalidade']]);
                            if ($linked !== null) {
                                throw new InvalidArgumentException('A redução da quantidade não pode remover uma equipe com jogos ou histórico.');
                            }
                            $deactivate = $this->prepare("UPDATE equipes SET status_equipe = '0' WHERE id_equipe = ?");
                            $teamIdToDeactivate = (int) $team['id_equipe'];
                            $deactivate->bind_param('i', $teamIdToDeactivate);
                            if (!$deactivate->execute()) {
                                $deactivate->close();
                                throw new RuntimeException('Não foi possível reduzir as equipes planejadas.');
                            }
                            $deactivate->close();
                            $meta = $this->prepare('UPDATE equipe_planejamentos SET planejada = 0 WHERE id_equipe = ?');
                            $meta->bind_param('i', $teamIdToDeactivate);
                            $meta->execute();
                            $meta->close();
                        }
                    }
                    for ($order = 1; $order <= $quantity; $order++) {
                        if (isset($byOrder[$order])) {
                            $existing++;
                            continue;
                        }
                        $name = trim((string) $class['nome_turma']) . ' ' . trim((string) $modality['nome_modalidade']) . ' - ' . $order;
                        $statement = $this->prepare('INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES (\'1\', ?, ?, ?)');
                        $modalityId = (int) $modality['id_modalidade'];
                        $classId = (int) $class['id_turma'];
                        $min = (int) ($modality['min_inscritos_equipe'] ?? 1);
                        $max = (int) ($modality['max_inscritos_equipe'] ?? 1);
                        $statement->bind_param('iis', $modalityId, $classId, $name);
                        if (!$statement->execute()) {
                            $statement->close();
                            throw new RuntimeException('Não foi possível preparar as equipes da modalidade.');
                        }
                        $statement->close();
                        $teamId = (int) $this->connection->insert_id;
                        $meta = $this->prepare('INSERT INTO equipe_planejamentos (id_equipe, ordem_planejada, planejada, min_inscritos, max_inscritos) VALUES (?, ?, 1, ?, ?)');
                        $meta->bind_param('iiii', $teamId, $order, $min, $max);
                        if (!$meta->execute()) {
                            $meta->close();
                            throw new RuntimeException('Não foi possível gravar a configuração da equipe planejada.');
                        }
                        $meta->close();
                        $created++;
                    }
                }
            }
            Transaction::commit($this->connection);
            return ['success' => true, 'equipes_criadas' => $created, 'equipes_existentes' => $existing];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function generateDraft(int $editionId, int $userId, array $options): array
    {
        $edition = $this->lockEditionForRead($editionId);
        if ((string) $edition['inscricoes_status'] !== 'fechadas' || !in_array((string) $edition['cronograma_status'], [CronogramaRules::RASCUNHO, CronogramaRules::REVISAO], true)) {
            throw new InvalidArgumentException('Gere a agenda somente com as inscrições fechadas e o cronograma em rascunho ou revisão.');
        }
        $startDate = trim((string) ($options['data_inicio'] ?? $options['inicio_data'] ?? ''));
        $endDate = trim((string) ($options['data_fim'] ?? $options['fim_data'] ?? $startDate));
        $startTime = trim((string) ($options['hora_inicio'] ?? '08:00'));
        $endTime = trim((string) ($options['hora_fim'] ?? '18:00'));
        try {
            $cursor = new \DateTimeImmutable($startDate . ' ' . $startTime);
            $lastDate = new \DateTimeImmutable($endDate . ' ' . $endTime);
            new \DateTimeImmutable('2000-01-01 ' . $startTime);
            new \DateTimeImmutable('2000-01-01 ' . $endTime);
        } catch (\Throwable) {
            throw new InvalidArgumentException('A janela da agenda é inválida.');
        }
        if ($cursor >= $lastDate) {
            throw new InvalidArgumentException('A janela da agenda deve ter início anterior ao fim.');
        }
        $durationDefault = $this->positiveOption($options['duracao_min'] ?? 30, 'A duração padrão');
        $gapDefault = $this->nonNegativeOption($options['intervalo_min'] ?? 10, 'O intervalo padrão');
        $localIds = $this->planningLocalIds($editionId, $options['id_locais'] ?? $options['locais'] ?? []);
        if ($localIds === []) {
            throw new InvalidArgumentException('Informe ao menos um local ativo para gerar a agenda.');
        }
        $modalities = $this->all('SELECT m.id_modalidade, m.nome_modalidade, m.categorias_id_categoria, tm.nome_tipo_modalidade, mp.equipes_planejadas, mp.formato_participacao, mp.duracao_prevista_min, mp.descanso_min FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' ORDER BY m.id_modalidade', 'i', [$editionId]);
        if ($modalities === []) {
            throw new InvalidArgumentException('Cadastre e configure ao menos uma modalidade ativa antes de gerar a agenda.');
        }
        $nodes = [];
        $scheduledNodes = [];
        $pendencias = [];
        $occupied = $this->occupiedSlots($editionId, (int) $edition['cronograma_versao']);
        foreach ($modalities as $modality) {
            $modalityId = (int) $modality['id_modalidade'];
            if ($modality['equipes_planejadas'] === null || (int) $modality['equipes_planejadas'] <= 0) {
                throw new InvalidArgumentException('Todas as modalidades ativas precisam de quantidade planejada antes de gerar a agenda.');
            }
            $duration = (int) ($modality['duracao_prevista_min'] ?? 0) > 0 ? (int) $modality['duracao_prevista_min'] : $durationDefault;
            $gap = max($gapDefault, (int) ($modality['descanso_min'] ?? 0));
            $classes = $this->all('SELECT id_turma FROM turmas WHERE interclasses_id_interclasse = ? AND categorias_id_categoria = ? AND status_turma = \'1\' ORDER BY id_turma', 'ii', [$editionId, (int) $modality['categorias_id_categoria']]);
            foreach ($classes as $class) {
                $classId = (int) $class['id_turma'];
                $teamRows = $this->all('SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ? AND e.status_equipe = \'1\' ORDER BY ep.ordem_planejada, e.id_equipe', 'ii', [$modalityId, $classId]);
                $teamIds = array_map(static fn (array $team): int => (int) $team['id_equipe'], $teamRows);
                if ($teamIds === []) {
                    $pendencias[] = ['tipo' => 'equipes', 'id_modalidade' => $modalityId, 'id_turma' => $classId, 'mensagem' => 'A modalidade ainda não possui equipes planejadas para a turma.'];
                    continue;
                }
                $format = strtolower((string) ($modality['nome_tipo_modalidade'] ?? '')) === 'individual' ? 'individual' : strtolower((string) ($modality['formato_participacao'] ?? ''));
                if ($format === 'individual') {
                    foreach ($teamIds as $teamId) {
                        $nodes[] = [
                            'id_modalidade' => $modalityId,
                            'id_turma' => $classId,
                            'chave_tag' => sprintf('PL:%d:IND:%d', $classId, $teamId),
                            'tipo_no' => 'individual',
                            'fase_largura' => 1,
                            'slot' => count($nodes),
                            'origem_a_tag' => null,
                            'origem_b_tag' => null,
                            'id_equipe_a' => $teamId,
                            'id_equipe_b' => null,
                            'equipe_ids' => [$teamId],
                            'condicional' => 0,
                        ];
                        $scheduledNodes[] = [$nodes[array_key_last($nodes)], $duration, $gap];
                    }
                } else {
                    foreach (CronogramaBracketPlanner::plan($modalityId, $classId, $teamIds) as $node) {
                        $node['id_modalidade'] = $modalityId;
                        $node['id_turma'] = $classId;
                        $nodes[] = $node;
                        if ($node['tipo_no'] !== 'bye') {
                            $scheduledNodes[] = [$node, $duration, $gap];
                        }
                    }
                }
            }
        }
        $commitments = [];
        $localIndex = 0;
        foreach ($scheduledNodes as [$node, $duration, $gap]) {
            $slot = $this->nextDraftSlot($cursor, $lastDate, $duration, $gap, $localIds, $localIndex, $startTime, $endTime, $occupied);
            if ($slot === null) {
                $pendencias[] = ['tipo' => 'janela', 'id_modalidade' => (int) $node['id_modalidade'], 'id_turma' => (int) $node['id_turma'], 'chave_tag' => (string) $node['chave_tag'], 'mensagem' => 'A janela e os locais não comportam todos os compromissos.'];
                continue;
            }
            [$cursor, $localIndex, $date, $start, $end, $local] = $slot;
            $commitments[] = [
                'id_modalidade' => (int) $node['id_modalidade'],
                'id_equipe' => (int) ($node['id_equipe_a'] ?? 0),
                'chave_tag' => (string) $node['chave_tag'],
                'data_compromisso' => $date,
                'inicio_compromisso' => $start,
                'termino_compromisso' => $end,
                'id_local' => $local,
                'condicional' => (int) ($node['condicional'] ?? 0),
            ];
            $occupied[] = ['data' => $date, 'inicio' => $start, 'termino' => $end, 'local' => $local];
        }
        return ['success' => $pendencias === [], 'cronograma_versao' => (int) $edition['cronograma_versao'], 'compromissos' => $commitments, 'nos' => $nodes, 'pendencias' => $pendencias];
    }

    public function publish(int $editionId, int $userId, int $expectedRevision, array $commitments, array $nodes = []): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            $this->assertRevision($edition, $expectedRevision);
            if ((string) $edition['inscricoes_status'] !== 'fechadas' || !in_array((string) $edition['cronograma_status'], [CronogramaRules::RASCUNHO, CronogramaRules::REVISAO], true)) {
                throw new InvalidArgumentException('Feche as inscrições e mantenha o cronograma em rascunho ou revisão antes de publicar.');
            }
            $modalities = $this->all('SELECT m.id_modalidade, mp.equipes_planejadas FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' FOR UPDATE', 'i', [$editionId]);
            if ($modalities === []) {
                throw new InvalidArgumentException('Cadastre ao menos uma modalidade ativa.');
            }
            foreach ($modalities as $modality) {
                if ($modality['equipes_planejadas'] === null || (int) $modality['equipes_planejadas'] <= 0) {
                    throw new InvalidArgumentException('Todas as modalidades ativas precisam de quantidade planejada de equipes.');
                }
            }
            if ($nodes === []) {
                throw new InvalidArgumentException('A publicação exige os nós do cronograma gerado.');
            }
            $this->validateCommitments($editionId, $modalities, $commitments, $nodes);
            $version = (int) $edition['cronograma_versao'] + 1;
            $this->insertNodes($editionId, $version, $nodes);
            $this->insertCommitments($editionId, $version, $commitments);
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET cronograma_status = 'publicado', inscricoes_status = 'fechadas', cronograma_versao = ? WHERE id_interclasse = ? AND cronograma_versao = ?");
            $statement->bind_param('iii', $version, $editionId, $expectedRevision);
            if (!$statement->execute() || $statement->affected_rows !== 1) {
                $statement->close();
                throw new RuntimeException('O cronograma foi alterado durante a publicação.');
            }
            $statement->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'cronograma_status' => CronogramaRules::PUBLICADO, 'inscricoes_status' => 'fechadas', 'cronograma_versao' => $version, 'compromissos' => count($commitments), 'nos' => count($nodes)];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function openRegistrations(int $editionId, int $userId, int $expectedRevision, ?string $opening, ?string $closing): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            $this->assertRevision($edition, $expectedRevision);
            if ((string) $edition['cronograma_status'] !== CronogramaRules::PUBLICADO) {
                throw new InvalidArgumentException('Publique o cronograma antes de abrir as inscrições.');
            }
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET inscricoes_status = 'abertas', inscricoes_abertura = ?, inscricoes_encerramento = ? WHERE id_interclasse = ? AND cronograma_versao = ?");
            $statement->bind_param('ssii', $opening, $closing, $editionId, $expectedRevision);
            if (!$statement->execute() || $statement->affected_rows !== 1) {
                $statement->close();
                throw new RuntimeException('O cronograma foi alterado durante a abertura das inscrições.');
            }
            $statement->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'cronograma_status' => CronogramaRules::PUBLICADO, 'inscricoes_status' => 'abertas', 'cronograma_versao' => $expectedRevision];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function closeRegistrations(int $editionId, int $userId, int $expectedRevision): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            $this->assertRevision($edition, $expectedRevision);
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET inscricoes_status = 'encerradas' WHERE id_interclasse = ? AND cronograma_versao = ?");
            $statement->bind_param('ii', $editionId, $expectedRevision);
            if (!$statement->execute() || $statement->affected_rows !== 1) {
                $statement->close();
                throw new RuntimeException('Não foi possível encerrar as inscrições.');
            }
            $statement->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'cronograma_status' => (string) $edition['cronograma_status'], 'inscricoes_status' => 'encerradas', 'cronograma_versao' => $expectedRevision];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function review(int $editionId, int $userId, int $expectedRevision): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            $this->assertRevision($edition, $expectedRevision);
            if ((string) $edition['cronograma_status'] !== CronogramaRules::PUBLICADO) {
                throw new InvalidArgumentException('Somente um cronograma publicado pode entrar em revisão.');
            }
            $nextVersion = (int) $edition['cronograma_versao'] + 1;
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET cronograma_status = 'revisao', inscricoes_status = 'fechadas', cronograma_versao = ? WHERE id_interclasse = ? AND cronograma_versao = ?");
            $statement->bind_param('iii', $nextVersion, $editionId, $expectedRevision);
            if (!$statement->execute() || $statement->affected_rows !== 1) {
                $statement->close();
                throw new RuntimeException('O cronograma foi alterado durante a revisão.');
            }
            $statement->close();
            $impact = $this->all('SELECT e.id_equipe, e.modalidades_id_modalidade AS id_modalidade, e.turmas_id_turma AS id_turma, ep.min_inscritos, COUNT(ehu.usuarios_id_usuario) AS inscritos FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe LEFT JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = e.id_equipe INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' AND e.status_equipe = \'1\' GROUP BY e.id_equipe, e.modalidades_id_modalidade, e.turmas_id_turma, ep.min_inscritos HAVING COUNT(ehu.usuarios_id_usuario) < ep.min_inscritos ORDER BY e.id_equipe', 'i', [$editionId]);
            Transaction::commit($this->connection);
            return ['success' => true, 'cronograma_status' => CronogramaRules::REVISAO, 'inscricoes_status' => 'fechadas', 'cronograma_versao' => $nextVersion, 'versao_suspensa' => $expectedRevision, 'equipes_incompletas' => $impact];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function materializeNode(int $editionId, int $userId, string $tag): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            if ((string) $edition['cronograma_status'] !== CronogramaRules::PUBLICADO || !in_array((string) $edition['inscricoes_status'], ['abertas', 'encerradas'], true)) {
                throw new InvalidArgumentException('A materialização exige cronograma publicado e inscrições abertas ou encerradas.');
            }
            $node = $this->one('SELECT id_no, id_modalidade, id_turma, tipo_no, chave_tag, origem_a_tag, origem_b_tag FROM cronograma_nos WHERE id_interclasse = ? AND cronograma_versao = ? AND chave_tag = ? LIMIT 1 FOR UPDATE', 'iis', [$editionId, (int) $edition['cronograma_versao'], $tag]);
            if ($node === null) {
                throw new InvalidArgumentException('Nó do cronograma não encontrado na versão publicada.');
            }
            $existing = $this->one('SELECT id_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = ? LIMIT 1 FOR UPDATE', 'is', [(int) $node['id_modalidade'], $tag]);
            if ($existing !== null) {
                Transaction::commit($this->connection);
                return ['success' => true, 'materializado' => true, 'id_jogo' => (int) $existing['id_jogo'], 'id_modalidade' => (int) $node['id_modalidade'], 'chave_tag' => $tag, 'idempotente' => true];
            }
            if ((string) $node['tipo_no'] === 'bye') {
                Transaction::commit($this->connection);
                return ['success' => true, 'materializado' => false, 'bye' => true, 'chave_tag' => $tag, 'mensagem' => 'BYE estrutural não cria partida física.'];
            }
            $participants = $this->plannedNodeParticipants($editionId, (int) $node['id_modalidade'], $node);
            if ($participants === null) {
                Transaction::commit($this->connection);
                return ['success' => false, 'materializado' => false, 'aguardando_resultado' => true, 'chave_tag' => $tag];
            }
            $required = (string) $node['tipo_no'] === 'individual' ? 1 : 2;
            if (count($participants) < $required || !$this->teamsHaveRoster($participants)) {
                Transaction::commit($this->connection);
                return ['success' => false, 'materializado' => false, 'aguardando_elenco' => true, 'chave_tag' => $tag];
            }
            $commitment = $this->one('SELECT data_compromisso, inicio_compromisso, termino_compromisso, id_local FROM cronograma_compromissos WHERE id_interclasse = ? AND id_modalidade = ? AND cronograma_versao = ? AND chave_tag = ? LIMIT 1', 'iiis', [$editionId, (int) $node['id_modalidade'], (int) $edition['cronograma_versao'], $tag]);
            if ($commitment === null) {
                throw new InvalidArgumentException('O nó não possui compromisso físico publicado.');
            }
            $statement = $this->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, ?, ?, ?, 'Agendado', ?, ?)");
            $modality = (int) $node['id_modalidade'];
            $local = (int) $commitment['id_local'];
            $date = (string) $commitment['data_compromisso'];
            $start = (string) $commitment['inicio_compromisso'];
            $end = (string) $commitment['termino_compromisso'];
            $statement->bind_param('ssssii', $tag, $date, $start, $end, $modality, $local);
            if (!$statement->execute()) {
                $error = $statement->error;
                $statement->close();
                throw new RuntimeException($error !== '' ? $error : 'Não foi possível materializar o jogo planejado.');
            }
            $gameId = (int) $this->connection->insert_id;
            $statement->close();
            $partida = $this->prepare('INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida) VALUES (?, ?, 0, \'1\')');
            foreach ($participants as $teamId) {
                $partida->bind_param('ii', $gameId, $teamId);
                if (!$partida->execute()) {
                    $error = $partida->error;
                    $partida->close();
                    throw new RuntimeException($error !== '' ? $error : 'Não foi possível materializar as equipes do jogo planejado.');
                }
            }
            $partida->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'materializado' => true, 'id_jogo' => $gameId, 'id_modalidade' => $modality, 'chave_tag' => $tag, 'equipes' => $participants];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    /** @param array<string,mixed> $node @return list<int>|null */
    private function plannedNodeParticipants(int $editionId, int $modalityId, array $node): ?array
    {
        $origins = array_values(array_filter([(string) ($node['origem_a_tag'] ?? ''), (string) ($node['origem_b_tag'] ?? '')], static fn (string $origin): bool => $origin !== ''));
        if ($origins === []) {
            $rows = $this->all('SELECT id_equipe FROM cronograma_no_equipes WHERE id_no = ? ORDER BY id_equipe', 'i', [(int) $node['id_no']]);
            return array_map(static fn (array $row): int => (int) $row['id_equipe'], $rows);
        }
        $participants = [];
        foreach ($origins as $origin) {
            $game = $this->one('SELECT id_jogo, status_jogo FROM jogos WHERE modalidades_id_modalidade = ? AND nome_jogo = ? LIMIT 1', 'is', [$modalityId, $origin]);
            if ($game !== null) {
                $winner = $this->one("SELECT equipes_id_equipe AS id_equipe FROM partidas WHERE jogos_id_jogo = ? AND status_partida = '1' ORDER BY resultado_partida DESC, id_partida ASC LIMIT 1", 'i', [(int) $game['id_jogo']]);
                if (!in_array((string) $game['status_jogo'], ['Concluido', 'Finalizado'], true) || $winner === null) {
                    return null;
                }
                $participants[] = (int) $winner['id_equipe'];
                continue;
            }
            $child = $this->one('SELECT id_no, tipo_no FROM cronograma_nos WHERE id_interclasse = ? AND id_modalidade = ? AND cronograma_versao = (SELECT cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ?) AND chave_tag = ? LIMIT 1', 'iiis', [$editionId, $modalityId, $editionId, $origin]);
            if ($child === null || (string) $child['tipo_no'] !== 'bye') {
                return null;
            }
            $team = $this->one('SELECT id_equipe FROM cronograma_no_equipes WHERE id_no = ? ORDER BY id_equipe LIMIT 1', 'i', [(int) $child['id_no']]);
            if ($team === null) {
                return null;
            }
            $participants[] = (int) $team['id_equipe'];
        }
        return array_values(array_unique(array_filter($participants, static fn (int $id): bool => $id > 0)));
    }

    /** @param list<int> $teamIds */
    private function teamsHaveRoster(array $teamIds): bool
    {
        foreach ($teamIds as $teamId) {
            if ($this->one('SELECT 1 FROM equipes_has_usuarios WHERE equipes_id_equipe = ? LIMIT 1', 'i', [$teamId]) === null) {
                return false;
            }
        }
        return true;
    }

    public function commitmentsForTeams(int $editionId, array $teamIds): array
    {
        $teamIds = array_values(array_unique(array_filter(array_map('intval', $teamIds), static fn (int $id): bool => $id > 0)));
        if ($teamIds === []) {
            return [];
        }
        $state = $this->one('SELECT cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ? LIMIT 1', 'i', [$editionId]);
        $version = (int) ($state['cronograma_versao'] ?? 0);
        $marks = implode(',', array_fill(0, count($teamIds), '?'));
        $types = 'ii' . str_repeat('i', count($teamIds));
        $params = array_merge([$editionId, $version], $teamIds);
        return $this->all("SELECT DISTINCT cc.id_compromisso, cc.id_modalidade, COALESCE(cne.id_equipe, cc.id_equipe) AS id_equipe, cc.chave_tag, DATE_FORMAT(cc.data_compromisso, '%Y-%m-%d') AS data_compromisso, TIME_FORMAT(cc.inicio_compromisso, '%H:%i:%s') AS inicio_compromisso, TIME_FORMAT(cc.termino_compromisso, '%H:%i:%s') AS termino_compromisso, cc.id_local, cc.condicional FROM cronograma_compromissos cc LEFT JOIN cronograma_nos cn ON cn.id_interclasse = cc.id_interclasse AND cn.id_modalidade = cc.id_modalidade AND cn.cronograma_versao = cc.cronograma_versao AND cn.chave_tag = cc.chave_tag LEFT JOIN cronograma_no_equipes cne ON cne.id_no = cn.id_no AND cne.id_equipe IN ($marks) WHERE cc.id_interclasse = ? AND cc.cronograma_versao = ? AND (cc.id_equipe IN ($marks) OR cne.id_equipe IS NOT NULL)", str_repeat('i', count($teamIds)) . 'ii' . str_repeat('i', count($teamIds)), array_merge($teamIds, [$editionId, $version], $teamIds));
    }

    /** @param list<array<string,mixed>> $nodes */
    private function insertNodes(int $editionId, int $version, array $nodes): void
    {
        if ($nodes === []) {
            throw new InvalidArgumentException('A árvore do cronograma não pode ficar vazia.');
        }
        $statement = $this->prepare('INSERT INTO cronograma_nos (id_interclasse, id_modalidade, id_turma, chave_tag, tipo_no, fase_largura, slot, origem_a_tag, origem_b_tag, id_equipe_a, id_equipe_b, cronograma_versao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $mapping = $this->prepare('INSERT INTO cronograma_no_equipes (id_no, id_equipe, lado) VALUES (?, ?, ?)');
        foreach ($nodes as $node) {
            $modality = (int) ($node['id_modalidade'] ?? 0);
            $class = (int) ($node['id_turma'] ?? 0);
            $tag = trim((string) ($node['chave_tag'] ?? ''));
            $kind = trim((string) ($node['tipo_no'] ?? ''));
            $width = (int) ($node['fase_largura'] ?? 0);
            $slot = (int) ($node['slot'] ?? 0);
            $teamA = isset($node['id_equipe_a']) ? (int) $node['id_equipe_a'] : null;
            $teamB = isset($node['id_equipe_b']) ? (int) $node['id_equipe_b'] : null;
            if ($modality <= 0 || $class <= 0 || $tag === '' || !in_array($kind, ['normal', 'bye', 'individual'], true) || $width <= 0 || $slot < 0) {
                $statement->close();
                $mapping->close();
                throw new InvalidArgumentException('Nó do cronograma inválido.');
            }
            $originA = ($node['origem_a_tag'] ?? null) !== null ? trim((string) $node['origem_a_tag']) : null;
            $originB = ($node['origem_b_tag'] ?? null) !== null ? trim((string) $node['origem_b_tag']) : null;
            $statement->bind_param('iiissiissiii', $editionId, $modality, $class, $tag, $kind, $width, $slot, $originA, $originB, $teamA, $teamB, $version);
            if (!$statement->execute()) {
                $error = $statement->error;
                $statement->close();
                $mapping->close();
                throw new RuntimeException($error !== '' ? $error : 'Não foi possível gravar os nós do cronograma.');
            }
            $nodeId = (int) $this->connection->insert_id;
            $teamIds = array_values(array_unique(array_filter(array_map('intval', is_array($node['equipe_ids'] ?? null) ? $node['equipe_ids'] : []), static fn (int $id): bool => $id > 0)));
            foreach ($teamIds as $teamId) {
                $side = $teamId === $teamA ? 'a' : ($teamId === $teamB ? 'b' : 'candidato');
                $mapping->bind_param('iis', $nodeId, $teamId, $side);
                if (!$mapping->execute()) {
                    $error = $mapping->error;
                    $statement->close();
                    $mapping->close();
                    throw new RuntimeException($error !== '' ? $error : 'Não foi possível gravar as equipes candidatas do nó.');
                }
            }
        }
        $statement->close();
        $mapping->close();
    }

    private function insertCommitments(int $editionId, int $version, array $items): void
    {
        if ($items === []) {
            return;
        }
        $statement = $this->prepare('INSERT INTO cronograma_compromissos (id_interclasse, id_modalidade, id_equipe, chave_tag, data_compromisso, inicio_compromisso, termino_compromisso, id_local, condicional, cronograma_versao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $modality = (int) ($item['id_modalidade'] ?? 0);
            $team = (int) ($item['id_equipe'] ?? 0);
            $tag = trim((string) ($item['chave_tag'] ?? ''));
            $date = trim((string) ($item['data_compromisso'] ?? $item['data'] ?? ''));
            $start = trim((string) ($item['inicio_compromisso'] ?? $item['inicio'] ?? ''));
            $end = trim((string) ($item['termino_compromisso'] ?? $item['fim'] ?? ''));
            $local = (int) ($item['id_local'] ?? 0);
            if ($modality <= 0 || $team <= 0 || $tag === '' || $local <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $start) || !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $end)) {
                $statement->close();
                throw new InvalidArgumentException('Compromisso do cronograma inválido.');
            }
            $check = $this->one('SELECT 1 FROM equipes e INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE e.id_equipe = ? AND e.modalidades_id_modalidade = ? AND m.interclasses_id_interclasse = ? AND e.status_equipe = \'1\' LIMIT 1', 'iii', [$team, $modality, $editionId]);
            if ($check === null) {
                $statement->close();
                throw new InvalidArgumentException('A equipe do compromisso não pertence à modalidade e edição.');
            }
            $conditional = !empty($item['condicional']) ? 1 : 0;
            $statement->bind_param('iiissssiii', $editionId, $modality, $team, $tag, $date, $start, $end, $local, $conditional, $version);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível gravar o compromisso do cronograma.');
            }
        }
        $statement->close();
    }

    /** @param list<array<string,mixed>> $modalities @param list<array<string,mixed>> $items @param list<array<string,mixed>> $nodes */
    private function validateCommitments(int $editionId, array $modalities, array $items, array $nodes): void
    {
        if ($items === [] && $nodes === []) {
            throw new InvalidArgumentException('O cronograma precisa ter nós antes da publicação.');
        }
        $modalityIds = array_fill_keys(array_map(static fn (array $row): int => (int) $row['id_modalidade'], $modalities), true);
        $coveredModalities = [];
        $coveredTeams = [];
        $nodesByTag = [];
        foreach ($nodes as $node) {
            $modalityId = (int) ($node['id_modalidade'] ?? 0);
            $classId = (int) ($node['id_turma'] ?? 0);
            $tag = trim((string) ($node['chave_tag'] ?? ''));
            $kind = (string) ($node['tipo_no'] ?? '');
            $candidateIds = array_values(array_unique(array_filter(array_map('intval', is_array($node['equipe_ids'] ?? null) ? $node['equipe_ids'] : []), static fn (int $id): bool => $id > 0)));
            if (!isset($modalityIds[$modalityId]) || $classId <= 0 || $tag === '' || !in_array($kind, ['normal', 'bye', 'individual'], true) || $candidateIds === []) {
                throw new InvalidArgumentException('Cada nó deve apontar para uma modalidade, turma e equipes candidatas válidas.');
            }
            if (isset($nodesByTag[$modalityId . ':' . $classId . ':' . $tag])) {
                throw new InvalidArgumentException('A árvore do cronograma contém nós duplicados.');
            }
            $nodesByTag[$modalityId . ':' . $classId . ':' . $tag] = $node + ['equipe_ids' => $candidateIds];
            $coveredModalities[$modalityId] = true;
            foreach ($candidateIds as $teamId) {
                $coveredTeams[$teamId] = true;
            }
        }
        foreach ($items as $item) {
            $modalityId = (int) ($item['id_modalidade'] ?? 0);
            $teamId = (int) ($item['id_equipe'] ?? 0);
            if (!isset($modalityIds[$modalityId]) || $teamId <= 0) {
                throw new InvalidArgumentException('Cada compromisso deve apontar para uma modalidade e equipe ativas.');
            }
            $coveredModalities[$modalityId] = true;
            $coveredTeams[$teamId] = true;
            $tag = trim((string) ($item['chave_tag'] ?? ''));
            $matches = array_filter($nodesByTag, static fn (array $node): bool => (int) ($node['id_modalidade'] ?? 0) === $modalityId && (string) ($node['chave_tag'] ?? '') === $tag);
            if ($matches === []) {
                throw new InvalidArgumentException('Cada compromisso deve referenciar um nó publicado.');
            }
            $node = array_values($matches)[0];
            if (($node['tipo_no'] ?? '') === 'bye' || !in_array($teamId, $node['equipe_ids'], true)) {
                throw new InvalidArgumentException('O compromisso não pertence às equipes candidatas do nó.');
            }
        }
        foreach (array_keys($modalityIds) as $modalityId) {
            if (!isset($coveredModalities[$modalityId])) {
                throw new InvalidArgumentException('Todas as modalidades ativas precisam estar cobertas pelo cronograma.');
            }
        }
        $teams = $this->all('SELECT e.id_equipe, e.modalidades_id_modalidade FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe INNER JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' AND e.status_equipe = \'1\'', 'i', [$editionId]);
        foreach ($teams as $team) {
            if (!isset($coveredTeams[(int) $team['id_equipe']])) {
                throw new InvalidArgumentException('Todas as equipes preparadas precisam de ao menos um compromisso.');
            }
        }
        foreach ($items as $index => $first) {
            foreach (array_slice($items, $index + 1) as $second) {
                $firstNode = $this->nodeForCommitment($nodesByTag, $first);
                $secondNode = $this->nodeForCommitment($nodesByTag, $second);
                $sameTeam = array_intersect($firstNode['equipe_ids'] ?? [(int) ($first['id_equipe'] ?? 0)], $secondNode['equipe_ids'] ?? [(int) ($second['id_equipe'] ?? 0)]) !== [];
                $sameLocal = (int) ($first['id_local'] ?? 0) === (int) ($second['id_local'] ?? 0);
                if (!$sameTeam && !$sameLocal) {
                    continue;
                }
                $margin = $sameLocal ? 10 : 0;
                if (CronogramaRules::overlap(
                    (string) ($first['data_compromisso'] ?? $first['data'] ?? ''),
                    (string) ($first['inicio_compromisso'] ?? $first['inicio'] ?? ''),
                    (string) ($first['termino_compromisso'] ?? $first['fim'] ?? ''),
                    (string) ($second['data_compromisso'] ?? $second['data'] ?? ''),
                    (string) ($second['inicio_compromisso'] ?? $second['inicio'] ?? ''),
                    (string) ($second['termino_compromisso'] ?? $second['fim'] ?? ''),
                    $margin,
                )) {
                    throw new InvalidArgumentException('O cronograma possui conflito de equipe ou local entre compromissos.');
                }
            }
        }
    }

    /** @param array<string,array<string,mixed>> $nodesByTag @return array<string,mixed> */
    private function nodeForCommitment(array $nodesByTag, array $commitment): array
    {
        $modality = (int) ($commitment['id_modalidade'] ?? 0);
        $tag = (string) ($commitment['chave_tag'] ?? '');
        foreach ($nodesByTag as $node) {
            if ((int) ($node['id_modalidade'] ?? 0) === $modality && (string) ($node['chave_tag'] ?? '') === $tag) {
                return $node;
            }
        }
        return ['equipe_ids' => [(int) ($commitment['id_equipe'] ?? 0)]];
    }

    private function lockEdition(int $editionId): array
    {
        $edition = $this->one('SELECT id_interclasse, cronograma_status, inscricoes_status, cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ? LIMIT 1 FOR UPDATE', 'i', [$editionId]);
        if ($edition === null) {
            throw new InvalidArgumentException('Edição não encontrada.');
        }
        return $edition;
    }

    /** @return array<string,mixed> */
    private function lockEditionForRead(int $editionId): array
    {
        $edition = $this->one('SELECT id_interclasse, cronograma_status, inscricoes_status, cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ? LIMIT 1', 'i', [$editionId]);
        if ($edition === null) {
            throw new InvalidArgumentException('Edição sem planejamento migrado ou não encontrada.');
        }
        return $edition;
    }

    /** @return list<int> */
    private function planningLocalIds(int $editionId, mixed $requested): array
    {
        $ids = is_array($requested) ? array_values(array_unique(array_filter(array_map('intval', $requested), static fn (int $id): bool => $id > 0))) : [];
        if ($ids === []) {
            $rows = $this->all('SELECT id_local FROM locais WHERE interclasses_id_interclasse = ? AND status_local = \'1\' AND disponivel_local = \'1\' ORDER BY id_local', 'i', [$editionId]);
            return array_map(static fn (array $row): int => (int) $row['id_local'], $rows);
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->all('SELECT id_local FROM locais WHERE interclasses_id_interclasse = ? AND status_local = \'1\' AND disponivel_local = \'1\' AND id_local IN (' . $marks . ') ORDER BY id_local', 'i' . str_repeat('i', count($ids)), array_merge([$editionId], $ids));
        $valid = array_map(static fn (array $row): int => (int) $row['id_local'], $rows);
        if (count($valid) !== count($ids)) {
            throw new InvalidArgumentException('Um ou mais locais não pertencem à edição ou estão indisponíveis.');
        }
        return $valid;
    }

    /** @return array{0:\DateTimeImmutable,1:int,2:string,3:string,4:string,5:int}|null */
    private function nextDraftSlot(\DateTimeImmutable $cursor, \DateTimeImmutable $lastDate, int $duration, int $gap, array $localIds, int $localIndex, string $dayStart, string $dayEnd, array $occupied = []): ?array
    {
        for ($attempt = 0; $attempt < 10000; $attempt++) {
            $dayStartAt = new \DateTimeImmutable($cursor->format('Y-m-d') . ' ' . $dayStart);
            $dayEndAt = new \DateTimeImmutable($cursor->format('Y-m-d') . ' ' . $dayEnd);
            if ($cursor < $dayStartAt) {
                $cursor = $dayStartAt;
            }
            $end = $cursor->modify('+' . $duration . ' minutes');
            if ($end > $dayEndAt) {
                $nextDay = $cursor->modify('+1 day')->setTime((int) $dayStartAt->format('H'), (int) $dayStartAt->format('i'));
                if ($nextDay > $lastDate) {
                    return null;
                }
                $cursor = $nextDay;
                continue;
            }
            if ($end > $lastDate) {
                return null;
            }
            $date = $cursor->format('Y-m-d');
            $start = $cursor->format('H:i:s');
            $endTime = $end->format('H:i:s');
            $localCount = count($localIds);
            for ($offset = 0; $offset < $localCount; $offset++) {
                $candidateIndex = $localIndex + $offset;
                $local = $localIds[$candidateIndex % $localCount];
                $conflict = false;
                foreach ($occupied as $reserved) {
                    if ((int) ($reserved['local'] ?? 0) !== $local || (string) ($reserved['data'] ?? '') !== $date) {
                        continue;
                    }
                    if (CronogramaRules::overlap($date, $start, $endTime, $date, (string) ($reserved['inicio'] ?? ''), (string) ($reserved['termino'] ?? ''), 10)) {
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    $next = $end->modify('+' . $gap . ' minutes');
                    return [$next, $candidateIndex + 1, $date, $start, $endTime, $local];
                }
            }
            $cursor = $end->modify('+1 minute');
            $localIndex = 0;
        }
        return null;
    }

    /** @return list<array{data:string,inicio:string,termino:string,local:int}> */
    private function occupiedSlots(int $editionId, int $version): array
    {
        $rows = $this->all("SELECT DATE_FORMAT(data_reserva, '%Y-%m-%d') AS data, TIME_FORMAT(inicio_reserva, '%H:%i:%s') AS inicio, TIME_FORMAT(termino_reserva, '%H:%i:%s') AS termino, id_local AS local FROM agenda_reservas WHERE id_interclasse = ? AND data_reserva IS NOT NULL AND inicio_reserva IS NOT NULL AND termino_reserva IS NOT NULL AND id_local IS NOT NULL UNION ALL SELECT DATE_FORMAT(data_compromisso, '%Y-%m-%d') AS data, TIME_FORMAT(inicio_compromisso, '%H:%i:%s') AS inicio, TIME_FORMAT(termino_compromisso, '%H:%i:%s') AS termino, id_local AS local FROM cronograma_compromissos WHERE id_interclasse = ? AND cronograma_versao = ?", 'iii', [$editionId, $editionId, $version]);
        return array_map(static fn (array $row): array => ['data' => (string) $row['data'], 'inicio' => (string) $row['inicio'], 'termino' => (string) $row['termino'], 'local' => (int) $row['local']], $rows);
    }

    private function positiveOption(mixed $value, string $label): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number <= 0) {
            throw new InvalidArgumentException($label . ' deve ser um inteiro positivo.');
        }
        return (int) $number;
    }

    private function nonNegativeOption(mixed $value, string $label): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < 0) {
            throw new InvalidArgumentException($label . ' deve ser um inteiro não negativo.');
        }
        return (int) $number;
    }

    private function assertRevision(array $edition, int $expected): void
    {
        if ((int) $edition['cronograma_versao'] !== $expected) {
            throw new InvalidArgumentException('O cronograma foi alterado. Atualize a página e tente novamente.');
        }
    }

    /** @return list<array<string,mixed>> */
    private function all(string $sql, string $types, array $params): array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param($types, ...$params);
        if (!$statement->execute()) {
            $error = $statement->error;
            $statement->close();
            throw new RuntimeException($error !== '' ? $error : 'Não foi possível consultar o cronograma.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    /** @return array<string,mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $rows = $this->all($sql, $types, $params);
        return $rows[0] ?? null;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar operação do cronograma.');
        }
        return $statement;
    }
}
