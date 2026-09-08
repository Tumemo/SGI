<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface CronometroRepository
{
    /**
     * @return array{status_jogo:string,duracao_jogo:int|string|null,tempo_extra_jogo:int|string|null,tempo_restante_jogo:int|string|null,data_inicio_real:int|null}|null
     */
    public function findForUpdate(int $gameId): ?array;

    /**
     * @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $snapshot
     */
    public function save(int $gameId, array $snapshot): void;
}
