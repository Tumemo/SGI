<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface ResultadoRepository
{
    /** @return array{game_id:int,modality_id:int,edition_id:int,tag:?string} */
    public function inspectResult(int $gameId, ?string $tag, int $modalityId): array;

    /** @param list<array<string, mixed>> $results */
    public function resolveAndValidate(int $gameId, ?string $tag, int $modalityId, array $results): int;

    /** @return array{status_jogo:string,nome_jogo:string,modalidade_id:int,interclasse_id:int} */
    public function lockGame(int $gameId): array;

    /** @return list<array{equipes_id_equipe:int,resultado_partida:int}> */
    public function carregarPartidas(int $gameId): array;

    /** @param list<array<string, mixed>> $results */
    public function persistirPlacar(int $gameId, array $results): void;

    public function concluirJogo(int $gameId): void;

    public function avancarChaveamento(int $gameId): void;

    public function reconstruirChaveamento(int $modalityId, int $largura): void;
}
