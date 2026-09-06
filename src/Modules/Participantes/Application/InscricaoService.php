<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Application;

use App\Modules\Participantes\Domain\InscricaoRepository;
use InvalidArgumentException;

final class InscricaoService
{
    public function __construct(private readonly InscricaoRepository $inscricoes)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string,insercoes:int,ja_existentes:int,erros:list<string>}
     */
    public function inscrever(int $userId, array $data): array
    {
        $editionId = (int) ($data['id_interclasse'] ?? 0);
        $teamIds = $data['id_equipes'] ?? [];
        if ($editionId <= 0 || !is_array($teamIds) || $teamIds === []) {
            throw new InvalidArgumentException('id_interclasse e id_equipes são obrigatórios.');
        }
        if (count($teamIds) > 3) {
            throw new InvalidArgumentException('Máximo de 3 modalidades permitidas.');
        }

        $normalised = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $teamIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($normalised === []) {
            throw new InvalidArgumentException('Nenhuma equipe válida informada.');
        }

        return $this->inscricoes->subscribe($userId, $editionId, $normalised);
    }
}
