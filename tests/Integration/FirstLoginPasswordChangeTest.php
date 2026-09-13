<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\Assertions;
use SGITests\Support\TestClient;
use SGITests\Support\TestDatabase;

final class FirstLoginPasswordChangeTest
{
    public static function run(int $editionId): void
    {
        echo "\n  \033[1;34m[Regressão: troca obrigatória de senha no primeiro acesso]\033[0m\n";

        $admin = new TestClient();
        Assertions::assertJsonSuccess('Administrador autentica para preparar aluno de primeiro acesso', $admin->login('admin', '123'));
        $classes = $admin->get('api/v1/turmas?id_interclasse=' . $editionId);
        $classId = (int) (($classes['json'][0]['id_turma'] ?? 0));
        Assertions::assert('Edição de teste contém turma para criar aluno de primeiro acesso', $classId > 0);
        if ($classId <= 0) {
            return;
        }

        $registration = '9' . date('ymdHis') . random_int(10, 99);
        $created = $admin->postJson('api/v1/usuarios?acao=criar_aluno', [
            'nome_usuario' => 'Aluno Primeiro Acesso',
            'matricula_usuario' => $registration,
            'data_nasc_usuario' => '2010-01-02',
            'genero_usuario' => 'MASC',
            'turmas_id_turma' => $classId,
        ]);
        Assertions::assertJsonSuccess('Cadastro administrativo cria aluno temporário', $created);
        $userId = (int) ($created['json']['id_usuario'] ?? 0);
        $temporaryPassword = (string) ($created['json']['senha_temporaria'] ?? '');
        Assertions::assert('Cadastro informa a senha inicial pública única', $temporaryPassword === 'sesi-senai');
        Assertions::assert('Cadastro marca a troca de senha como pendente no banco', self::passwordState($userId)['pending'] === true);
        Assertions::assert('Cadastro persiste somente hash da senha inicial', self::passwordState($userId)['hash_matches_initial'] === true);

        // Use the credential actually returned so the baseline can reach the
        // broken authorization path even before the fixed credential exists.
        $student = new TestClient();
        $login = $student->login($registration, $temporaryPassword);
        Assertions::assertJsonSuccess('Aluno entra usando a senha temporária emitida', $login);
        Assertions::assert(
            'Primeiro login prioriza a página obrigatória de troca de senha',
            str_ends_with((string) ($login['json']['redirect'] ?? ''), '/aluno/trocar-senha'),
        );
        $passwordPageResponse = $student->getWithoutRedirects('aluno/trocar-senha');
        Assertions::assert('A página de troca está disponível no primeiro login', self::passwordPage($passwordPageResponse), self::passwordPageDetails($passwordPageResponse));
        Assertions::assert(
            'Página de troca de senha não pode ser armazenada em cache',
            strtolower((string) ($passwordPageResponse['headers']['Cache-Control'] ?? '')) === 'no-store',
        );
        $homePageResponse = $student->getWithoutRedirects('aluno/inicio');
        Assertions::assert(
            'Acesso direto ao início redireciona para a troca obrigatória',
            ($homePageResponse['code'] ?? 0) === 302
                && str_ends_with((string) ($homePageResponse['headers']['Location'] ?? ''), '/aluno/trocar-senha'),
            self::passwordPageDetails($homePageResponse),
        );
        $termsPageResponse = $student->getWithoutRedirects('aluno/termos');
        Assertions::assert(
            'Acesso direto aos termos redireciona para a troca obrigatória',
            ($termsPageResponse['code'] ?? 0) === 302
                && str_ends_with((string) ($termsPageResponse['headers']['Location'] ?? ''), '/aluno/trocar-senha'),
            self::passwordPageDetails($termsPageResponse),
        );

        $pendingSession = new TestClient();
        $pendingSession->login($registration, $temporaryPassword);
        $logoutSession = new TestClient();
        $logoutSession->login($registration, $temporaryPassword);

        foreach (['api/v1/termos', 'api/v1/session', 'api/v1/jogos', 'api/v1/inscricoes'] as $endpoint) {
            $response = $endpoint === 'api/v1/inscricoes'
                ? $student->postJson($endpoint, ['id_interclasse' => $editionId, 'id_equipes' => []])
                : $student->get($endpoint);
            Assertions::assert(
                "Aluno pendente não alcança API de negócio [$endpoint]",
                ($response['code'] ?? 0) === 403
                    && str_ends_with((string) ($response['json']['redirect'] ?? ''), '/aluno/trocar-senha'),
            );
        }
        Assertions::assert(
            'GET não herda a permissão específica de POST da API de senha',
            ($student->get('api/v1/senha')['code'] ?? 0) === 403,
        );
        $logout = $logoutSession->postJson('api/v1/logout', []);
        Assertions::assert(
            'Aluno pendente ainda pode sair',
            ($logout['code'] ?? 0) === 200 && str_contains((string) ($logout['body'] ?? ''), 'form_desktop'),
        );

        $before = self::passwordState($userId);
        $newPassword = 'PrimeiroAcesso#2026';
        $payload = ['nova_senha' => $newPassword, 'confirmar_senha' => $newPassword];
        $badCsrf = $student->postJson('api/v1/senha', $payload, ['X-SGI-CSRF' => 'token-invalido']);
        Assertions::assert(
            'Troca obrigatória continua protegida por CSRF',
            ($badCsrf['code'] ?? 0) === 403
                && str_contains((string) ($badCsrf['json']['message'] ?? ''), 'Token de segurança'),
        );
        Assertions::assert('CSRF inválido preserva hash, pendência e versão', self::passwordState($userId) === $before);

        $short = $student->postJson('api/v1/senha', ['nova_senha' => 'curta', 'confirmar_senha' => 'curta']);
        Assertions::assert('Senha curta não conclui o primeiro acesso', ($short['json']['success'] ?? true) === false);
        $mismatch = $student->postJson('api/v1/senha', ['nova_senha' => $newPassword, 'confirmar_senha' => 'OutraSenha#2026']);
        Assertions::assert('Confirmação divergente não conclui o primeiro acesso', ($mismatch['json']['success'] ?? true) === false);
        Assertions::assert('Validações inválidas preservam a pendência', self::passwordState($userId)['pending'] === true);

        $changed = $student->postJson('api/v1/senha', $payload);
        Assertions::assertJsonSuccess('Aluno troca senha sem informar a senha temporária no formulário', $changed);
        Assertions::assert(
            'Resposta da troca de senha não pode ser armazenada em cache',
            strtolower((string) ($changed['headers']['Cache-Control'] ?? '')) === 'no-store',
        );
        $after = self::passwordState($userId);
        Assertions::assert('Troca confirmada limpa a pendência junto com o hash', $after['pending'] === false && $after['hash_matches_new'] === true);
        Assertions::assert('Troca de senha incrementa auth_version uma única vez', $after['auth_version'] === $before['auth_version'] + 1);
        Assertions::assertStatus('Sessão que trocou a senha continua válida', $student->get('api/v1/termos'), 200);
        Assertions::assertStatus('Sessão concorrente antiga é revogada por auth_version', $pendingSession->get('api/v1/session'), 401);

        $oldPasswordLogin = new TestClient();
        Assertions::assertStatus('Senha inicial deixa de autenticar após a troca', $oldPasswordLogin->login($registration, 'sesi-senai'), 401);
        $freshSession = new TestClient();
        $freshLogin = $freshSession->login($registration, $newPassword);
        Assertions::assert('Login após a troca segue para termos ainda não aceitos', str_ends_with((string) ($freshLogin['json']['redirect'] ?? ''), '/aluno/termos'));
        Assertions::assert('Aluno só aceita termos depois da troca', ($freshSession->postJson('api/v1/termos', [])['json']['success'] ?? false) === true);
        Assertions::assertStatus('Aluno acessa o portal depois da troca e do aceite', $freshSession->get('aluno/inicio'), 200);
        $completedPasswordPage = $freshSession->getWithoutRedirects('aluno/trocar-senha');
        Assertions::assert(
            'Aluno sem pendência não permanece na página de troca obrigatória',
            ($completedPasswordPage['code'] ?? 0) === 302
                && str_ends_with((string) ($completedPasswordPage['headers']['Location'] ?? ''), '/aluno/inicio'),
        );
        $sharedPasswordAttempt = $freshSession->postJson('aluno/perfil', [
            'nome_usuario' => 'Aluno Primeiro Acesso',
            'senha_atual' => $newPassword,
            'nova_senha' => 'sesi-senai',
        ]);
        Assertions::assert(
            'Perfil de aluno também impede reutilizar a senha inicial compartilhada',
            ($sharedPasswordAttempt['json']['success'] ?? true) === false
            && self::passwordState($userId) === $after,
        );

        $reset = $admin->postJson('api/v1/usuarios?acao=resetar_senha_aluno', ['id_usuario' => $userId]);
        Assertions::assertJsonSuccess('Reset administrativo restaura a senha inicial compartilhada', $reset);
        Assertions::assert(
            'Reset marca nova troca pendente e redefine o hash inicial',
            ($reset['json']['senha_temporaria'] ?? '') === 'sesi-senai'
            && self::passwordState($userId)['pending'] === true
            && self::passwordState($userId)['hash_matches_initial'] === true,
        );
        $resetState = self::passwordState($userId);
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $staleRepository = new \App\Modules\Acesso\Infrastructure\MysqliPerfilRepository($connection);
        $stalePasswordWrite = $staleRepository->update(
            $userId,
            'Tentativa de senha desatualizada',
            password_hash('SenhaObsoleta#2026', PASSWORD_DEFAULT),
            $after['auth_version'],
        );
        Assertions::assert(
            'Reset vence alteração de perfil concorrente com auth_version antigo',
            !$stalePasswordWrite && self::passwordState($userId) === $resetState,
        );
        $connection->close();
        Assertions::assertStatus('Reset administrativo revoga sessões antigas', $freshSession->get('api/v1/session'), 401);
        $resetLogin = new TestClient();
        $resetAuth = $resetLogin->login($registration, 'sesi-senai');
        Assertions::assert('Senha após reset exige novamente a página de troca', str_ends_with((string) ($resetAuth['json']['redirect'] ?? ''), '/aluno/trocar-senha'));
    }

