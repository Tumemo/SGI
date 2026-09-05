<?php

declare(strict_types=1);

namespace App\Interclasse\Infrastructure;

use App\Interclasse\Domain\ArrecadacaoRepository;
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
        $this->connection->begin_transaction();
        try {
            $update = $this->connection->prepare(
                'UPDATE turmas t
                 INNER JOIN interclasses i ON t.interclasses_id_interclasse = i.id_interclasse
                 SET t.qtd_itens_arrecadados = t.qtd_itens_arrecadados + ?,
                     t.pontuacao_turma = t.pontuacao_turma + ROUND(? * i.valor_item_arrecadacao, 2)
                 WHERE t.id_turma = ? AND i.id_interclasse = ?',
            );
            $history = $this->connection->prepare(
                'INSERT INTO historico_arrecadacoes
                    (id_turma, id_interclasse, quantidade, pontos_adicionados, registrado_por)
                 VALUES (?, ?, ?, ROUND(? * (SELECT valor_item_arrecadacao FROM interclasses WHERE id_interclasse = ?)), ?)',
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
                $quantity = $item['quantidade'];
                $turmaId = $item['id_turma'];
                $update->bind_param('ddii', $quantity, $quantity, $turmaId, $interclasseId);
                if (!$update->execute() || $update->affected_rows === 0) {
                    throw new RuntimeException('Turma não pertence à edição informada.');
                }
                $history->bind_param('iiddii', $turmaId, $interclasseId, $quantity, $quantity, $interclasseId, $userId);
                if (!$history->execute()) {
                    throw new RuntimeException('Não foi possível registrar o histórico da arrecadação.');
                }
            }
            $update->close();
            $history->close();
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function remove(int $historicoId, int $interclasseId): string
    {
        $this->connection->begin_transaction();
        try {
            $find = $this->connection->prepare(
                'SELECT id_turma, quantidade, pontos_adicionados, status_historico
                 FROM historico_arrecadacoes WHERE id_historico = ? AND id_interclasse = ?',
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
                $this->connection->rollback();
                return 'not_found';
            }
            if ((string) $row['status_historico'] === '0') {
                $this->connection->rollback();
                return 'already_removed';
            }

            $turmaId = (int) $row['id_turma'];
            $quantity = (float) $row['quantidade'];
            $points = (int) $row['pontos_adicionados'];
            $revert = $this->connection->prepare(
                'UPDATE turmas t
                 INNER JOIN interclasses i ON t.interclasses_id_interclasse = i.id_interclasse
                 SET t.qtd_itens_arrecadados = t.qtd_itens_arrecadados - ?,
                     t.pontuacao_turma = t.pontuacao_turma - ?
                 WHERE t.id_turma = ? AND i.id_interclasse = ?',
            );
            $mark = $this->connection->prepare(
                "UPDATE historico_arrecadacoes SET status_historico = '0' WHERE id_historico = ?",
            );
            if ($revert === false || $mark === false) {
                if ($revert !== false) {
                    $revert->close();
                }
                if ($mark !== false) {
                    $mark->close();
                }
                throw new RuntimeException('Não foi possível reverter a arrecadação.');
            }
            $revert->bind_param('ddii', $quantity, $points, $turmaId, $interclasseId);
            if (!$revert->execute() || $revert->affected_rows === 0) {
                throw new RuntimeException('Turma não pertence à edição informada.');
            }
            $mark->bind_param('i', $historicoId);
            if (!$mark->execute()) {
                throw new RuntimeException('Não foi possível atualizar o histórico.');
            }
            $revert->close();
            $mark->close();
            $this->connection->commit();
            return 'removed';
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }
}
