<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use App\Shared\Storage\StoragePaths;

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

        foreach (['bootstrap-theme.css', 'shared.css', 'admin.css', 'aluno.css', 'login.css'] as $bundle) {
            $asset = $client->get('assets/css/' . $bundle);
            Assertions::assertStatus("Bundle CSS público servido: {$bundle}", $asset, 200);
            Assertions::assert(
                "Bundle CSS possui content-type correto: {$bundle}",
                str_contains((string) ($asset['headers']['Content-Type'] ?? ''), 'text/css')
                    || str_contains((string) ($asset['body'] ?? ''), 'SGI'),
            );
        }

        $testResultsRoot = realpath(dirname(__DIR__, 2) . '/test-results');
        $uploadDirectory = realpath(StoragePaths::turmaPdfs());
        $isolatedUploadDirectory = $testResultsRoot !== false
            && $uploadDirectory !== false
            && str_starts_with(
                strtolower($uploadDirectory . DIRECTORY_SEPARATOR),
                strtolower(rtrim($testResultsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR),
            );
        Assertions::assert('Fixture sintética de PDF fica em diretório isolado de test-results', $isolatedUploadDirectory);
        if ($isolatedUploadDirectory) {
            $filename = 'turma_public_boundary_' . bin2hex(random_bytes(8)) . '.pdf';
            $pdfPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
            $marker = "%PDF-1.4\nSGI-PRIVATE-TURMA-PDF-FIXTURE\n";
            $written = file_put_contents($pdfPath, $marker);
            Assertions::assert('Fixture de PDF privado foi criada', $written === strlen($marker));
            if ($written === strlen($marker)) {
                try {
                    $pdfUrl = 'uploads/turmas/' . rawurlencode($filename);
                    $student = new TestClient();
                    Assertions::assertJsonSuccess('Login do aluno para testar a URL antiga do PDF', $student->login('2879', '123'));
                    $mesario = new TestClient();
                    Assertions::assertJsonSuccess('Login do mesário para testar a URL antiga do PDF', $mesario->login('mesario', '123'));
                    $administrator = new TestClient();
                    Assertions::assertJsonSuccess('Login do administrador para testar a URL antiga do PDF', $administrator->login('admin', '123'));

                    foreach ([
                        'Anônimo' => $client,
                        'Aluno' => $student,
                        'Mesário' => $mesario,
                        'Administrador' => $administrator,
                    ] as $profile => $viewer) {
                        $response = $viewer->get($pdfUrl);
                        Assertions::assertStatus("URL pública antiga do PDF de turma recusada para {$profile}", $response, 404);
                        Assertions::assert("Corpo sintético do PDF não é exposto para {$profile}", !str_contains((string) ($response['body'] ?? ''), 'SGI-PRIVATE-TURMA-PDF-FIXTURE'));
                    }
                    Assertions::assertStatus('HEAD da URL pública antiga do PDF é recusado', $client->request($pdfUrl, 'HEAD'), 404);
                } finally {
                    @unlink($pdfPath);
                }
            }
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
