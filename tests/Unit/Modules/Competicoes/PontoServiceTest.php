<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Application\PontoService;
use App\Modules\Competicoes\Domain\PontoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PontoServiceTest extends TestCase
{
    public function testBlocksPointWithoutAthleteWithTheUserFacingMessage(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Selecione o aluno responsável pela jogada para confirmar o ponto.',
        ));

        (new PontoService(new InMemoryPontoRepository()))->registrar([
            'jogos_id_jogo' => 10,
            'id_partida' => 20,
            'equipes_id_equipe' => 30,
            'chave_jogada' => 'regression-no-athlete',
        ], 2);
    }

    public function testRequiresAnAthleteFromTheExactTeamAndIsIdempotent(): void
    {
        $repository = new InMemoryPontoRepository();
        $service = new PontoService($repository);
        $base = [
            'jogos_id_jogo' => 10,
            'id_partida' => 20,
            'equipes_id_equipe' => 30,
            'chave_jogada' => 'regression-idempotent',
        ];

        $this->expectException(InvalidArgumentException::class);
        $service->registrar($base + ['usuarios_id_usuario' => 99], 2);
    }

    public function testRetryWithSameKeyDoesNotInsertAnotherPoint(): void
    {
        $repository = new InMemoryPontoRepository();
        $service = new PontoService($repository);
        $data = [
            'jogos_id_jogo' => 10,
            'id_partida' => 20,
            'equipes_id_equipe' => 30,
            'usuarios_id_usuario' => 42,
            'chave_jogada' => 'regression-idempotent-retry',
        ];

        $first = $service->registrar($data, 2);
        $retry = $service->registrar($data, 2);

        self::assertSame($first, $retry);
        self::assertSame(1, $repository->insertions);
    }

    public function testAnulationChangesScoreStateWithoutDeletingIndividualHistory(): void
    {
        $repository = new InMemoryPontoRepository();
        $service = new PontoService($repository);
        $service->registrar([
            'jogos_id_jogo' => 10,
            'id_partida' => 20,
            'equipes_id_equipe' => 30,
            'usuarios_id_usuario' => 42,
            'chave_jogada' => 'regression-anulation',
        ], 2);
        $result = $service->anular(7, 2);

        self::assertSame('anulado', $result['status_artilheiro']);
        self::assertSame(0, $result['conta_no_placar']);
        self::assertSame(1, $repository->anulations);
        self::assertFalse($repository->deleted);
    }
}

final class InMemoryPontoRepository implements PontoRepository
{
    /** @var array<string, mixed>|null */
    public ?array $point = null;
    public int $insertions = 0;
    public int $anulations = 0;
    public bool $deleted = false;

    public function listarAtletas(int $gameId, int $teamId): array
    {
        return [];
    }

    public function listarPontos(int $gameId, ?int $teamId = null): array
    {
        return $this->point === null ? [] : [$this->point];
    }

    public function contextoPartida(int $gameId, int $partidaId, int $teamId): ?array
    {
        return ['status_jogo' => 'Iniciado', 'individual' => 0];
    }

    public function atletaElegivel(int $userId, int $gameId, int $teamId): bool
    {
        return $userId === 42 && $teamId === 30;
    }

    public function buscarPorChave(string $key): ?array
    {
        return $this->point !== null && $this->point['chave_jogada'] === $key ? $this->point : null;
    }

    public function inserir(array $data): array
    {
        $this->insertions++;
        $this->point = [
            'id_artilheiro' => 7,
            'jogos_id_jogo' => (int) $data['jogos_id_jogo'],
            'partidas_id_partida' => (int) $data['partidas_id_partida'],
            'equipes_id_equipe' => (int) $data['equipes_id_equipe'],
            'usuarios_id_usuario' => (int) $data['usuarios_id_usuario'],
            'chave_jogada' => (string) $data['chave_jogada'],
            'status_artilheiro' => 'ativo',
            'conta_no_placar' => 1,
            'status_jogo' => 'Iniciado',
        ];
        return $this->point;
    }

    public function buscar(int $pointId): ?array
    {
        return $this->point;
    }

    public function anular(int $pointId, int $operatorId): array
    {
        $this->anulations++;
        if ($this->point !== null) {
            $this->point['status_artilheiro'] = 'anulado';
            $this->point['conta_no_placar'] = 0;
            $this->point['resultado_partida'] = 0;
        }
        return $this->point ?? [];
    }

    public function exigeVinculo(int $gameId): bool
    {
        return true;
    }

    public function garantirPartidas(int $gameId, array $results): void
    {
    }

    public function validarPlacarVinculado(int $gameId, array $results): void
    {
    }

    public function persistirPontosOffline(int $gameId, array $points): void
    {
    }
}
