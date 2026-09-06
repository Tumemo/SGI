<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Application;

use App\Modules\Disciplina\Domain\OcorrenciaTurmaRepository;
use InvalidArgumentException;

final class OcorrenciaTurmaService
{
    public function __construct(private readonly OcorrenciaTurmaRepository $ocorrencias)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->ocorrencias->list($filters);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function registrar(array $data): int
    {
        $teamId = (int) ($data['turmas_id_turma'] ?? 0);
        $interclasseId = (int) ($data['interclasses_id_interclasse'] ?? 0);
        $title = trim((string) ($data['titulo_ocorrencia'] ?? ''));
        $date = trim((string) ($data['data_ocorrencia'] ?? ''));
        if ($teamId <= 0 || $interclasseId <= 0 || $title === '' || $date === '') {
            throw new InvalidArgumentException('Dados incompletos.');
        }
        $userId = $data['usuarios_id_usuario'] ?? null;
        $userId = $userId === null || $userId === '' ? null : (int) $userId;
        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('Usuário da ocorrência é inválido.');
        }
        return $this->ocorrencias->create([
            'turmas_id_turma' => $teamId,
            'interclasses_id_interclasse' => $interclasseId,
            'titulo_ocorrencia' => $title,
            'descricao_ocorrencia' => trim((string) ($data['descricao_ocorrencia'] ?? '')),
            'pontos_descontados' => (int) ($data['pontos_descontados'] ?? 0),
            'data_ocorrencia' => $date,
            'usuarios_id_usuario' => $userId,
        ]);
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID obrigatório.');
        }
        if (!$this->ocorrencias->delete($id)) {
            throw new OcorrenciaTurmaNaoEncontradaException();
        }
    }
}
