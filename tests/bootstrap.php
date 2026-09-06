<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Unit tests must not use the web server's session directory or cookies.
$unitSessionDirectory = dirname(__DIR__) . '/test-results/unit-sessions/' . getmypid();
if (!is_dir($unitSessionDirectory) && !mkdir($unitSessionDirectory, 0770, true) && !is_dir($unitSessionDirectory)) {
    throw new RuntimeException('Não foi possível preparar sessões isoladas dos testes.');
}
session_save_path($unitSessionDirectory);
