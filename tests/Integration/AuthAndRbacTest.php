<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class AuthAndRbacTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 1: Autenticação, RBAC e Perfis de Acesso]\033[0m\n";

        $client = new TestClient();

        // 1.1 Login com credenciais válidas - Admin
        $res = $client->login('admin', '123');
        Assertions::assertStatus("Login do Administrador (HTTP 200)", $res, 200);
        Assertions::assertJsonSuccess("Retorno de sucesso no login do Admin", $res);
        Assertions::assert("Redirecionamento de Admin para home.php", str_contains((string)($res['json']['redirect'] ?? ''), 'home.php'));

        // 1.2 Login Colaborador
        $clientColab = new TestClient();
        $resColab = $clientColab->login('colab', '123');
        Assertions::assertJsonSuccess("Login do Colaborador (nível 1)", $resColab);

        // 1.3 Login Mesário
        $clientMes = new TestClient();
        $resMes = $clientMes->login('mesario', '123');
        Assertions::assertJsonSuccess("Login do Mesário (nível 2)", $resMes);

        // 1.4 Login Aluno
        $clientAluno = new TestClient();
        $resAluno = $clientAluno->login('2879', '123');
        Assertions::assertJsonSuccess("Login do Aluno/Competidor (nível 3)", $resAluno);
        Assertions::assert("Redirecionamento de Aluno para portal do aluno", str_contains((string)($resAluno['json']['redirect'] ?? ''), 'alunos/home.php'));

        // 1.5 Rejeição de Senha Incorreta
        $clientAnon = new TestClient();
        $resInvalido = $clientAnon->login('admin', 'senha_totalmente_errada');
        Assertions::assertStatus("Bloqueio de senha incorreta (HTTP 401)", $resInvalido, 401);

        // 1.6 Rejeição de Matrícula Inexistente
        $resInexistente = $clientAnon->login('usuario_fantasma_9999', '123');
        Assertions::assertStatus("Bloqueio de matrícula inexistente (HTTP 401)", $resInexistente, 401);

        // 1.7 Bloqueio de Acesso a Área Staff sem Login
        $resSemSessao = $clientAnon->get('views/src/pages/dashboard.php');
        Assertions::assert("Redirecionamento/Bloqueio de rota staff sem sessão", $resSemSessao['code'] === 200 || $resSemSessao['code'] === 302);
        // O conteúdo não pode carregar o dashboard administrativo para anônimos
        Assertions::assert("Não expõe conteúdo de admin sem autenticação", !str_contains((string)$resSemSessao['body'], 'Total de Jogos'));

        // 1.8 Troca de Senha - Validação de mínimo de 6 dígitos
        $resTrocaCurta = $client->postJson('api/trocar_senha.php', [
            'nova_senha' => '123',
            'confirmar_senha' => '123'
        ]);
        Assertions::assert("Rejeição de troca para senha com menos de 6 caracteres", ($resTrocaCurta['json']['success'] ?? false) === false);

        // 1.9 Troca de Senha - Divergência na confirmação
        $resTrocaDiv = $client->postJson('api/trocar_senha.php', [
            'nova_senha' => 'senhaSegura123',
            'confirmar_senha' => 'senhaDiferente456'
        ]);
        Assertions::assert("Rejeição quando as senhas não coincidem", ($resTrocaDiv['json']['success'] ?? false) === false);

        // 1.10 Logout
        $resLogout = $client->get('api/logout.php');
        Assertions::assert("Execução de logout limpo", $resLogout['code'] === 200 || $resLogout['code'] === 302);
    }
}
