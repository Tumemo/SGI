<?php

declare(strict_types=1);

namespace App\Shared\Database;

use LogicException;
use mysqli;
use WeakMap;

/** Nested units use savepoints; only the outer owner can commit the request. */
final class Transaction
{
    /** @var WeakMap<mysqli, int>|null */
    private static ?WeakMap $depths = null;

    public static function begin(mysqli $connection): void
    {
        self::$depths ??= new WeakMap();
        $depth = self::$depths[$connection] ?? 0;
        if ($depth === 0) {
            $connection->begin_transaction();
        } else {
            $connection->query('SAVEPOINT sgi_' . $depth);
        }
        self::$depths[$connection] = $depth + 1;
    }

    public static function commit(mysqli $connection): void
    {
        $depth = self::$depths[$connection] ?? 0;
        if ($depth === 0) {
            throw new LogicException('Nenhuma transação ativa.');
        }
        if ($depth === 1) {
            $connection->commit();
        } else {
            $connection->query('RELEASE SAVEPOINT sgi_' . ($depth - 1));
        }
        self::$depths[$connection] = $depth - 1;
    }

    public static function rollback(mysqli $connection): void
    {
        $depth = self::$depths[$connection] ?? 0;
        if ($depth === 0) {
            return;
        }
        if ($depth === 1) {
            $connection->rollback();
        } else {
            $connection->query('ROLLBACK TO SAVEPOINT sgi_' . ($depth - 1));
            $connection->query('RELEASE SAVEPOINT sgi_' . ($depth - 1));
        }
        self::$depths[$connection] = $depth - 1;
    }
}
