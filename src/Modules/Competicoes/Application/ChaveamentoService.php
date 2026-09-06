<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\ChaveamentoManagement;
use InvalidArgumentException;

final class ChaveamentoService
{
    public function __construct(private readonly ChaveamentoManagement $repository)
    {
    }

    public function edition(int $id): ?int
    {
        $modality = $this->repository->modality($id);
        return isset($modality['interclasses_id_interclasse']) ? (int) $modality['interclasses_id_interclasse'] : null;
    }

    /** @return array<mixed> */
    public function consultar(int $id, bool $individual, string $action): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID da modalidade é obrigatório.');
        }
        if (!$individual && $action === 'classificacao') {
            $result = $this->repository->read($id, false, 'historico');
            unset($result['confrontos']);
            return $result;
        }
        return $this->repository->read($id, $individual, $action);
    }

    /** @param array<string, mixed>|null $ranking
     * @return array<string, mixed>
     */
    public function gerar(int $id, bool $individual, ?array $ranking): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Informe o ID da modalidade.');
        }
        if ($individual) {
            $podium = isset($ranking['primeiro'], $ranking['segundo'], $ranking['terceiro'])
                ? ['primeiro' => (int) $ranking['primeiro'], 'segundo' => (int) $ranking['segundo'], 'terceiro' => (int) $ranking['terceiro']]
                : null;
            return $this->repository->saveIndividual($id, $podium);
        }
        $modality = $this->repository->modality($id);
        if ((int) ($modality['tipos_modalidades_id_tipo_modalidade'] ?? 0) === 2) {
            return $this->repository->saveIndividual($id, null);
        }
        return $this->repository->createBracket($id);
    }
}
