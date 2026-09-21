<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\CronogramaRepository;
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
        $edition = $this->one('SELECT i.id_interclasse, p.modo_planejamento, p.cronograma_status, p.inscricoes_status, p.cronograma_versao, p.inscricoes_abertura, p.inscricoes_encerramento FROM interclasses i INNER JOIN interclasse_planejamentos p ON p.id_interclasse = i.id_interclasse WHERE i.id_interclasse = ? LIMIT 1', 'i', [$editionId]);
        if ($edition === null) {
            throw new InvalidArgumentException('Edição não encontrada.');
        }
        $modalities = $this->all('SELECT m.id_modalidade, m.nome_modalidade, m.categorias_id_categoria, mp.equipes_planejadas, mp.min_inscritos_equipe, mp.max_inscritos_equipe, mp.formato_participacao, mp.duracao_prevista_min, mp.descanso_min, (SELECT COUNT(*) FROM equipes e WHERE e.modalidades_id_modalidade = m.id_modalidade AND e.status_equipe = \'1\') AS equipes_criadas FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' ORDER BY m.id_modalidade', 'i', [$editionId]);
        $commitments = $this->all('SELECT id_compromisso, id_modalidade, id_equipe, chave_tag, DATE_FORMAT(data_compromisso, \'%Y-%m-%d\') AS data_compromisso, TIME_FORMAT(inicio_compromisso, \'%H:%i:%s\') AS inicio_compromisso, TIME_FORMAT(termino_compromisso, \'%H:%i:%s\') AS termino_compromisso, id_local, condicional, cronograma_versao FROM cronograma_compromissos WHERE id_interclasse = ? AND cronograma_versao = ? ORDER BY data_compromisso, inicio_compromisso, id_local, id_compromisso', 'ii', [$editionId, (int) $edition['cronograma_versao']]);
        $edition['modalidades'] = $modalities;
        $edition['compromissos'] = $commitments;
        return $edition;
    }

    public function enablePlanning(int $editionId, int $userId): array
    {
        Transaction::begin($this->connection);
        try {
            $base = $this->one('SELECT id_interclasse FROM interclasses WHERE id_interclasse = ? LIMIT 1 FOR UPDATE', 'i', [$editionId]);
            if ($base === null) {
                throw new InvalidArgumentException('Edição não encontrada.');
            }
            $seed = $this->prepare('INSERT INTO interclasse_planejamentos (id_interclasse) VALUES (?) ON DUPLICATE KEY UPDATE id_interclasse = VALUES(id_interclasse)');
            $seed->bind_param('i', $editionId);
            if (!$seed->execute()) {
                $seed->close();
                throw new RuntimeException('Não foi possível preparar o modo planejado.');
            }
            $seed->close();
            $edition = $this->lockEdition($editionId);
            if ((string) $edition['modo_planejamento'] === CronogramaRules::PLANEJADO) {
                Transaction::commit($this->connection);
                return ['success' => true, 'modo_planejamento' => CronogramaRules::PLANEJADO, 'cronograma_status' => (string) $edition['cronograma_status'], 'inscricoes_status' => (string) $edition['inscricoes_status'], 'cronograma_versao' => (int) $edition['cronograma_versao']];
            }
            if ((string) $edition['inscricoes_status'] !== 'fechadas' || (string) $edition['cronograma_status'] !== CronogramaRules::RASCUNHO) {
                throw new InvalidArgumentException('O modo planejado só pode ser ativado antes da publicação e das inscrições.');
            }
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET modo_planejamento = 'planejado' WHERE id_interclasse = ?");
            $statement->bind_param('i', $editionId);
            if (!$statement->execute()) {
                $statement->close();
                throw new RuntimeException('Não foi possível ativar o modo planejado.');
            }
            $statement->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'modo_planejamento' => CronogramaRules::PLANEJADO, 'cronograma_status' => CronogramaRules::RASCUNHO, 'inscricoes_status' => 'fechadas', 'cronograma_versao' => (int) $edition['cronograma_versao']];
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function prepareTeams(int $editionId, int $userId): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            if ((string) $edition['modo_planejamento'] !== CronogramaRules::PLANEJADO || !in_array((string) $edition['cronograma_status'], [CronogramaRules::RASCUNHO, CronogramaRules::REVISAO], true) || (string) $edition['inscricoes_status'] !== 'fechadas') {
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
        if ((string) $edition['modo_planejamento'] !== CronogramaRules::PLANEJADO) {
            throw new InvalidArgumentException('Ative o modo de cronograma planejado antes de gerar a agenda.');
        }
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
        $modalities = $this->all('SELECT m.id_modalidade, m.nome_modalidade, tm.nome_tipo_modalidade, mp.equipes_planejadas, mp.duracao_prevista_min, mp.descanso_min FROM modalidades m LEFT JOIN modalidade_planejamentos mp ON mp.id_modalidade = m.id_modalidade INNER JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade WHERE m.interclasses_id_interclasse = ? AND m.status_modalidade = \'1\' ORDER BY m.id_modalidade', 'i', [$editionId]);
        if ($modalities === []) {
            throw new InvalidArgumentException('Cadastre e configure ao menos uma modalidade ativa antes de gerar a agenda.');
        }
        $commitments = [];
        $pendencias = [];
        $localIndex = 0;
        foreach ($modalities as $modality) {
            $modalityId = (int) $modality['id_modalidade'];
            if ($modality['equipes_planejadas'] === null || (int) $modality['equipes_planejadas'] <= 0) {
                throw new InvalidArgumentException('Todas as modalidades ativas precisam de quantidade planejada antes de gerar a agenda.');
            }
            $teams = $this->all('SELECT e.id_equipe FROM equipes e INNER JOIN equipe_planejamentos ep ON ep.id_equipe = e.id_equipe WHERE e.modalidades_id_modalidade = ? AND e.status_equipe = \'1\' ORDER BY ep.ordem_planejada, e.id_equipe', 'i', [$modalityId]);
            if ($teams === []) {
                $pendencias[] = ['tipo' => 'equipes', 'id_modalidade' => $modalityId, 'mensagem' => 'A modalidade ainda não possui equipes planejadas.'];
                continue;
            }
            $rounds = strtolower((string) ($modality['nome_tipo_modalidade'] ?? '')) === 'individual' ? 1 : max(1, (int) ceil(log(max(1, count($teams)), 2)));
            $duration = (int) ($modality['duracao_prevista_min'] ?? 0) > 0 ? (int) $modality['duracao_prevista_min'] : $durationDefault;
            $gap = max($gapDefault, (int) ($modality['descanso_min'] ?? 0));
            foreach ($teams as $team) {
                $teamId = (int) $team['id_equipe'];
                for ($round = 1; $round <= $rounds; $round++) {
                    $slot = $this->nextDraftSlot($cursor, $lastDate, $duration, $gap, $localIds, $localIndex, $startTime, $endTime);
                    if ($slot === null) {
                        $pendencias[] = ['tipo' => 'janela', 'id_modalidade' => $modalityId, 'id_equipe' => $teamId, 'fase' => $round, 'mensagem' => 'A janela e os locais não comportam todos os compromissos.'];
                        continue 2;
                    }
                    [$cursor, $localIndex, $date, $start, $end, $local] = $slot;
                    $commitments[] = [
                        'id_modalidade' => $modalityId,
                        'id_equipe' => $teamId,
                        'chave_tag' => sprintf('PL:%d:%d:%d', $modalityId, $round, $teamId),
                        'data_compromisso' => $date,
                        'inicio_compromisso' => $start,
                        'termino_compromisso' => $end,
                        'id_local' => $local,
                        'condicional' => $round > 1 ? 1 : 0,
                    ];
                }
            }
        }
        return ['success' => $pendencias === [], 'cronograma_versao' => (int) $edition['cronograma_versao'], 'compromissos' => $commitments, 'pendencias' => $pendencias];
    }

    public function publish(int $editionId, int $userId, int $expectedRevision, array $commitments): array
    {
        Transaction::begin($this->connection);
        try {
            $edition = $this->lockEdition($editionId);
            $this->assertRevision($edition, $expectedRevision);
            if ((string) $edition['modo_planejamento'] !== CronogramaRules::PLANEJADO) {
                throw new InvalidArgumentException('Ative o modo de cronograma planejado antes da publicação.');
            }
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
            $this->validateCommitments($editionId, $modalities, $commitments);
            $version = (int) $edition['cronograma_versao'] + 1;
            $this->insertCommitments($editionId, $version, $commitments);
            $statement = $this->prepare("UPDATE interclasse_planejamentos SET cronograma_status = 'publicado', inscricoes_status = 'fechadas', cronograma_versao = ? WHERE id_interclasse = ? AND cronograma_versao = ?");
            $statement->bind_param('iii', $version, $editionId, $expectedRevision);
            if (!$statement->execute() || $statement->affected_rows !== 1) {
                $statement->close();
                throw new RuntimeException('O cronograma foi alterado durante a publicação.');
            }
            $statement->close();
            Transaction::commit($this->connection);
            return ['success' => true, 'cronograma_status' => CronogramaRules::PUBLICADO, 'inscricoes_status' => 'fechadas', 'cronograma_versao' => $version, 'compromissos' => count($commitments)];
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
            if ((string) $edition['modo_planejamento'] !== CronogramaRules::PLANEJADO || (string) $edition['cronograma_status'] !== CronogramaRules::PUBLICADO) {
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
        return $this->all("SELECT id_compromisso, id_modalidade, id_equipe, chave_tag, DATE_FORMAT(data_compromisso, '%Y-%m-%d') AS data_compromisso, TIME_FORMAT(inicio_compromisso, '%H:%i:%s') AS inicio_compromisso, TIME_FORMAT(termino_compromisso, '%H:%i:%s') AS termino_compromisso, id_local, condicional FROM cronograma_compromissos WHERE id_interclasse = ? AND cronograma_versao = ? AND id_equipe IN ($marks)", $types, $params);
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

    /** @param list<array<string,mixed>> $modalities @param list<array<string,mixed>> $items */
    private function validateCommitments(int $editionId, array $modalities, array $items): void
    {
        if ($items === []) {
            throw new InvalidArgumentException('O cronograma precisa ter compromissos antes da publicação.');
        }
        $modalityIds = array_fill_keys(array_map(static fn (array $row): int => (int) $row['id_modalidade'], $modalities), true);
        $coveredModalities = [];
        $coveredTeams = [];
        foreach ($items as $item) {
            $modalityId = (int) ($item['id_modalidade'] ?? 0);
            $teamId = (int) ($item['id_equipe'] ?? 0);
            if (!isset($modalityIds[$modalityId]) || $teamId <= 0) {
                throw new InvalidArgumentException('Cada compromisso deve apontar para uma modalidade e equipe ativas.');
            }
            $coveredModalities[$modalityId] = true;
            $coveredTeams[$teamId] = true;
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
                $sameTeam = (int) ($first['id_equipe'] ?? 0) === (int) ($second['id_equipe'] ?? 0);
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

    private function lockEdition(int $editionId): array
    {
        $edition = $this->one('SELECT id_interclasse, modo_planejamento, cronograma_status, inscricoes_status, cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ? LIMIT 1 FOR UPDATE', 'i', [$editionId]);
        if ($edition === null) {
            throw new InvalidArgumentException('Edição não encontrada.');
        }
        return $edition;
    }

    /** @return array<string,mixed> */
    private function lockEditionForRead(int $editionId): array
    {
        $edition = $this->one('SELECT id_interclasse, modo_planejamento, cronograma_status, inscricoes_status, cronograma_versao FROM interclasse_planejamentos WHERE id_interclasse = ? LIMIT 1', 'i', [$editionId]);
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
    private function nextDraftSlot(\DateTimeImmutable $cursor, \DateTimeImmutable $lastDate, int $duration, int $gap, array $localIds, int $localIndex, string $dayStart, string $dayEnd): ?array
    {
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
            $dayEndAt = new \DateTimeImmutable($cursor->format('Y-m-d') . ' ' . $dayEnd);
            $end = $cursor->modify('+' . $duration . ' minutes');
        }
        if ($end > $lastDate) {
            return null;
        }
        $local = $localIds[$localIndex % count($localIds)];
        $next = $end->modify('+' . $gap . ' minutes');
        return [$next, $localIndex + 1, $cursor->format('Y-m-d'), $cursor->format('H:i:s'), $end->format('H:i:s'), $local];
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
