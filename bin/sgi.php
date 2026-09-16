<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/autoload.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $command = $argv[1] ?? 'help';
    if ($command === 'schema:install') {
        if (in_array('--baseline', $argv, true)) {
            throw new RuntimeException('A opção --baseline não faz parte da instalação pelo schema atual.');
        }
        $installed = (new \App\Shared\Database\SchemaInstaller(
            \App\Shared\Database\ConnectionFactory::get(),
            SGI_ROOT . '/database/schema-inicial.sql',
        ))->install();
        echo $installed ? "Schema inicial instalado.\n" : "Schema inicial já estava instalado.\n";
    } elseif ($command === 'migrate') {
        if (in_array('--baseline', $argv, true)) {
            throw new RuntimeException('A opção --baseline não faz parte da instalação pelo schema atual.');
        }
        $connection = \App\Shared\Database\ConnectionFactory::get();
        $installed = (new \App\Shared\Database\SchemaInstaller(
            $connection,
            SGI_ROOT . '/database/schema-inicial.sql',
        ))->install();
        if ($installed) {
            echo "Schema inicial instalado.\n";
        }
        foreach ((new \App\Shared\Database\MigrationRunner($connection, SGI_ROOT . '/database/migrations'))->migrate() as $version) {
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
    } elseif ($command === 'students:senha-inicial') {
        $environment = \App\Shared\Config\Env::get('SGI_APP_ENV');
        $database = \App\Shared\Config\Env::get('SGI_DB_NAME', 'sgi') ?? 'sgi';
        $confirmation = null;
        foreach ($argv as $argument) {
            if (str_starts_with($argument, '--confirm-database=')) {
                $confirmation = substr($argument, strlen('--confirm-database='));
                break;
            }
        }
        if ($environment !== 'development') {
            throw new RuntimeException('A inicialização da senha compartilhada só pode ser executada em SGI_APP_ENV=development.');
        }
        if (!is_string($confirmation) || $confirmation === '' || !hash_equals($database, $confirmation)) {
            throw new RuntimeException('Confirme explicitamente o banco local com --confirm-database=<SGI_DB_NAME>.');
        }
        $updated = (new \App\Modules\Acesso\Infrastructure\MysqliStudentPasswordInitializer(\App\Shared\Database\ConnectionFactory::get()))->initialize();
        echo "Alunos inicializados: {$updated}. A senha inicial é sesi-senai e exige troca no próximo acesso.\n";
    } elseif ($command === 'pontuacao:diagnosticar') {
        $diagnostico = (new \App\Modules\Resultados\Infrastructure\MysqliPodioRepository(\App\Shared\Database\ConnectionFactory::get()))->diagnosticar();
        echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    } else {
        echo "Uso: php bin/sgi.php schema:install\n";
        echo "     php bin/sgi.php migrate (schema inicial + migrations pendentes)\n";
        echo "     php bin/sgi.php admin:create (SGI_ADMIN_LOGIN, SGI_ADMIN_NAME, SGI_ADMIN_PASSWORD)\n";
        echo "     php bin/sgi.php students:senha-inicial --confirm-database=<SGI_DB_NAME> (somente desenvolvimento)\n";
        echo "     php bin/sgi.php pontuacao:diagnosticar\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
