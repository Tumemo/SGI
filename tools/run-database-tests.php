<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$powershellScript = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'test-docker.ps1';
$shellScript = $root . '/tools/test-docker.sh';

if (PHP_OS_FAMILY === 'Windows') {
    $command = implode(' ', [
        'powershell.exe',
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-File',
        escapeshellarg($powershellScript),
        '-Database',
        'mariadb',
        '-SkipQuality',
        '-SkipBrowser',
    ]);
} else {
    $command = implode(' ', [
        'sh',
        escapeshellarg($shellScript),
        '--database',
        'mariadb',
        '--skip-quality',
        '--skip-browser',
    ]);
}

passthru($command, $exitCode);
exit($exitCode);
