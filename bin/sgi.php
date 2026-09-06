<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $command = $argv[1] ?? 'help';
    if ($command === 'migrate') {
        $runner = new \App\Shared\Database\MigrationRunner(\App\Shared\Database\ConnectionFactory::get(), SGI_ROOT . '/database/migrations');
        foreach ($runner->migrate(in_array('--baseline', $argv, true)) as $version) {
            echo 'Aplicada: ' . $version . PHP_EOL;
        }
        echo "Banco atualizado.\n";
    } elseif ($command === 'admin:create') {
        $id = (new \App\Modules\Acesso\Infrastructure\InitialAdminProvisioner(\App\Shared\Database\ConnectionFactory::get()))->create(
            (string) getenv('SGI_ADMIN_LOGIN'),
            (string) getenv('SGI_ADMIN_NAME'),
            (string) getenv('SGI_ADMIN_PASSWORD'),
        );
        echo 'Administrador inicial criado: ' . $id . PHP_EOL;
    } else {
        echo "Uso: php bin/sgi.php migrate [--baseline]\n";
        echo "     php bin/sgi.php admin:create (SGI_ADMIN_LOGIN, SGI_ADMIN_NAME, SGI_ADMIN_PASSWORD)\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