    private static function passwordPage(array $response): bool
    {
        $body = (string) ($response['body'] ?? '');
        return ($response['code'] ?? 0) === 200
            && str_contains($body, 'id="tituloPrimeiroAcesso"')
            && str_contains($body, 'id="formPrimeiroAcesso"')
            && !str_contains($body, 'id="listaInterclassesAluno"');
    }

    private static function passwordPageDetails(array $response): string
    {
        $body = (string) ($response['body'] ?? '');
        return 'HTTP ' . (int) ($response['code'] ?? 0)
            . '; Location=' . (string) ($response['headers']['Location'] ?? '')
            . '; título=' . (str_contains($body, 'id="tituloPrimeiroAcesso"') ? 'sim' : 'não')
            . '; formulário=' . (str_contains($body, 'id="formPrimeiroAcesso"') ? 'sim' : 'não')
            . '; início=' . (str_contains($body, 'id="listaInterclassesAluno"') ? 'sim' : 'não')
            . '; corpo=' . mb_substr($body, 0, 180);
    }

    /** @return array{pending: ?bool, auth_version: int, hash_matches_initial: bool, hash_matches_new: bool} */
    private static function passwordState(int $userId): array
    {
        if ($userId <= 0) {
            return ['pending' => null, 'auth_version' => 0, 'hash_matches_initial' => false, 'hash_matches_new' => false];
        }
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $column = $connection->query("SHOW COLUMNS FROM usuarios LIKE 'senha_troca_pendente'");
        if ($column === false || $column->num_rows !== 1) {
            $connection->close();
            return ['pending' => null, 'auth_version' => 0, 'hash_matches_initial' => false, 'hash_matches_new' => false];
        }
        $statement = $connection->prepare('SELECT senha_usuario, senha_troca_pendente, auth_version FROM usuarios WHERE id_usuario = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        $connection->close();
        $hash = (string) ($row['senha_usuario'] ?? '');
        return [
            'pending' => array_key_exists('senha_troca_pendente', $row) ? (int) $row['senha_troca_pendente'] === 1 : null,
            'auth_version' => (int) ($row['auth_version'] ?? 0),
            'hash_matches_initial' => password_verify('sesi-senai', $hash),
            'hash_matches_new' => password_verify('PrimeiroAcesso#2026', $hash),
        ];
    }
}
