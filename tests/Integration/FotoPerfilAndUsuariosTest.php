<?php

declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use SGITests\Support\ProcessExitCode;
use SGITests\Support\TestDatabase;
use SGITests\Support\AuditFixtures;
use mysqli;
use CURLFile;

class FotoPerfilAndUsuariosTest
{
    public static function run(int $idTurma, int $idEdicao): void
    {
        echo "\n  \033[1;34m[Suite 12: Gestão de Fotos de Perfil e Usuários]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 12.1 Consultar dados do usuário atual autenticado
        $resAuth = $admin->get('api/v1/session');
        Assertions::assertStatus("Consulta de sessão autenticada (HTTP 200)", $resAuth, 200);
        $user = $resAuth['json']['usuario'] ?? [];
        $idUser = (int) ($user['id'] ?? 0);
        Assertions::assert("Identificação de usuário autenticado", $idUser > 0);

        // 12.2 Testar upload de foto de perfil
        $tmpImg = sys_get_temp_dir() . '/test_avatar.png';
        // Cria imagem PNG 1x1 em bytes válidos
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpImg, $pngContent);

        $cfile = new CURLFile($tmpImg, 'image/png', 'test_avatar.png');
        $resUpload = $admin->postForm('api/v1/foto', [
            'foto' => $cfile,
        ]);
        Assertions::assert("Upload de foto de perfil (PNG)", in_array($resUpload['code'], [200, 201], true) && ($resUpload['json']['success'] ?? false) === true);

        // 12.3 Consultar foto de perfil
        $resFoto = $admin->get("api/v1/foto?user_id=$idUser");
        Assertions::assertStatus("Consulta de foto de perfil (HTTP 200)", $resFoto, 200);

        // 12.4 Remover foto de perfil
        $resDelete = $admin->deleteJson('api/v1/foto');
        Assertions::assertJsonSuccess("Exclusão de foto de perfil retorna confirmação válida", $resDelete);
        $after = $admin->get("api/v1/foto?user_id=$idUser");
        Assertions::assert('Foto removida não permanece referenciada no perfil', ($after['json']['foto_usuario'] ?? null) === '');

