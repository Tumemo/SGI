<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Application\UsuarioService;
use App\Modules\Eventos\Domain\EdicaoConsulta;
use mysqli;

/**
 * Adaptador legado para consumidores que ainda usam o gateway histórico.
 * As consultas e mutações reais vivem nos repositórios separados.
 */
final class MysqliUsuarioGateway
{
    private readonly MysqliUsuarioConsultaRepository $consultas;

    private readonly UsuarioService $service;

    private readonly EdicaoConsulta $edicoes;

    public function __construct(mysqli $connection)
    {
        $this->consultas = new MysqliUsuarioConsultaRepository($connection);
        $this->edicoes = new \App\Modules\Eventos\Infrastructure\MysqliEdicaoConsultaRepository($connection);
        $this->service = new UsuarioService(
            $this->consultas,
            new MysqliUsuarioManagementRepository($connection),
            new LocalFotoStorage(\App\Shared\Storage\StoragePaths::fotosUsuarios()),
        );
    }

    public function activeEdition(): ?int
    {
        return $this->edicoes->findActiveId();
    }

    /** @return array<string, mixed> */
    public function competitors(int $classId, ?int $editionId = null, string $gender = ''): array
    {
        return $this->consultas->competitors($classId, $editionId ?? $this->activeEdition() ?? 0, $gender);
    }

    /** @return array<string, mixed> */
    public function collaborators(int $editionId): array
    {
        return $this->consultas->collaborators($editionId);
    }

    /** @return array<string, mixed> */
    public function allUsers(int $editionId): array
    {
        return $this->consultas->allUsers($editionId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createStudent(array $data, int $editionId): array
    {
        return $this->service->criarAluno($data, $editionId);
    }

    public function assignStudent(int $userId, int $classId, int $editionId): void
    {
        $this->service->atribuirAluno($userId, $classId, $editionId);
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $photo */
    public function createStaff(array $data, int $editionId, ?array $photo = null): array
    {
        $path = is_array($photo) && is_string($photo['tmp_name'] ?? null) ? $photo['tmp_name'] : null;
        return $this->service->cadastrarColaborador($data, $editionId, $path);
    }

    /** @param array<string, mixed> $data */
    public function updateStaffRole(array $data, int $editionId): void
    {
        $this->service->atualizarColaborador($data, $editionId);
    }

    /** @param array<string, mixed> $data */
    public function updateStaffDetails(array $data, int $editionId): void
    {
        $this->service->atualizarDadosColaborador($data, $editionId);
    }

    /** @param array<string, mixed> $data */
    public function updateStudent(array $data, int $editionId): void
    {
        $this->service->editarAluno($data, $editionId);
    }

    /** @return array<string, mixed>|null */
    public function findCompetitorForValidation(string $registration, string $birth, int $editionId): ?array
    {
        return $this->service->validarInscricao($registration, $birth, $editionId);
    }
}
