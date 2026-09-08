<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TransactionRunner
{
    /** @param callable():mixed $callback */
    public function run(callable $callback): mixed;
}
