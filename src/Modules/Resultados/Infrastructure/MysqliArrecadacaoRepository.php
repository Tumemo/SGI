<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use App\Modules\Resultados\Domain\ArrecadacaoQuantidadeInsuficienteException;
use App\Modules\Resultados\Domain\ArrecadacaoRepository;
use App\Modules\Resultados\Domain\PontuacaoRules;
use App\Shared\Database\Transaction;
use mysqli;
use RuntimeException;

final class MysqliArrecadacaoRepository implements ArrecadacaoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function listByInterclasse(int $interclasseId): array
    {
        $statement = $this->connection->prepare(
            'SELECT h.id_historico, h.id_turma, h.quantidade, h.pontos_adicionados,
                    h.data_registro, h.status_historico, t.nome_turma, c.nome_categoria,
                    u.nome_usuario AS registrado_por_nome
             FROM historico_arrecadacoes h
             INNER JOIN turmas t ON t.id_turma = h.id_turma
             LEFT JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
             LEFT JOIN usuarios u ON u.id_usuario = h.registrado_por
             WHERE h.id_interclasse = ?
             ORDER BY h.data_registro DESC, h.id_historico DESC',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar a arrecadação.');
        }
        $statement->bind_param('i', $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar a arrecadação.');
        }
        $rows = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $rows[] = [
                'id_historico' => (int) $row['id_historico'],
                'id_turma' => (int) $row['id_turma'],
                'quantidade' => (float) $row['quantidade'],
                'pontos_adicionados' => (int) $row['pontos_adicionados'],
                'data_registro' => $row['data_registro'],
                'status_historico' => $row['status_historico'],
                'nome_turma' => $row['nome_turma'],
                'nome_categoria' => $row['nome_categoria'] ?? 'Geral',
                'registrado_por_nome' => $row['registrado_por_nome'] ?? 'Sistema',
            ];
        }
        $statement->close();
        return $rows;
    }

    public function addBatch(int $interclasseId, int $userId, array $items): void
    {
        Transaction::begin($this->connection);
        try {
            $value = $this->lockEdition($interclasseId);
            $turmas = $this->lockTurmas($interclasseId, $items);
            $update = $this->connection->prepare(
                'UPDATE turmas
                 SET qtd_itens_arrecadados = ?, pontuacao_turma = ?
                 WHERE id_turma = ? AND interclasses_id_interclasse = ?',
            );
            $history = $this->connection->prepare(
                'INSERT INTO historico_arrecadacoes
                    (id_turma, id_interclasse, quantidade, pontos_adicionados, registrado_por)
                 VALUES (?, ?, ?, ?, ?)',
            );
            if ($update === false || $history === false) {
                if ($update !== false) {
                    $update->close();
                }
                if ($history !== false) {
                    $history->close();
                }
                throw new RuntimeException('Não foi possível registrar a arrecadação.');
            }

            foreach ($items as $item) {
                $turmaId = (int) $item['id_turma'];
                $quantity = self::quantityToken($item['quantidade']);
                $before = $turmas[$turmaId];
                $afterQuantity = PontuacaoRules::somarQuantidade($before['quantidade'], $quantity);
                $delta = PontuacaoRules::doacao($afterQuantity, $value) - PontuacaoRules::doacao($before['quantidade'], $value);
                $afterPoints = $before['pontos'] + $delta;
                $update->bind_param('siii', $afterQuantity, $afterPoints, $turmaId, $interclasseId);
                if (!$update->execute()) {
                    throw new RuntimeException('Não foi possível atualizar a turma da arrecadação.');
                }
                $history->bind_param('iisii', $turmaId, $interclasseId, $quantity, $delta, $userId);
                if (!$history->execute()) {
                    throw new RuntimeException('Não foi possível registrar o histórico da arrecadação.');
                }
                $turmas[$turmaId] = ['quantidade' => $afterQuantity, 'pontos' => $afterPoints];
            }
            $update->close();
            $history->close();
            Transaction::commit($this->connection);
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    public function remove(int $historicoId, int $interclasseId): string
    {
        $turmaId = $this->findHistoryClass($historicoId, $interclasseId);
        if ($turmaId === null) {
            return 'not_found';
        }

        Transaction::begin($this->connection);
        try {
            $value = $this->lockEdition($interclasseId);
            $turma = $this->lockTurma($interclasseId, $turmaId);
            $find = $this->connection->prepare(
                'SELECT id_turma, quantidade, status_historico
                 FROM historico_arrecadacoes
                 WHERE id_historico = ? AND id_interclasse = ?
                 FOR UPDATE',
            );
            if ($find === false) {
                throw new RuntimeException('Não foi possível consultar o histórico.');
            }
            $find->bind_param('ii', $historicoId, $interclasseId);
            if (!$find->execute()) {
                $find->close();
                throw new RuntimeException('Não foi possível consultar o histórico.');
            }
            $row = $find->get_result()->fetch_assoc();
            $find->close();
            if ($row === null) {
                Transaction::rollback($this->connection);
                return 'not_found';
            }
            if ((string) $row['status_historico'] === '0') {
                Transaction::rollback($this->connection);
                return 'already_removed';
            }
            if ((int) $row['id_turma'] !== $turmaId) {
                throw new RuntimeException('O histórico não pertence à turma travada.');
            }

            $quantity = self::quantityToken((string) $row['quantidade']);
            try {
                $afterQuantity = PontuacaoRules::subtrairQuantidade($turma['quantidade'], $quantity);
            } catch (\InvalidArgumentException $exception) {
                throw new ArrecadacaoQuantidadeInsuficienteException($exception->getMessage(), 0, $exception);
            }
            $delta = PontuacaoRules::doacao($afterQuantity, $value) - PontuacaoRules::doacao($turma['quantidade'], $value);
            $afterPoints = $turma['pontos'] + $delta;

            $mark = $this->connection->prepare(
                "UPDATE historico_arrecadacoes
                 SET status_historico = '0'
                 WHERE id_historico = ? AND id_interclasse = ? AND status_historico = '1'",
            );
            $update = $this->connection->prepare(
                'UPDATE turmas
                 SET qtd_itens_arrecadados = ?, pontuacao_turma = ?
                 WHERE id_turma = ? AND interclasses_id_interclasse = ?',
            );
            if ($mark === false || $update === false) {
                if ($mark !== false) {
                    $mark->close();
                }
                if ($update !== false) {
                    $update->close();
                }
                throw new RuntimeException('Não foi possível reverter a arrecadação.');
            }
            $mark->bind_param('ii', $historicoId, $interclasseId);
            if (!$mark->execute() || $mark->affected_rows !== 1) {
                $mark->close();
                $update->close();
                throw new RuntimeException('Não foi possível confirmar o estorno do histórico.');
            }
            $update->bind_param('siii', $afterQuantity, $afterPoints, $turmaId, $interclasseId);
            if (!$update->execute()) {
                $mark->close();
                $update->close();
                throw new RuntimeException('Não foi possível atualizar a turma da arrecadação.');
            }
            $mark->close();
            $update->close();
            Transaction::commit($this->connection);
            return 'removed';
        } catch (\Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }

    private function lockEdition(int $interclasseId): int
    {
        $statement = $this->connection->prepare('SELECT valor_item_arrecadacao FROM interclasses WHERE id_interclasse = ? FOR UPDATE');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível travar a edição da arrecadação.');
        }
        $statement->bind_param('i', $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar a edição da arrecadação.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($row === null) {
            throw new RuntimeException('Edição não encontrada para a arrecadação.');
        }
        return (int) $row['valor_item_arrecadacao'];
    }

    /** @param list<array{id_turma: int, quantidade: float}> $items */
    private function lockTurmas(int $interclasseId, array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[(int) $item['id_turma']] = true;
        }
        $ids = array_keys($ids);
        sort($ids, SORT_NUMERIC);
        $turmas = [];
        foreach ($ids as $turmaId) {
            $turmas[$turmaId] = $this->lockTurma($interclasseId, (int) $turmaId);
        }
        return $turmas;
    }

    /** @return array{quantidade: string, pontos: int} */
    private function lockTurma(int $interclasseId, int $turmaId): array
    {
        $statement = $this->connection->prepare(
            'SELECT qtd_itens_arrecadados, pontuacao_turma
             FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ? FOR UPDATE',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível travar a turma da arrecadação.');
        }
        $statement->bind_param('ii', $turmaId, $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar a turma da arrecadação.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($row === null) {
            throw new RuntimeException('Turma não pertence à edição informada.');
        }
        return [
            'quantidade' => self::quantityToken((string) $row['qtd_itens_arrecadados']),
            'pontos' => (int) $row['pontuacao_turma'],
        ];
    }

    private function findHistoryClass(int $historicoId, int $interclasseId): ?int
    {
        $statement = $this->connection->prepare('SELECT id_turma FROM historico_arrecadacoes WHERE id_historico = ? AND id_interclasse = ?');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar o histórico.');
        }
        $statement->bind_param('ii', $historicoId, $interclasseId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar o histórico.');
        }
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        return $value === null ? null : (int) $value;
    }

    private static function quantityToken(float|string|int $quantity): string
    {
        return number_format((float) $quantity, 2, '.', '');
    }
}
