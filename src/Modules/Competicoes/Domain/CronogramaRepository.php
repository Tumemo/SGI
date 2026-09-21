<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface CronogramaRepository
{
    /** @return array<string,mixed> */
    public function state(int $editionId): array;

    /** @return array<string,mixed> */
    public function prepareTeams(int $editionId, int $userId): array;

    /** @param array<string,mixed> $options @return array<string,mixed> */
    public function generateDraft(int $editionId, int $userId, array $options): array;

    /** @param list<array<string,mixed>> $commitments @param list<array<string,mixed>> $nodes @return array<string,mixed> */
    public function publish(int $editionId, int $userId, int $expectedRevision, array $commitments, array $nodes = []): array;

    public function openRegistrations(int $editionId, int $userId, int $expectedRevision, ?string $opening, ?string $closing): array;

    public function closeRegistrations(int $editionId, int $userId, int $expectedRevision): array;

    /** @return array<string,mixed> */
    public function review(int $editionId, int $userId, int $expectedRevision): array;

    /** @return array<string,mixed> */
    public function materializeNode(int $editionId, int $userId, string $tag): array;

    /** @param list<int> $teamIds @return list<array<string,mixed>> */
    public function commitmentsForTeams(int $editionId, array $teamIds): array;
}
