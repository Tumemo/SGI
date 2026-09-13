<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Disciplina;

use App\Modules\Disciplina\Application\OcorrenciaService;
use App\Modules\Disciplina\Domain\OcorrenciaRepository;
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

    public function testPrefixesTeamMetadataWhenThereIsNoGame(): void
    {
        $repository = new InMemoryOcorrenciaRepository();
        (new OcorrenciaService($repository))->registrar([
            'titulo_ocorrencia' => 'Conduta',
            'descricao_ocorrencia' => 'Registro sem jogo',
            'data_ocorrencia' => '2026-09-04',
            'usuarios_id_usuario' => 5,
            'id_turma' => 3,
        ]);

        self::assertSame('[TURMA:3]Registro sem jogo', $repository->created['descricao_ocorrencia']);
    }

    public function testRejectsMetadataMarkersInFreeTextOnCreate(): void
    {
        $service = new OcorrenciaService(new InMemoryOcorrenciaRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->registrar([
            'titulo_ocorrencia' => 'Amarelo',
            'descricao_ocorrencia' => '[JOGO:19][TURMA:7]Tentativa textual',
            'data_ocorrencia' => '2026-09-04',
            'usuarios_id_usuario' => 5,
            'id_jogo' => 9,
            'id_turma' => 3,
        ]);
    }

    public function testUpdatePreservesStoredReferencesAndRejectsTextualReplacement(): void
    {
        $repository = new InMemoryOcorrenciaRepository();
        $repository->found = ['descricao_ocorrencia' => '[JOGO:9][TURMA:3]Original'];
        $service = new OcorrenciaService($repository);

        self::assertTrue($service->atualizar([
            'id_ocorrencia' => 1,
            'descricao_ocorrencia' => 'Texto editado',
        ]));
        self::assertSame('[JOGO:9][TURMA:3]Texto editado', $repository->updated['descricao_ocorrencia']);

        try {
            $service->atualizar([
                'id_ocorrencia' => 1,
                'descricao_ocorrencia' => '[JOGO:10][TURMA:4]Troca',
            ]);
            self::fail('A substituição textual de referências deveria ser rejeitada.');
        } catch (InvalidArgumentException) {
            self::assertSame('[JOGO:9][TURMA:3]Texto editado', $repository->updated['descricao_ocorrencia']);
        }
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

    public function testRejectsNegativePenaltyForValidUser(): void
    {
        $service = new OcorrenciaService(new InMemoryOcorrenciaRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->registrar([
            'titulo_ocorrencia' => 'Teste',
            'descricao_ocorrencia' => 'Descrição',
            'data_ocorrencia' => '2026-09-04',
            'usuarios_id_usuario' => 5,
            'penalidade' => -1,
        ]);
    }
}

final class InMemoryOcorrenciaRepository implements OcorrenciaRepository
{
    /** @var array<string, mixed> */
    public array $created = [];
    /** @var array<string, mixed> */
    public array $updated = [];
    /** @var array<string, mixed>|null */
    public ?array $found = null;

    public function create(array $data): array
    {
        $this->created = $data;
        return ['id' => 1, 'evento' => null];
    }

    public function update(int $id, array $data): bool
    {
        $this->updated = $data;
        return true;
    }

    public function find(int $id): ?array
    {
        return $this->found;
    }

    public function editionOfUser(int $userId): ?int
    {
        return 1;
    }

    public function roleOfUser(int $userId): ?int
    {
        return 3;
    }

    public function editionOfGame(int $gameId): ?int
    {
        return 1;
    }

    public function editionOfTurma(int $turmaId): ?int
    {
        return 1;
    }

    public function gameContainsTurma(int $gameId, int $turmaId): bool
    {
        return true;
    }

    public function userBelongsToTurma(int $userId, int $turmaId): bool
    {
        return true;
    }

    public function userParticipatesInGame(int $userId, int $gameId): bool
    {
        return true;
    }
}