        // 12.5 Cobrir as ações administrativas que antes ficavam no arquivo procedural.
        $staff = $admin->postJson('api/v1/usuarios?acao=cadastrar_usuario', [
            'nome_usuario' => 'Colaborador de contrato',
            'matricula_usuario' => '991234',
            'senha_usuario' => 'senhaSegura123',
            'data_nasc_usuario' => '1990-01-01',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Cadastro de colaborador pela rota modular', $staff);
        $staffId = (int) ($staff['json']['id_usuario'] ?? 0);
        Assertions::assert('Cadastro de colaborador retorna identificador', $staffId > 0);
        $role = $admin->postJson('api/v1/usuarios?acao=atualizar_colaborador', [
            'id_usuario' => $staffId,
            'is_mesario_clicado' => '1',
        ]);
        Assertions::assertJsonSuccess('Atualização de papel do colaborador', $role);
        $details = $admin->postJson('api/v1/usuarios?acao=atualizar_dados_colaborador', [
            'id_usuario' => $staffId,
            'nome_usuario' => 'Colaborador atualizado',
            'matricula_usuario' => '991234',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Atualização de dados do colaborador', $details);
        $removeStaff = $admin->postJson('api/v1/usuarios?acao=excluir_colaborador', ['id_usuario' => $staffId]);
        Assertions::assertJsonSuccess('Exclusão de colaborador pela rota modular', $removeStaff);

        $student = $admin->postJson('api/v1/usuarios?acao=criar_aluno', [
            'nome_usuario' => 'Aluno de contrato',
            'matricula_usuario' => '991235',
            'data_nasc_usuario' => '2010-02-03',
            'genero_usuario' => 'MASC',
            'turmas_id_turma' => $idTurma,
        ]);
        Assertions::assertJsonSuccess('Cadastro de aluno pela rota modular', $student);
        $studentId = (int) ($student['json']['id_usuario'] ?? 0);
        Assertions::assert('Cadastro de aluno retorna identificador', $studentId > 0);
        Assertions::assert(
            'Cadastro de aluno devolve senha inicial compartilhada do produto',
            is_string($student['json']['senha_temporaria'] ?? null)
            && ($student['json']['senha_temporaria'] ?? '') === 'sesi-senai',
        );
        $editStudent = $admin->postJson('api/v1/usuarios?acao=editar_aluno', [
            'id_usuario' => $studentId,
            'nome_usuario' => 'Aluno de contrato atualizado',
            'matricula_usuario' => '991235',
            'data_nasc_usuario' => '2010-02-03',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Edição de aluno pela rota modular', $editStudent);
        $resetStudent = $admin->postJson('api/v1/usuarios?acao=resetar_senha_aluno', ['id_usuario' => $studentId]);
        Assertions::assertJsonSuccess('Redefinição de senha de aluno', $resetStudent);
        Assertions::assert(
            'Reset de aluno devolve senha inicial compartilhada do produto',
            is_string($resetStudent['json']['senha_temporaria'] ?? null)
            && ($resetStudent['json']['senha_temporaria'] ?? '') === 'sesi-senai',
        );
        $removeStudent = $admin->postJson('api/v1/usuarios?acao=excluir_aluno', ['id_usuario' => $studentId]);
        Assertions::assertJsonSuccess('Exclusão de aluno pela rota modular', $removeStudent);

        self::testStudentTransferPreservesHistory($admin);

        self::testAdministratorRoleChanges($admin, $idUser, $idEdicao);

        @unlink($tmpImg);
    }

    private static function testStudentTransferPreservesHistory(TestClient $admin): void
    {
        echo "\n  \033[1;34m[Regressão: histórico e turma do aluno]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $previousActiveId = AuditFixtures::activeEditionId($connection);
        $fixture = null;
        $occurrenceId = 0;
        $createdStudentId = 0;

        try {
            $fixture = AuditFixtures::createAuthorizationFixture($connection);
            $editionA = $fixture['by_edition']['A'];
            $editionB = $fixture['by_edition']['B'];
            $classA1 = (int) $editionA['turma_ids'][0];
            $classA2 = (int) $editionA['turma_ids'][1];
            $studentWithHistory = (int) $editionA['atleta_ids'][0];
            $studentWithRoster = (int) $editionA['atleta_ids'][1];

            $penalty = 7;
            $insertOccurrence = $connection->prepare(
                "INSERT INTO ocorrencias (titulo_ocorrencia, descricao_ocorrencia, data_ocorrencia,
                    penalidade, status_ocorrencia, usuarios_id_usuario)
                 VALUES ('Penalidade L11', 'Fixture de regressão L11', NOW(), ?, '1', ?)",
            );
            $insertOccurrence->bind_param('ii', $penalty, $studentWithHistory);
            $insertOccurrence->execute();
            $occurrenceId = (int) $insertOccurrence->insert_id;
            $insertOccurrence->close();

            $beforeA1 = self::rankingForClass($admin, (int) $editionA['interclasse_id'], $classA1);
            $beforeA2 = self::rankingForClass($admin, (int) $editionA['interclasse_id'], $classA2);
            $transferWithHistory = $admin->postJson("api/v1/usuarios?id={$studentWithHistory}", [
                'interclasses_id_interclasse' => (int) $editionA['interclasse_id'],
                'turmas_id_turma' => $classA2,
            ]);
            Assertions::assertStatus('Transferência de aluno com ocorrência e equipe é recusada', $transferWithHistory, 400);
            Assertions::assert(
                'Histórico disciplinar e vínculo esportivo continuam com a turma original',
                self::studentClass($connection, $studentWithHistory) === $classA1,
            );
            $afterA1 = self::rankingForClass($admin, (int) $editionA['interclasse_id'], $classA1);
            $afterA2 = self::rankingForClass($admin, (int) $editionA['interclasse_id'], $classA2);
            Assertions::assert(
                'Recusa preserva o desconto no ranking da turma original',
                (int) ($afterA1['pontuacao_liquida'] ?? 0) === (int) ($beforeA1['pontuacao_liquida'] ?? 0),
            );
            Assertions::assert(
                'Recusa não desloca o desconto para a turma de destino',
                (int) ($afterA2['pontuacao_liquida'] ?? 0) === (int) ($beforeA2['pontuacao_liquida'] ?? 0),
            );

            $beforeGender = self::studentDetails($connection, $studentWithRoster);
            $changeRosterGender = $admin->postJson('api/v1/usuarios?acao=editar_aluno', [
                'id_usuario' => $studentWithRoster,
                'nome_usuario' => (string) ($beforeGender['nome_usuario'] ?? ''),
                'matricula_usuario' => (string) ($beforeGender['matricula_usuario'] ?? ''),
                'data_nasc_usuario' => (string) ($beforeGender['data_nasc_usuario'] ?? ''),
                'genero_usuario' => 'FEM',
            ]);
            Assertions::assertStatus('Gênero incompatível com elenco ativo é recusado', $changeRosterGender, 400);
            Assertions::assert(
                'Recusa de gênero preserva o cadastro do atleta',
                self::studentDetails($connection, $studentWithRoster) === $beforeGender,
            );

            $modalityId = (int) $editionA['modalidade_id'];
            $restoreGender = $connection->prepare('UPDATE usuarios SET genero_usuario = ? WHERE id_usuario = ?');
            $originalGender = (string) ($beforeGender['genero_usuario'] ?? 'MASC');
            $studentIdForGender = $studentWithRoster;
            $restoreGender->bind_param('si', $originalGender, $studentIdForGender);
            try {
                // The preceding baseline assertion can leave the synthetic user changed
                // on an unfixed checkout; restore the fixture before each probe.
                $restoreGender->execute();
                $inactiveModality = $connection->prepare("UPDATE modalidades SET status_modalidade = '0' WHERE id_modalidade = ?");
                $inactiveModality->bind_param('i', $modalityId);
                $inactiveModality->execute();
                $inactiveModality->close();

                $genderWithInactiveModality = $admin->postJson('api/v1/usuarios?acao=editar_aluno', [
                    'id_usuario' => $studentWithRoster,
                    'nome_usuario' => (string) ($beforeGender['nome_usuario'] ?? ''),
                    'matricula_usuario' => (string) ($beforeGender['matricula_usuario'] ?? ''),
                    'data_nasc_usuario' => (string) ($beforeGender['data_nasc_usuario'] ?? ''),
                    'genero_usuario' => 'FEM',
                ]);
                Assertions::assertStatus('Elenco de equipe ativa continua protegido com modalidade inativa', $genderWithInactiveModality, 400);
                Assertions::assert(
                    'Modalidade inativa não permite invalidar o elenco nem alterar o cadastro',
                    self::studentDetails($connection, $studentWithRoster) === $beforeGender,
                );

                $restoreGender->execute();
                $inactiveClass = $connection->prepare("UPDATE turmas SET status_turma = '0' WHERE id_turma = ?");
                $inactiveClass->bind_param('i', $classA2);
                $inactiveClass->execute();
                $inactiveClass->close();

                $genderWithInactiveClass = $admin->postJson('api/v1/usuarios?acao=editar_aluno', [
                    'id_usuario' => $studentWithRoster,
                    'nome_usuario' => (string) ($beforeGender['nome_usuario'] ?? ''),
                    'matricula_usuario' => (string) ($beforeGender['matricula_usuario'] ?? ''),
                    'data_nasc_usuario' => (string) ($beforeGender['data_nasc_usuario'] ?? ''),
                    'genero_usuario' => 'FEM',
                ]);
                Assertions::assertStatus('Elenco de equipe ativa continua protegido com turma inativa', $genderWithInactiveClass, 400);
                Assertions::assert(
                    'Turma inativa não permite invalidar o elenco nem alterar o cadastro',
                    self::studentDetails($connection, $studentWithRoster) === $beforeGender,
                );
            } finally {
                $restoreGender->execute();
                $restoreGender->close();
                $restoreModality = $connection->prepare("UPDATE modalidades SET status_modalidade = '1' WHERE id_modalidade = ?");
                $restoreModality->bind_param('i', $modalityId);
                $restoreModality->execute();
                $restoreModality->close();
                $restoreClass = $connection->prepare("UPDATE turmas SET status_turma = '1' WHERE id_turma = ?");
                $restoreClass->bind_param('i', $classA2);
                $restoreClass->execute();
                $restoreClass->close();
            }

            $registration = '98' . random_int(1000000, 9999999);
            $newStudent = $admin->postJson('api/v1/usuarios?acao=criar_aluno', [
                'nome_usuario' => 'Aluno sem histórico L11',
                'matricula_usuario' => $registration,
                'data_nasc_usuario' => '2010-02-03',
                'genero_usuario' => 'MASC',
                'turmas_id_turma' => $classA1,
            ]);
            Assertions::assertJsonSuccess('Cadastro de aluno sem histórico para transferência', $newStudent);
            $createdStudentId = (int) ($newStudent['json']['id_usuario'] ?? 0);
            Assertions::assert('Novo aluno de regressão retorna identificador', $createdStudentId > 0);

            $validTransfer = $admin->postJson("api/v1/usuarios?id={$createdStudentId}", [
                'interclasses_id_interclasse' => (int) $editionA['interclasse_id'],
                'turmas_id_turma' => $classA2,
            ]);
            Assertions::assertJsonSuccess('Aluno sem histórico pode mudar de turma na mesma edição', $validTransfer);
            Assertions::assert(
                'Transferência válida persiste a turma de destino',
                self::studentClass($connection, $createdStudentId) === $classA2,
            );

            $crossEditionTransfer = $admin->postJson("api/v1/usuarios?id={$createdStudentId}", [
                'interclasses_id_interclasse' => (int) $editionB['interclasse_id'],
                'turmas_id_turma' => (int) $editionB['turma_ids'][0],
            ]);
            Assertions::assertStatus('Transferência para outra edição é recusada', $crossEditionTransfer, 403);
            Assertions::assert(
                'Tentativa entre edições preserva a turma',
                self::studentClass($connection, $createdStudentId) === $classA2,
            );

            $studentClient = new TestClient();
            $studentLogin = $studentClient->login($registration, (string) ($newStudent['json']['senha_temporaria'] ?? ''));
            Assertions::assertJsonSuccess('Login do aluno sintético para teste de perfil', $studentLogin);
            Assertions::assertJsonSuccess('Aluno sintético troca a senha antes de acessar API do perfil', $studentClient->changeFirstLoginPassword('PerfilNovo#2026'));
            Assertions::assertJsonSuccess('Aluno sintético aceita termos antes do teste de perfil', $studentClient->postJson('api/v1/termos', []));
            $studentTransfer = $studentClient->postJson("api/v1/usuarios?id={$createdStudentId}", [
                'interclasses_id_interclasse' => (int) $editionA['interclasse_id'],
                'turmas_id_turma' => $classA1,
            ]);
            Assertions::assertStatus('Perfil de aluno não pode atribuir turma administrativa', $studentTransfer, 403);
            Assertions::assert(
                'Recusa do perfil sem privilégios preserva turma e identidade',
                self::studentClass($connection, $createdStudentId) === $classA2,
            );
        } finally {
            if ($occurrenceId > 0) {
                $deleteOccurrence = $connection->prepare('DELETE FROM ocorrencias WHERE id_ocorrencia = ?');
                $deleteOccurrence->bind_param('i', $occurrenceId);
                $deleteOccurrence->execute();
                $deleteOccurrence->close();
            }
            if ($createdStudentId > 0) {
                $deleteTerms = $connection->prepare('DELETE FROM usuarios_has_interclasses WHERE usuarios_id_usuario = ?');
                $deleteTerms->bind_param('i', $createdStudentId);
                $deleteTerms->execute();
                $deleteTerms->close();
                $deleteStudent = $connection->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
                $deleteStudent->bind_param('i', $createdStudentId);
                $deleteStudent->execute();
                $deleteStudent->close();
            }
            if ($fixture !== null) {
                AuditFixtures::restoreAndRemove($connection, $fixture, $previousActiveId);
            }
            $connection->close();
        }
    }

    /** @return array<string, mixed> */
    private static function rankingForClass(TestClient $client, int $editionId, int $classId): array
    {
        $response = $client->get("api/v1/ranking?id_interclasse={$editionId}&id_turma={$classId}");
        Assertions::assertStatus('Consulta de ranking sintético A11', $response, 200);
        foreach (($response['json'] ?? []) as $row) {
            if ((int) ($row['id_turma'] ?? 0) === $classId) {
                return $row;
            }
        }
        return [];
    }

    private static function studentClass(mysqli $connection, int $studentId): int
    {
        $statement = $connection->prepare('SELECT turmas_id_turma FROM usuarios WHERE id_usuario = ?');
        $statement->bind_param('i', $studentId);
        $statement->execute();
        $classId = $statement->get_result()->fetch_column();
        $statement->close();
        return $classId === false || $classId === null ? 0 : (int) $classId;
    }

    /** @return array<string, mixed>|null */
    private static function readUserPersonalData(mysqli $connection, int $userId): ?array
    {
        $statement = $connection->prepare(
            'SELECT nome_usuario, matricula_usuario, genero_usuario, senha_usuario, auth_version
             FROM usuarios WHERE id_usuario = ?',
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $user;
    }

    /** @return array<string, mixed>|null */
    private static function studentDetails(mysqli $connection, int $studentId): ?array
    {
        $statement = $connection->prepare(
            'SELECT nome_usuario, matricula_usuario, genero_usuario, data_nasc_usuario, turmas_id_turma
             FROM usuarios WHERE id_usuario = ?',
        );
        $statement->bind_param('i', $studentId);
        $statement->execute();
        $student = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $student;
    }

    private static function testAdministratorRoleChanges(TestClient $root, int $rootId, int $editionId): void
    {
        echo "\n  \033[1;34m[Regressão: papel e último administrador ativo]\033[0m\n";
        $database = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($database);
        $connection = TestDatabase::connect($database);
        $syntheticUserIds = [];
        $otherEditionId = 0;

        $createStaff = static function (TestClient $client, string $label, bool $administrator) use (&$syntheticUserIds): array {
            $registration = '94' . random_int(1000000, 9999999);
            $password = 'A14-Role-' . bin2hex(random_bytes(6));
            $response = $client->postJson('api/v1/usuarios?acao=cadastrar_usuario', [
                'nome_usuario' => 'A14 ' . $label,
                'matricula_usuario' => $registration,
                'senha_usuario' => $password,
                'data_nasc_usuario' => '1990-01-01',
                'genero_usuario' => 'MASC',
                'is_admin_clicado' => $administrator ? '1' : '0',
            ]);
            Assertions::assertJsonSuccess("Cadastro sintético de colaborador A14 ({$label})", $response);
            $id = (int) ($response['json']['id_usuario'] ?? 0);
            Assertions::assert("Cadastro sintético A14 ({$label}) retorna ID", $id > 0);
            if ($id > 0) {
                $syntheticUserIds[] = $id;
            }
            return ['id' => $id, 'registration' => $registration, 'password' => $password];
        };

        $setRole = static function (TestClient $client, int $userId, string $role, array $headers = []): array {
            return $client->postJson('api/v1/usuarios?acao=atualizar_colaborador', [
                'id_usuario' => $userId,
                'is_admin_clicado' => $role === 'admin' ? '1' : '0',
                'is_mesario_clicado' => $role === 'mesario' ? '1' : '0',
            ], $headers);
        };

        try {
            Assertions::assert('Fixture começa com exatamente um administrador ativo', self::activeAdministratorCount($connection) === 1);
            $adminA = $createStaff($root, 'administrador A', true);
            $clientA = new TestClient();
            Assertions::assertJsonSuccess('Login do administrador A sintético', $clientA->login($adminA['registration'], $adminA['password']));

            $adminNotLogged = $createStaff($root, 'administrador protegido', true);
            $personalDataBefore = self::readUserPersonalData($connection, $adminNotLogged['id']);
            $blockedDetails = $root->postJson('api/v1/usuarios?acao=atualizar_dados_colaborador', [
                'id_usuario' => $adminNotLogged['id'],
                'nome_usuario' => 'Administrador indevidamente alterado',
                'matricula_usuario' => 'A14-ADMIN-ALTERADO',
                'genero_usuario' => 'FEM',
                'senha_usuario' => 'Senha indevida 2026',
            ]);
            Assertions::assertStatus('Administrador não pode alterar dados pessoais de outro administrador', $blockedDetails, 400);
            Assertions::assert(
                'Bloqueio de edição preserva nome, NIF, gênero e senha do administrador',
                self::readUserPersonalData($connection, $adminNotLogged['id']) === $personalDataBefore,
            );

            $removeAdmin = $root->postJson('api/v1/usuarios?acao=excluir_colaborador', [
                'id_usuario' => $adminNotLogged['id'],
            ]);
            Assertions::assertJsonSuccess('Administrador pode excluir outro administrador não logado', $removeAdmin);
            $removedAdmin = self::readUser($connection, $adminNotLogged['id']);
            Assertions::assert(
                'Exclusão de outro administrador desativa a conta e revoga sua versão de autenticação',
                $removedAdmin !== null
                && $removedAdmin['status_usuario'] === '0'
                && (int) $removedAdmin['auth_version'] === (int) $personalDataBefore['auth_version'] + 1,
            );

            $selfRemoval = $root->postJson('api/v1/usuarios?acao=excluir_colaborador', [
                'id_usuario' => $rootId,
            ]);
            Assertions::assertStatus('Administrador não pode excluir a própria conta', $selfRemoval, 400);
            $rootAfterSelfRemoval = self::readUser($connection, $rootId);
            Assertions::assert(
                'Tentativa de autoexclusão preserva a conta do administrador atual',
                $rootAfterSelfRemoval !== null && $rootAfterSelfRemoval['status_usuario'] === '1',
            );

            $rootBefore = self::readUser($connection, $rootId);
            $demoteRoot = $setRole($clientA, $rootId, 'staff');
            Assertions::assertJsonSuccess('Com dois administradores, outro administrador pode rebaixar um deles', $demoteRoot);
            $rootAfter = self::readUser($connection, $rootId);
            Assertions::assert(
                'Rebaixamento efetivo preserva auth_version ao revogar a sessão antiga',
                $rootBefore !== null && $rootAfter !== null
                    && $rootAfter['nivel_usuario'] === '1'
                    && (int) $rootAfter['auth_version'] === (int) $rootBefore['auth_version'] + 1,
            );
            Assertions::assert('Após rebaixar um dos dois, resta um administrador ativo', self::activeAdministratorCount($connection) === 1);

            $adminABefore = self::readUser($connection, $adminA['id']);
            $lastAdministrator = $setRole($clientA, $adminA['id'], 'staff');
            Assertions::assertStatus('Rebaixar o último administrador é recusado', $lastAdministrator, 400);
            $adminAAfter = self::readUser($connection, $adminA['id']);
            Assertions::assert(
                'Recusa do último administrador mantém papel, status e auth_version',
                $adminABefore !== null && $adminAAfter !== null
                    && $adminAAfter['nivel_usuario'] === '0'
                    && $adminAAfter['status_usuario'] === '1'
                    && $adminAAfter['auth_version'] === $adminABefore['auth_version'],
            );
            Assertions::assert('Recusa não revoga a sessão do administrador restante', $clientA->get('api/v1/session')['code'] === 200);

            $csrfBefore = self::readUser($connection, $adminA['id']);
            Assertions::assertStatus(
                'Ação de papel continua exigindo CSRF',
                $setRole($clientA, $adminA['id'], 'staff', ['X-SGI-CSRF' => '']),
                403,
            );
            Assertions::assert('CSRF inválido não altera auth_version do administrador', self::readUser($connection, $adminA['id']) === $csrfBefore);

            Assertions::assertStatus(
                'ID inexistente não retorna sucesso na atualização de colaborador',
                $setRole($clientA, 2000000000, 'mesario'),
                400,
            );
            $studentId = (int) $connection->query("SELECT id_usuario FROM usuarios WHERE matricula_usuario = '2879' LIMIT 1")->fetch_column();
            $studentBefore = self::readUser($connection, $studentId);
            Assertions::assertStatus('Aluno não pode ser enviado à ação de papel de colaborador', $setRole($clientA, $studentId, 'mesario'), 400);
            Assertions::assert('Tentativa de papel não altera a conta do aluno', self::readUser($connection, $studentId) === $studentBefore);

            [$otherEditionId, $outOfScopeUserId] = self::createOutOfEditionCollaborator($connection);
            $syntheticUserIds[] = $outOfScopeUserId;
            Assertions::assertStatus(
                'Colaborador de outra edição não pode ser alterado pela edição ativa',
                $setRole($clientA, $outOfScopeUserId, 'mesario'),
                400,
            );

            $inactiveStaff = $createStaff($clientA, 'inativo', false);
            $inactiveBefore = self::readUser($connection, $inactiveStaff['id']);
            $deactivate = $connection->prepare("UPDATE usuarios SET status_usuario = '0' WHERE id_usuario = ?");
            $deactivate->bind_param('i', $inactiveStaff['id']);
            $deactivate->execute();
            $deactivate->close();
            $inactiveChange = $setRole($clientA, $inactiveStaff['id'], 'mesario');
            Assertions::assertJsonSuccess('Mudança de papel de conta inativa continua permitida', $inactiveChange);
            $inactiveAfter = self::readUser($connection, $inactiveStaff['id']);
            Assertions::assert(
                'Mudança de papel em conta inativa preserva status e incrementa auth_version',
                $inactiveBefore !== null && $inactiveAfter !== null
                    && $inactiveAfter['nivel_usuario'] === '2'
                    && $inactiveAfter['status_usuario'] === '0'
                    && (int) $inactiveAfter['auth_version'] === (int) $inactiveBefore['auth_version'] + 1,
            );

            $adminB = $createStaff($clientA, 'administrador B', true);
            $clientB = new TestClient();
            Assertions::assertJsonSuccess('Login do administrador B sintético', $clientB->login($adminB['registration'], $adminB['password']));
            $versionBeforeSelfDemotion = self::readUser($connection, $adminA['id']);
            Assertions::assertJsonSuccess('Auto rebaixamento é permitido quando há outro administrador ativo', $setRole($clientA, $adminA['id'], 'staff'));
            $versionAfterSelfDemotion = self::readUser($connection, $adminA['id']);
            Assertions::assert(
                'Auto rebaixamento efetivo incrementa auth_version',
                $versionBeforeSelfDemotion !== null && $versionAfterSelfDemotion !== null
                    && (int) $versionAfterSelfDemotion['auth_version'] === (int) $versionBeforeSelfDemotion['auth_version'] + 1,
            );
            Assertions::assert('Auto rebaixamento deixa o outro administrador ativo', self::activeAdministratorCount($connection) === 1);

            Assertions::assertJsonSuccess('Outro administrador pode promover novamente o colaborador', $setRole($clientB, $adminA['id'], 'admin'));
            $clientAFresh = new TestClient();
            Assertions::assertJsonSuccess('Reautenticação após auth_version revogada', $clientAFresh->login($adminA['registration'], $adminA['password']));
            $versionBeforePeerDemotion = self::readUser($connection, $adminB['id']);
            Assertions::assertJsonSuccess('Rebaixamento de outro administrador é permitido quando há dois ativos', $setRole($clientAFresh, $adminB['id'], 'staff'));
            $versionAfterPeerDemotion = self::readUser($connection, $adminB['id']);
            Assertions::assert(
                'Rebaixamento do outro administrador incrementa auth_version',
                $versionBeforePeerDemotion !== null && $versionAfterPeerDemotion !== null
                    && (int) $versionAfterPeerDemotion['auth_version'] === (int) $versionBeforePeerDemotion['auth_version'] + 1,
            );
            Assertions::assert('Rebaixamento de outro administrador mantém um ativo', self::activeAdministratorCount($connection) === 1);

            $sameRoleBefore = self::readUser($connection, $adminA['id']);
            Assertions::assertJsonSuccess('Reenvio do mesmo papel continua idempotente', $setRole($clientAFresh, $adminA['id'], 'admin'));
            Assertions::assert('Papel sem mudança não incrementa auth_version', self::readUser($connection, $adminA['id']) === $sameRoleBefore);

            Assertions::assertJsonSuccess('Segundo administrador é reativado para o teste de concorrência', $setRole($clientAFresh, $adminB['id'], 'admin'));
            $race = self::runConcurrentAdministratorDemotions($connection, $editionId, $adminA['id'], $adminB['id']);
            Assertions::assert('Duas mudanças concorrentes realmente aguardam um bloqueio comum', $race['waited'], $race['diagnostic']);
            $raceResults = array_column($race['results'], 'result');
            sort($raceResults);
            Assertions::assert('Rebaixamentos concorrentes não removem os dois administradores', $raceResults === ['accepted', 'rejected'], json_encode($race['results']));
            Assertions::assert('Após a corrida resta exatamente um administrador ativo', self::activeAdministratorCount($connection) === 1);

            $states = [self::readUser($connection, $adminA['id']), self::readUser($connection, $adminB['id'])];
            $remainingAdministratorId = 0;
            foreach ($states as $state) {
                if ($state !== null && $state['nivel_usuario'] === '0' && $state['status_usuario'] === '1') {
                    $remainingAdministratorId = (int) $state['id_usuario'];
                    break;
                }
            }
            $remainingCredentials = $remainingAdministratorId === $adminA['id'] ? $adminA : $adminB;
            $remainingClient = new TestClient();
            Assertions::assertJsonSuccess('Login do administrador restante após a corrida', $remainingClient->login($remainingCredentials['registration'], $remainingCredentials['password']));
            Assertions::assertJsonSuccess('Administrador restante restaura a conta base do fixture', $setRole($remainingClient, $rootId, 'admin'));
            $rootFresh = new TestClient();
            Assertions::assertJsonSuccess('Login da conta base após restauração do papel', $rootFresh->login('admin', '123'));
            Assertions::assertJsonSuccess('Conta base rebaixa administrador temporário antes da limpeza', $setRole($rootFresh, $remainingAdministratorId, 'staff'));
        } finally {
            $restoreRoot = $connection->prepare("UPDATE usuarios SET nivel_usuario = '0', status_usuario = '1', auth_version = auth_version + 1 WHERE id_usuario = ?");
            $restoreRoot->bind_param('i', $rootId);
            $restoreRoot->execute();
            $restoreRoot->close();
            foreach (array_unique($syntheticUserIds) as $userId) {
                if ($userId > 0) {
                    $delete = $connection->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
                    $delete->bind_param('i', $userId);
                    $delete->execute();
                    $delete->close();
                }
            }
            if ($otherEditionId > 0) {
                $deleteEdition = $connection->prepare('DELETE FROM interclasses WHERE id_interclasse = ?');
                $deleteEdition->bind_param('i', $otherEditionId);
                $deleteEdition->execute();
                $deleteEdition->close();
            }
            $connection->close();
        }
    }

    /** @return array<string, mixed>|null */
    private static function readUser(mysqli $connection, int $userId): ?array
    {
        $statement = $connection->prepare('SELECT id_usuario, nivel_usuario, status_usuario, auth_version FROM usuarios WHERE id_usuario = ?');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    private static function activeAdministratorCount(mysqli $connection): int
    {
        return (int) $connection->query("SELECT COUNT(*) FROM usuarios WHERE nivel_usuario = '0' AND status_usuario = '1'")->fetch_column();
    }

    /** @return array{int,int} */
    private static function createOutOfEditionCollaborator(mysqli $connection): array
    {
        $token = bin2hex(random_bytes(4));
        $editionName = 'A14 Escopo ' . $token;
        $edition = $connection->prepare(
            "INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse)
             VALUES (?, CURRENT_TIMESTAMP, '', '0')",
        );
        $edition->bind_param('s', $editionName);
        $edition->execute();
        $editionId = (int) $connection->insert_id;
        $edition->close();

        $registration = 'A14-SCOPE-' . $token;
        $name = 'A14 Colaborador fora do escopo';
        $password = password_hash('A14-synthetic-password', PASSWORD_DEFAULT);
        $level = '1';
        $gender = 'MASC';
        $birth = '1990-01-01';
        $photo = 'default.jpg';
        $status = '1';
        $user = $connection->prepare(
            'INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, interclasses_id_interclasse)
             VALUES (\'SS\', ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $user->bind_param('ssssssssi', $registration, $name, $password, $level, $gender, $birth, $photo, $status, $editionId);
        $user->execute();
        $userId = (int) $connection->insert_id;
        $user->close();

        return [$editionId, $userId];
    }

    /** @return array{waited:bool,diagnostic:string,results:list<array<string,mixed>>} */
    private static function runConcurrentAdministratorDemotions(mysqli $connection, int $editionId, int $firstUserId, int $secondUserId): array
    {
        $barrier = dirname(__DIR__, 2) . '/test-results/admin-role-' . bin2hex(random_bytes(8));
        if (!mkdir($barrier, 0777, true) && !is_dir($barrier)) {
            throw new \RuntimeException('Não foi possível criar a barreira de papéis.');
        }

        $processes = [];
        $transactionOpen = false;
        try {
            $connection->begin_transaction();
            $transactionOpen = true;
            $lockedAdministrators = $connection->query(
                "SELECT id_usuario FROM usuarios WHERE nivel_usuario = '0' AND status_usuario = '1' ORDER BY id_usuario FOR UPDATE",
            );
            if ($lockedAdministrators->num_rows !== 2) {
                $lockedAdministrators->free();
                throw new \RuntimeException('O teste concorrente exige exatamente dois administradores ativos.');
            }
            $lockedAdministrators->free();

            foreach ([$firstUserId, $secondUserId] as $index => $userId) {
                $pipes = [];
                $command = [
                    PHP_BINARY,
                    '-d',
                    'display_startup_errors=0',
                    dirname(__DIR__) . '/Support/AdministratorRoleWorker.php',
                    $barrier,
                    (string) $index,
                    (string) $userId,
                    (string) $editionId,
                ];
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) {
                    throw new \RuntimeException('Não foi possível iniciar o worker de papel concorrente.');
                }
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }

            $deadline = microtime(true) + 5.0;
            do {
                $ready = is_file($barrier . '/ready-0') && is_file($barrier . '/ready-1');
                if ($ready) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            if (!$ready) {
                throw new \RuntimeException('Os workers concorrentes não anunciaram prontidão.');
            }
            file_put_contents($barrier . '/release', 'go', LOCK_EX);

            $waited = false;
            $diagnostic = '';
            $deadline = microtime(true) + 5.0;
            do {
                $queries = $connection->query(
                    'SELECT INFO FROM information_schema.PROCESSLIST WHERE DB = DATABASE() AND ID <> CONNECTION_ID()',
                );
                $waitingQueries = [];
                while (($row = $queries->fetch_assoc()) !== null) {
                    $query = (string) ($row['INFO'] ?? '');
                    if (str_contains(strtoupper($query), 'GET_LOCK') || str_contains(strtoupper($query), 'FOR UPDATE')) {
                        $waitingQueries[] = $query;
                    }
                }
                $queries->free();
                $diagnostic = implode(' | ', $waitingQueries);
                if (count($waitingQueries) >= 2) {
                    $waited = true;
                    break;
                }
                usleep(20000);
            } while (microtime(true) < $deadline);

            $connection->commit();
            $transactionOpen = false;
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $observedExitCode = -1;
                $deadline = microtime(true) + 10.0;
                do {
                    $status = proc_get_status($process);
                    $observedExitCode = ProcessExitCode::observe($observedExitCode, $status) ?? -1;
                    if (!$status['running']) {
                        break;
                    }
                    usleep(10000);
                } while (microtime(true) < $deadline);
                if ($status['running']) {
                    proc_terminate($process);
                    throw new \RuntimeException('Worker concorrente excedeu o prazo.');
                }
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $closeExitCode = proc_close($process);
                if (ProcessExitCode::resolve($closeExitCode, $observedExitCode) !== 0) {
                    throw new \RuntimeException('Worker de papel falhou: ' . $errors);
                }
                $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($result)) {
                    throw new \RuntimeException('Worker de papel retornou JSON inválido.');
                }
                $results[] = $result;
            }

            return ['waited' => $waited, 'diagnostic' => $diagnostic, 'results' => $results];
        } finally {
            if (!is_file($barrier . '/release')) {
                @file_put_contents($barrier . '/release', 'go', LOCK_EX);
            }
            if ($transactionOpen) {
                $connection->rollback();
            }
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    $status = proc_get_status($process);
                    if ($status['running']) {
                        proc_terminate($process);
                    }
                }
                foreach ([1, 2] as $index) {
                    if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                        fclose($pipes[$index]);
                    }
                }
                if (is_resource($process)) {
                    proc_close($process);
                }
            }
            foreach (glob($barrier . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($barrier);
        }
    }
}
