<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$conn = \App\Shared\Database\ConnectionFactory::get();
return $conn;
