<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface CronogramaRepository
{
    /** @return array<string,mixed> */
    public function state(int $editionId): array;

    public function enablePlanning(int $editionId, int $userId): array;

    /** @return array<string,mixed> */
    public function prepareTeams(int $editionId, int $userId): array;

    /** @param array<string,mixed> $options @return array<string,mixed> */
    public function generateDraft(int $editionId, int $userId, array $options): array;

    /** @param list<array<string,mixed>> $commitments @return array<string,mixed> */
    public function publish(int $editionId, int $userId, int $expectedRevision, array $commitments): array;

    public function openRegistrations(int $editionId, int $userId, int $expectedRevision, ?string $opening, ?string $closing): array;

    public function closeRegistrations(int $editionId, int $userId, int $expectedRevision): array;

    /** @param list<int> $teamIds @return list<array<string,mixed>> */
    public function commitmentsForTeams(int $editionId, array $teamIds): array;
}
