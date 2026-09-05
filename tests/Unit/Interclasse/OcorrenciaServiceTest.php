<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\OcorrenciaService;
use App\Interclasse\Domain\OcorrenciaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OcorrenciaServiceTest extends TestCase
{
    public function testPrefixesGameAndTeamMetadata(): void
    {
        $repository = new InMemoryOcorrenciaRepository();
        $result = (new OcorrenciaService($repository))->registrar([
            'titulo_ocorrencia' => 'Amarelo',
            'descricao_ocorrencia' => 'Cartão',
            'data_ocorrencia' => '2026-09-04',
            'usuarios_id_usuario' => 5,
            'id_jogo' => 9,
            'id_turma' => 3,
        ]);

        self::assertSame(1, $result['id']);
        self::assertSame('[JOGO:9][TURMA:3]Cartão', $repository->created['descricao_ocorrencia']);
    }

    public function testRejectsInvalidPenaltyAndMissingUser(): void
    {
        $service = new OcorrenciaService(new InMemoryOcorrenciaRepository());
        $this->expectException(InvalidArgumentException::class);
        $service->registrar([
            'titulo_ocorrencia' => 'Teste',
            'descricao_ocorrencia' => 'Descrição',
            'data_ocorrencia' => '2026-09-04',
            'usuarios_id_usuario' => 0,
            'penalidade' => -1,
        ]);
    }
}

final class InMemoryOcorrenciaRepository implements OcorrenciaRepository
{
    /** @var array<string, mixed> */
    public array $created = [];

    public function create(array $data): array
    {
        $this->created = $data;
        return ['id' => 1, 'evento' => null];
    }

    public function update(int $id, array $data): bool
    {
        return true;
    }
}
