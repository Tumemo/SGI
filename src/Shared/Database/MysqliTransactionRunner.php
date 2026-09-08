<?php

declare(strict_types=1);

namespace App\Shared\Database;

use App\Shared\Application\TransactionRunner;
use mysqli;
use Throwable;

final class MysqliTransactionRunner implements TransactionRunner
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    /** @param callable():mixed $callback */
    public function run(callable $callback): mixed
    {
        Transaction::begin($this->connection);
        try {
            $result = $callback();
            Transaction::commit($this->connection);
            return $result;
        } catch (Throwable $exception) {
            Transaction::rollback($this->connection);
            throw $exception;
        }
    }
}
