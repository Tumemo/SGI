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
        if (in_array('--baseline', $argv, true)) {
            throw new RuntimeException('A opção --baseline não faz parte da instalação atual.');
        }
        foreach ($runner->migrate() as $version) {
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
    } elseif ($command === 'pontuacao:diagnosticar') {
        $diagnostico = (new \App\Modules\Resultados\Infrastructure\MysqliPodioRepository(\App\Shared\Database\ConnectionFactory::get()))->diagnosticar();
        echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    } else {
        echo "Uso: php bin/sgi.php migrate\n";
        echo "     php bin/sgi.php admin:create (SGI_ADMIN_LOGIN, SGI_ADMIN_NAME, SGI_ADMIN_PASSWORD)\n";
        echo "     php bin/sgi.php pontuacao:diagnosticar\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
