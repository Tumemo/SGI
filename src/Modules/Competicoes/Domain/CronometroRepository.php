<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

interface CronometroRepository
{
    /**
     * @return array{status_jogo:string,data_jogo?:string|null,inicio_jogo?:string|null,termino_jogo?:string|null,locais_id_local?:int|null,duracao_jogo:int|string|null,tempo_extra_jogo:int|string|null,tempo_restante_jogo:int|string|null,data_inicio_real:int|null,tipos_modalidades_id_tipo_modalidade?:int}|null
     */
    public function findForUpdate(int $gameId): ?array;

    /**
     * @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $snapshot
     */
    public function save(int $gameId, array $snapshot): void;
}
