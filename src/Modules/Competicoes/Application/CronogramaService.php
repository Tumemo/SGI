<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\CronogramaRepository;
use InvalidArgumentException;

final class CronogramaService
{
    public function __construct(private readonly CronogramaRepository $repository)
    {
    }

    /** @return array<string,mixed> */
    public function estado(int $editionId): array
    {
        if ($editionId <= 0) {
            throw new InvalidArgumentException('O ID da edição é obrigatório.');
        }
        return $this->repository->state($editionId);
    }

    public function ativar(int $editionId, int $userId): array
    {
        if ($editionId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Edição e usuário são obrigatórios.');
        }
        return $this->repository->enablePlanning($editionId, $userId);
    }

    public function preparar(int $editionId, int $userId): array
    {
        if ($editionId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Edição e usuário são obrigatórios.');
        }
        return $this->repository->prepareTeams($editionId, $userId);
    }

    /** @param array<string,mixed> $options @return array<string,mixed> */
    public function gerar(int $editionId, int $userId, array $options): array
    {
        if ($editionId <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('Edição e usuário são obrigatórios.');
        }
        return $this->repository->generateDraft($editionId, $userId, $options);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function publicar(int $editionId, int $userId, array $data): array
    {
        return $this->repository->publish($editionId, $userId, $this->revision($data), $this->commitments($data));
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function abrir(int $editionId, int $userId, array $data): array
    {
        $opening = $this->dateTime($data['inscricoes_abertura'] ?? null);
        $closing = $this->dateTime($data['inscricoes_encerramento'] ?? null);
        if ($opening !== null && $closing !== null && $opening >= $closing) {
            throw new InvalidArgumentException('O encerramento deve ser posterior à abertura.');
        }
        return $this->repository->openRegistrations($editionId, $userId, $this->revision($data), $opening, $closing);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function fechar(int $editionId, int $userId, array $data): array
    {
        return $this->repository->closeRegistrations($editionId, $userId, $this->revision($data));
    }

    private function revision(array $data): int
    {
        $revision = filter_var($data['cronograma_versao'] ?? $data['revisao'] ?? 0, FILTER_VALIDATE_INT);
        if ($revision === false || $revision < 0) {
            throw new InvalidArgumentException('A revisão do cronograma é inválida.');
        }
        return (int) $revision;
    }

    /** @return list<array<string,mixed>> */
    private function commitments(array $data): array
    {
        $items = $data['compromissos'] ?? [];
        if (!is_array($items)) {
            throw new InvalidArgumentException('Os compromissos do cronograma são inválidos.');
        }
        return array_values(array_filter($items, 'is_array'));
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return (new \DateTimeImmutable((string) $value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw new InvalidArgumentException('A data do período de inscrições é inválida.');
        }
    }
}
