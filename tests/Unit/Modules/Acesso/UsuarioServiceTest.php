<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\UsuarioService;
use App\Modules\Acesso\Domain\FotoStorage;
use App\Modules\Acesso\Domain\UsuarioConsultaRepository;
use App\Modules\Acesso\Domain\UsuarioManagementRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UsuarioServiceTest extends TestCase
{
    public function testEnrollmentValidationNormalizesInputBeforeQuery(): void
    {
        $queries = new InMemoryUsuarioConsultaRepository();
        $queries->validated = ['id_usuario' => 9, 'nivel_usuario' => '3'];
        $service = new UsuarioService($queries, new InMemoryUsuarioManagementRepository(), new InMemoryFotoStorage());

        self::assertSame($queries->validated, $service->validarInscricao('RM-009', '04/09/2010', 7));
        self::assertSame(['009', '2010-09-04', 7], $queries->validationArguments);
    }

    public function testInvalidEnrollmentNeverReachesRepository(): void
    {
        $queries = new InMemoryUsuarioConsultaRepository();
        $service = new UsuarioService($queries, new InMemoryUsuarioManagementRepository(), new InMemoryFotoStorage());

        self::assertNull($service->validarInscricao('RM-009', '31/02/2010', 7));
        self::assertNull($queries->validationArguments);
    }

    public function testStudentAndStaffInputsAreTranslatedBeforePersistence(): void
    {
        $management = new InMemoryUsuarioManagementRepository();
        $storage = new InMemoryFotoStorage();
        $service = new UsuarioService(new InMemoryUsuarioConsultaRepository(), $management, $storage);

        $service->criarAluno([
            'nome_usuario' => '  Aluno  ',
            'matricula_usuario' => 'RM-123',
            'data_nasc_usuario' => '04/09/2010',
            'turmas_id_turma' => 2,
            'genero_usuario' => 'invalid',
        ], 7);
        $service->cadastrarColaborador([
            'nome_usuario' => 'Colaborador',
            'matricula_usuario' => 'COL-9',
            'senha_usuario' => 'secret',
            'data_nasc_usuario' => '2010-09-04',
        ], 7, 'temporary.jpg');

        self::assertSame('123', $management->student['matricula_usuario']);
        self::assertSame('2010-09-04', $management->student['data_nasc_usuario']);
        self::assertSame('MASC', $management->student['genero_usuario']);
        self::assertSame('9', $management->staff['matricula_usuario']);
        self::assertSame('stored.jpg', $management->staffPhoto);
        self::assertSame('temporary.jpg', $storage->savedPath);
    }

    public function testInvalidStudentDoesNotWrite(): void
    {
        $management = new InMemoryUsuarioManagementRepository();
        $service = new UsuarioService(new InMemoryUsuarioConsultaRepository(), $management, new InMemoryFotoStorage());

        $this->expectException(RuntimeException::class);
        $service->criarAluno(['nome_usuario' => 'Aluno'], 7);
        self::assertNull($management->student);
    }
}

final class InMemoryUsuarioConsultaRepository implements UsuarioConsultaRepository
{
    /** @var array<string, mixed>|null */
    public ?array $validated = null;

    /** @var list<mixed>|null */
    public ?array $validationArguments = null;

    public function competitors(int $classId, int $editionId, string $gender = '', bool $includeSensitive = true): array
    {
        return [];
    }

    public function collaborators(int $editionId): array
    {
        return [];
    }

    public function allUsers(int $editionId): array
    {
        return [];
    }

    public function findCompetitorForValidation(string $registration, string $birth, int $editionId): ?array
    {
        $this->validationArguments = [$registration, $birth, $editionId];
        return $this->validated;
    }
}

final class InMemoryUsuarioManagementRepository implements UsuarioManagementRepository
{
    /** @var array<string, mixed>|null */
    public ?array $student = null;

    /** @var array<string, mixed>|null */
    public ?array $staff = null;

    public ?string $staffPhoto = null;

    public function createStudent(array $data, int $editionId): array
    {
        $this->student = $data;
        return ['status' => 'sucesso'];
    }

    public function assignStudent(int $userId, int $classId, int $editionId): void
    {
    }

    public function createStaff(array $data, int $editionId, string $photoFilename = 'default.jpg'): array
    {
        $this->staff = $data;
        $this->staffPhoto = $photoFilename;
        return ['status' => 'sucesso'];
    }

    public function updateStaffRole(array $data, int $editionId): void
    {
    }

    public function updateStaffDetails(array $data, int $editionId): void
    {
    }

    public function updateStudent(array $data, int $editionId): void
    {
    }
}

final class InMemoryFotoStorage implements FotoStorage
{
    public ?string $savedPath = null;

    public function save(string $temporaryPath): string
    {
        $this->savedPath = $temporaryPath;
        return 'stored.jpg';
    }

    public function remove(string $filename): void
    {
    }
}
