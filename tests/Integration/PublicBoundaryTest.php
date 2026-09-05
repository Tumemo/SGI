<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;

final class PublicBoundaryTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 14: Fronteira Pública e Arquivos Protegidos]\033[0m\n";

        $client = new TestClient();

        foreach (['config/db.php', 'src/Shared/Config/Env.php', 'tests/run_all.php', 'vendor/autoload.php', 'composer.json', 'api/filtros.php', 'api/conversor_pdf.php'] as $path) {
            $response = $client->get($path);
            Assertions::assertStatus("Arquivo interno não exposto: {$path}", $response, 404);
        }

        $traversal = $client->get('views/%2e%2e/config/db.php');
        Assertions::assert('Tentativa de traversal é rejeitada', in_array($traversal['code'], [400, 404], true));

        $asset = $client->get('views/src/styles/style.css');
        Assertions::assertStatus('Asset CSS público servido pelo front controller', $asset, 200);
        Assertions::assert(
            'Asset CSS possui content-type correto',
            str_contains((string) ($asset['body'] ?? ''), 'SGI - Folha de Estilos Principal'),
        );

        $publicSource = $client->get('public/index.php');
        Assertions::assertStatus('Código do front controller não é baixável', $publicSource, 404);
    }
}
