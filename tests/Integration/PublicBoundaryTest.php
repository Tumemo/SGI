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

        foreach (['config/db.php', 'src/Shared/Config/Env.php', 'tests/run_all.php', 'vendor/autoload.php', 'composer.json'] as $path) {
            $response = $client->get($path);
            Assertions::assertStatus("Arquivo interno não exposto: {$path}", $response, 404);
        }

        foreach (['api/v1/filtros', 'api/v1/conversor-pdf', 'api/v1/pontuacao'] as $path) {
            $response = $client->get($path);
            Assertions::assert(
                "Arquivo interno não exposto: {$path}",
                in_array($response['code'], [401, 404], true),
            );
        }

        $traversal = $client->get('views/%2e%2e/config/db.php');
        Assertions::assert('Tentativa de traversal é rejeitada', in_array($traversal['code'], [400, 404], true));

        foreach (['admin.css', 'aluno.css', 'login.css'] as $bundle) {
            $asset = $client->get('assets/css/' . $bundle);
            Assertions::assertStatus("Bundle CSS público servido: {$bundle}", $asset, 200);
            Assertions::assert(
                "Bundle CSS possui content-type correto: {$bundle}",
                str_contains((string) ($asset['headers']['Content-Type'] ?? ''), 'text/css')
                    || str_contains((string) ($asset['body'] ?? ''), 'SGI'),
            );
        }

        $publicSource = $client->get('public/index.php');
        Assertions::assertStatus('Código do front controller não é baixável', $publicSource, 404);

        $health = $client->get('api/v1/health');
        Assertions::assertStatus('Núcleo HTTP modular responde pelo namespace versionado', $health, 200);
        Assertions::assert(
            'Resposta de saúde possui payload JSON válido',
            ($health['json']['status'] ?? null) === 'ok' && ($health['json']['service'] ?? null) === 'sgi',
        );

        $admin = new TestClient();
        $login = $admin->login('admin', '123');
        Assertions::assertStatus('Login para validar controlador modular', $login, 200);
        $types = $admin->get('api/v1/tipos-modalidade');
        Assertions::assertStatus('Endpoint modular de tipos de modalidade responde', $types, 200);
        Assertions::assert('Controlador modular retorna uma lista', is_array($types['json']));

        foreach ([
            'api/v1/categorias' => 'categorias',
            'api/v1/locais' => 'locais',
            'api/v1/modalidades' => 'modalidades',
            'api/v1/ranking' => 'ranking',
        ] as $endpoint => $label) {
            $response = $admin->get($endpoint);
            Assertions::assertStatus("Endpoint modular de {$label} responde", $response, 200);
            Assertions::assert("Endpoint modular de {$label} retorna JSON", is_array($response['json']));
        }
    }
}
