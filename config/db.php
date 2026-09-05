<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Shared\Config\Env;

mysqli_report(MYSQLI_REPORT_OFF);

$host = Env::get('SGI_DB_HOST', 'localhost');
$port = (int) Env::get('SGI_DB_PORT', '3306');
$user = Env::get('SGI_DB_USER', 'root');
$pass = Env::get('SGI_DB_PASSWORD', '');
$db = Env::get('SGI_DB_NAME', 'sgi');

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_errno !== 0) {
    error_log(sprintf('Falha de conexão com o banco (%d): %s', $conn->connect_errno, $conn->connect_error));
    http_response_code(503);
    throw new RuntimeException('Serviço temporariamente indisponível.');
}

if (!$conn->set_charset('utf8mb4')) {
    error_log('Não foi possível configurar o charset utf8mb4: ' . $conn->error);
    throw new RuntimeException('Serviço temporariamente indisponível.');
}

return $conn;
