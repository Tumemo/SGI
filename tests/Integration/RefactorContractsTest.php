<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;

final class RefactorContractsTest
{
    public static function run(): void
    {
        echo "\n  [Contratos de segurança e compatibilidade da refatoração]\n";
        $admin = new TestClient();
        Assertions::assertJsonSuccess('Preparação dos contratos HTTP', $admin->login('admin', '123'));

        $withoutToken = $admin->postJson('api/trocar_senha.php', [
            'nova_senha' => 'curta',
            'confirmar_senha' => 'curta',
        ], ['X-SGI-CSRF' => '']);
        Assertions::assertStatus('Token CSRF ausente é rejeitado também em teste', $withoutToken, 403);

        $foreignOrigin = $admin->postJson('api/trocar_senha.php', [], ['Origin' => 'https://other.invalid']);
        Assertions::assertStatus('Origem externa é rejeitada', $foreignOrigin, 403);

        $anonymous = new TestClient();
        Assertions::assertStatus('Consulta de sessão anônima é rejeitada', $anonymous->get('api/auth.php'), 401);
        $session = $admin->get('api/auth.php');
        Assertions::assert('Consulta de sessão informa usuário autenticado', ($session['json']['success'] ?? false) && ($session['json']['usuario']['nivel'] ?? -1) === 0);
        Assertions::assertStatus(
            'Upload antigo exige autenticação antes de processar arquivos',
            $anonymous->postForm('views/src/pages/upload_turma_pdf.php', []),
            401,
        );
        foreach (['views/src/pages/componentes/head.php', 'views/src/componentes/footer.php', 'resources/views/pages/competicoes/placar.php'] as $path) {
            Assertions::assertStatus('Template não é uma rota pública: ' . $path, $anonymous->get($path), 404);
        }

        foreach ([
            'tipoModalidade.php' => 'tipos-modalidade',
            'categorias.php' => 'categorias',
            'locais.php' => 'locais',
            'modalidades.php' => 'modalidades',
            'ranking.php' => 'ranking',
            'turmas.php' => 'turmas',
            'arrecadacao.php' => 'arrecadacao',
            'ocorrencias.php' => 'ocorrencias',
            'ocorrencias_turmas.php' => 'ocorrencias-turmas',
            'equipes.php' => 'equipes',
        ] as $legacy => $resource) {
            $old = $admin->get('api/' . $legacy);
            $new = $admin->get('api/v1/' . $resource);
            Assertions::assertStatus('Rota versionada preserva status: ' . $resource, $new, $old['code']);
            Assertions::assert('Rota versionada preserva dados: ' . $resource, $new['json'] === $old['json']);
        }
    }
}
