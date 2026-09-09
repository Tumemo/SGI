<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Infrastructure;

use App\Modules\Sincronizacao\Domain\MutationConflict;
use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Shared\Database\Transaction;
use mysqli;
use RuntimeException;

final class MysqliMutationStore
{
    private ?string $lock = null;
    private bool $active = false;

    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @return array{status:int,payload:array<string,mixed>}|null */
    public function begin(string $route, MutationIdentity $identity): ?array
    {
        if ($identity->key !== null) {
            $database = (string) $this->connection->query('SELECT DATABASE()')->fetch_row()[0];
            $name = hash('sha256', $database . ':' . $route . ':' . $identity->key);
            $stmt = $this->connection->prepare('SELECT GET_LOCK(?, 10)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $acquired = (int) $stmt->get_result()->fetch_row()[0] === 1;
            $stmt->close();
            if (!$acquired) {
                throw new RuntimeException('Sincronização em andamento. Tente novamente.');
            }
            $this->lock = $name;
            $stmt = $this->connection->prepare('SELECT status_http, resposta_json, request_hash FROM sincronizacoes_idempotentes WHERE rota = ? AND chave_mutacao = ?');
            $key = $identity->key;
            $stmt->bind_param('ss', $route, $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row !== null) {
                $this->release();
                if ($row['request_hash'] === null || !hash_equals($row['request_hash'], $identity->fingerprint)) {
                    throw new MutationConflict('Identificador já utilizado para outra operação.');
                }
                return ['status' => (int) $row['status_http'], 'payload' => json_decode($row['resposta_json'], true, 512, JSON_THROW_ON_ERROR)];
            }
        }
        Transaction::begin($this->connection);
        $this->active = true;
        return null;
    }

    /** @param array<string,mixed> $payload */
    public function complete(string $route, MutationIdentity $identity, int $status, array $payload): void
    {
        if (!$this->active) {
            throw new RuntimeException('Sincronização não iniciada.');
        }
        if ($identity->key !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $stmt = $this->connection->prepare('INSERT INTO sincronizacoes_idempotentes (rota, chave_mutacao, status_http, resposta_json, request_hash) VALUES (?, ?, ?, ?, ?)');
            $key = $identity->key;
            $fingerprint = $identity->fingerprint;
            $stmt->bind_param('ssiss', $route, $key, $status, $json, $fingerprint);
            $stmt->execute();
            $stmt->close();
        }
        Transaction::commit($this->connection);
        $this->active = false;
        $this->release();
    }

    public function cancel(): void
    {
        if ($this->active) {
            Transaction::rollback($this->connection);
            $this->active = false;
        }
        $this->release();
    }

    private function release(): void
    {
        if ($this->lock === null) {
            return;
        }
        $stmt = $this->connection->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->bind_param('s', $this->lock);
        $stmt->execute();
        $stmt->close();
        $this->lock = null;
    }
}
