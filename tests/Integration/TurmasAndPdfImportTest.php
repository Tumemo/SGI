<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;
use CURLFile;

class TurmasAndPdfImportTest
{
    public static function run(int $idEdicao): int
    {
        echo "\n  \033[1;34m[Suite 3: Turmas e Importação de Alunos via PDF]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 3.1 Consultar turmas geradas automaticamente
        $resTurmas = $admin->get("api/v1/turmas?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de turmas da edição (HTTP 200)", $resTurmas, 200);
        $turmas = $resTurmas['json'] ?? [];
        Assertions::assert("Total de 7 turmas padrão geradas (6EF ao 3EMA)", count($turmas) === 7, "Total: " . count($turmas));

        $turmaAlvo = $turmas[0] ?? null;
        $idTurma = (int) ($turmaAlvo['id_turma'] ?? 0);
        Assertions::assert("ID válido para turma de teste", $idTurma > 0);

        // 3.2 Upload de PDF de alunos
        $pdfPath = dirname(__DIR__, 2) . '/tests/fixtures/6EFB.pdf';
        if (!file_exists($pdfPath)) {
            $pdfPath = 'C:/xampp/htdocs/SGI/tests/fixtures/6EFB.pdf';
        }

        if (file_exists($pdfPath) && $idTurma > 0) {
            $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
            $uploadDirectory = (string) (getenv('SGI_UPLOAD_DIR') ?: '');
            $persistedPdfPath = $uploadDirectory . DIRECTORY_SEPARATOR . 'turma_' . $idTurma . '.pdf';
            $oldPdfHash = hash_file('sha256', $pdfPath);
            $seededPreviousPdf = $uploadDirectory !== ''
                && (is_dir($uploadDirectory) || @mkdir($uploadDirectory, 0770, true))
                && @copy($pdfPath, $persistedPdfPath);
            Assertions::assert(
                'Fixture de upload prepara PDF anterior na pasta isolada do runner',
                $seededPreviousPdf && is_string($oldPdfHash),
            );

            $replacementPdfPath = tempnam(sys_get_temp_dir(), 'sgi-pdf-replacement-');
            $sourcePdfContents = file_get_contents($pdfPath);
            $replacementPdfContents = is_string($sourcePdfContents)
                ? $sourcePdfContents . "\n% tentativa sintética de atualização\n"
                : false;
            $replacementPdfReady = is_string($replacementPdfPath)
                && is_string($replacementPdfContents)
                && file_put_contents($replacementPdfPath, $replacementPdfContents) !== false;
            Assertions::assert(
                'PDF enviado para a falha tem conteúdo diferente do arquivo anterior',
                $replacementPdfReady
                    && is_string($oldPdfHash)
                    && hash_file('sha256', $replacementPdfPath) !== $oldPdfHash,
            );

            $beforeInternalFailure = self::countStudents($database, $idTurma, $idEdicao);
            $database->query(
                "CREATE TRIGGER sgi_n07_import_failure BEFORE INSERT ON usuarios FOR EACH ROW
                 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'N07_IMPORT_SQL_MARKER C:/synthetic/private/Importer.php:47 #0'",
            );
            try {
                $internalFailure = $admin->postForm('api/v1/importacoes/turma-pdf', [
                    'pdf_arquivo' => new CURLFile((string) $replacementPdfPath, 'application/pdf', '6EFB-atualizado.pdf'),
                    'id_turma' => (string) $idTurma,
                    'id_interclasse' => (string) $idEdicao,
                ]);
                Assertions::assert(
                    'Falha SQL da importação retorna 500 sem caminho, parser ou stack no envelope',
                    ($internalFailure['code'] ?? 0) === 500
                        && ($internalFailure['json']['success'] ?? true) === false
                        && !str_contains((string) ($internalFailure['body'] ?? ''), 'N07_IMPORT_SQL_MARKER')
                        && !str_contains((string) ($internalFailure['body'] ?? ''), 'synthetic/private')
                        && !str_contains((string) ($internalFailure['body'] ?? ''), '#0'),
                    (string) ($internalFailure['body'] ?? ''),
                );
                Assertions::assert(
                    'Falha da importação preserva o PDF válido já publicado para a turma',
                    is_string($oldPdfHash)
                        && is_file($persistedPdfPath)
                        && hash_file('sha256', $persistedPdfPath) === $oldPdfHash,
                    is_file($persistedPdfPath)
                        ? 'hash anterior=' . (string) $oldPdfHash . '; hash atual=' . (string) hash_file('sha256', $persistedPdfPath)
                        : 'PDF anterior removido',
                );
                Assertions::assert(
                    'Erro interno da importação faz rollback de todos os alunos do arquivo',
                    self::countStudents($database, $idTurma, $idEdicao) === $beforeInternalFailure,
                );
            } finally {
                $database->query('DROP TRIGGER IF EXISTS sgi_n07_import_failure');
                $database->close();
                if (is_string($replacementPdfPath) && is_file($replacementPdfPath)) {
                    @unlink($replacementPdfPath);
                }
            }

            $lastUserIdBeforeImport = self::lastUserId($database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test'));
            $database->close();
            $cfile = new CURLFile($pdfPath, 'application/pdf', '6EFB.pdf');
            $resUpload = $admin->postForm('api/v1/importacoes/turma-pdf', [
                'pdf_arquivo' => $cfile,
                'id_turma' => (string) $idTurma,
                'id_interclasse' => (string) $idEdicao
            ]);
            Assertions::assertJsonSuccess("Upload e extração automática de alunos via PDF", $resUpload);
            $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
            $importedStudents = self::studentsCreatedAfter($database, $idTurma, $idEdicao, $lastUserIdBeforeImport);
            Assertions::assert(
                'Importação grava senha compartilhada somente como hash e marca troca obrigatória',
                count($importedStudents) >= 20
                    && count(array_filter($importedStudents, static fn (array $student): bool =>
                        (int) $student['senha_troca_pendente'] === 1
                        && password_verify('sesi-senai', (string) $student['senha_usuario']),
                    )) === count($importedStudents),
            );
            $database->close();

            // 3.3 Listar competidores cadastrados
            $resAlunos = $admin->get("api/v1/usuarios?acao=listar_competidores&id_turma=$idTurma&id_interclasse=$idEdicao");
            Assertions::assertStatus("Listagem de competidores da turma (HTTP 200)", $resAlunos, 200);
            $competidores = $resAlunos['json']['competidores'] ?? [];
            Assertions::assert("Alunos inseridos no banco de dados (esperado >= 20)", count($competidores) >= 20, "Total: " . count($competidores));

            // Validação dos dados do primeiro aluno
            $primeiro = $competidores[0] ?? [];
            Assertions::assert("Aluno possui nome válido", !empty($primeiro['nome_usuario']));
            Assertions::assert("Aluno possui matrícula/RM", !empty($primeiro['matricula_usuario']));
            Assertions::assert("Aluno cadastrado com nível 3 (competidor)", (string)($primeiro['nivel_usuario'] ?? '') === '3');

            $repeated = $admin->postForm('api/v1/importacoes/turma-pdf', [
                'pdf_arquivo' => new CURLFile($pdfPath, 'application/pdf', '6EFB.pdf'),
                'id_turma' => (string) $idTurma,
            ]);
            Assertions::assertJsonSuccess('Reimportação versionada usa a edição da turma', $repeated);
            $legacyView = $admin->postForm('upload_turma_pdf.php', []);
            Assertions::assertStatus('Upload antigo não possui rota', $legacyView, 404);
            $wrongEdition = $admin->postForm('api/v1/importacoes/turma-pdf', [
                'pdf_arquivo' => new CURLFile($pdfPath, 'application/pdf', '6EFB.pdf'),
                'id_turma' => (string) $idTurma,
                'id_interclasse' => (string) ($idEdicao + 999),
            ]);
            Assertions::assert('Upload rejeita edição diferente da turma', ($wrongEdition['json']['success'] ?? true) === false && str_contains($wrongEdition['json']['message'] ?? '', 'não pertence'));
            $invalid = $admin->postForm('api/v1/importacoes/turma-pdf', [
                'pdf_arquivo' => new CURLFile(dirname(__DIR__) . '/fixtures/not-a-pdf.txt', 'application/pdf', 'renomeado.pdf'),
                'id_turma' => (string) $idTurma,
            ]);
            Assertions::assert('Upload verifica conteúdo do arquivo além da extensão', ($invalid['json']['success'] ?? true) === false && str_contains($invalid['json']['message'] ?? '', 'PDF válido'));
            $afterUploads = $admin->get("api/v1/usuarios?acao=listar_competidores&id_turma=$idTurma&id_interclasse=$idEdicao");
            Assertions::assert('Reenvios e arquivos recusados não duplicam nem alteram alunos', ($afterUploads['json']['competidores'] ?? null) === $competidores);
            $anonymous = new TestClient();
            Assertions::assertStatus('Upload versionado exige autenticação', $anonymous->postForm('api/v1/importacoes/turma-pdf', []), 401);
            $mesario = new TestClient();
            $mesario->login('mesario', '123');
            Assertions::assertStatus('Mesário não pode importar alunos', $mesario->postForm('api/v1/importacoes/turma-pdf', []), 403);
            Assertions::assertStatus('Upload versionado exige token CSRF válido', $admin->postForm('api/v1/importacoes/turma-pdf', [], ['X-SGI-CSRF' => '']), 403);
        } else {
            Assertions::assert("Arquivo de PDF disponível para teste", false, "tests/fixtures/6EFB.pdf não localizado");
        }

        return $idTurma;
    }

    private static function countStudents(\mysqli $database, int $classId, int $editionId): int
    {
        $statement = $database->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE nivel_usuario = '3' AND turmas_id_turma = ? AND interclasses_id_interclasse = ?",
        );
        $statement->bind_param('ii', $classId, $editionId);
        $statement->execute();
        $count = (int) ($statement->get_result()->fetch_column() ?: 0);
        $statement->close();

        return $count;
    }

    private static function lastUserId(\mysqli $database): int
    {
        return (int) ($database->query('SELECT COALESCE(MAX(id_usuario), 0) FROM usuarios')->fetch_column() ?: 0);
    }

    /** @return list<array{senha_usuario: string, senha_troca_pendente: int}> */
    private static function studentsCreatedAfter(\mysqli $database, int $classId, int $editionId, int $lastUserId): array
    {
        $statement = $database->prepare(
            "SELECT senha_usuario, senha_troca_pendente
             FROM usuarios
             WHERE nivel_usuario = '3' AND turmas_id_turma = ? AND interclasses_id_interclasse = ? AND id_usuario > ?",
        );
        $statement->bind_param('iii', $classId, $editionId, $lastUserId);
        $statement->execute();
        $students = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $students;
    }
}
