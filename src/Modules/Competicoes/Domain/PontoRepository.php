<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface PontoRepository
{
    /** @return list<array<string, mixed>> */
    public function listarAtletas(int $gameId, int $teamId): array;

    /** @return list<array<string, mixed>> */
    public function listarPontos(int $gameId, ?int $teamId = null): array;

    /** @return array<string, mixed>|null */
    public function contextoPartida(int $gameId, int $partidaId, int $teamId): ?array;

    public function atletaElegivel(int $userId, int $gameId, int $teamId): bool;

    /** @return array<string, mixed>|null */
    public function buscarPorChave(string $key): ?array;

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function inserir(array $data): array;

    /** @return array<string, mixed>|null */
    public function buscar(int $pointId): ?array;

    /** @return array<string, mixed> */
    public function anular(int $pointId, int $operatorId): array;

    public function exigeVinculo(int $gameId): bool;

    /** @param list<array<string, mixed>> $results */
    public function garantirPartidas(int $gameId, array $results): void;

    /** @param list<array<string, mixed>> $results */
    public function validarPlacarVinculado(int $gameId, array $results): void;

    /** @param list<array<string, mixed>> $points */
    public function persistirPontosOffline(int $gameId, array $points): void;
}
