<?php

declare(strict_types=1);

namespace App\Shared\Database;

use App\Shared\Config\Env;
use mysqli;
use RuntimeException;

final class ConnectionFactory
{
    private static ?mysqli $connection = null;

    public static function get(): mysqli
    {
        if (self::$connection !== null) {
            return self::$connection;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $connection = new mysqli(
                Env::get('SGI_DB_HOST', 'localhost'),
                Env::get('SGI_DB_USER', 'root'),
                Env::get('SGI_DB_PASSWORD', ''),
                Env::get('SGI_DB_NAME', 'sgi'),
                (int) Env::get('SGI_DB_PORT', '3306'),
            );
            $connection->set_charset('utf8mb4');
            self::$connection = $connection;
            return $connection;
        } catch (\mysqli_sql_exception $exception) {
            error_log('Conexão indisponível: ' . $exception->getMessage());
            throw new RuntimeException('Serviço temporariamente indisponível.', 503, $exception);
        }
    }
}
