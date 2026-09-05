<?php

declare(strict_types=1);

namespace Tests\Unit\Interclasse;

use App\Interclasse\Application\JogoConflitoException;
use App\Interclasse\Application\JogoService;
use App\Interclasse\Domain\JogoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class JogoServiceTest extends TestCase
{
    public function testNormalizesTimesWhenScheduling(): void
    {
        $repository = new InMemoryJogoRepository();
        $id = (new JogoService($repository))->agendar([
            'nome_jogo' => 'MM:4:0:N',
            'data_jogo' => '2026-09-04',
            'inicio_jogo' => '08:30',
            'termino_jogo' => '09:15',
            'modalidades_id_modalidade' => 2,
            'locais_id_local' => 3,
        ]);

        self::assertSame(1, $id);
        self::assertSame('08:30:00', $repository->created['inicio_jogo']);
        self::assertSame('09:15:00', $repository->created['termino_jogo']);
        self::assertSame('Agendado', $repository->created['status_jogo']);
    }

    public function testRejectsSchedulingConflict(): void
    {
        $repository = new InMemoryJogoRepository();
        $repository->conflict = 'Já existe conflito.';
        $this->expectException(JogoConflitoException::class);
        (new JogoService($repository))->agendar([
            'nome_jogo' => 'Jogo',
            'data_jogo' => '2026-09-04',
            'modalidades_id_modalidade' => 2,
            'locais_id_local' => 3,
        ]);
    }

    public function testRejectsIncompleteScheduling(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new JogoService(new InMemoryJogoRepository()))->agendar(['nome_jogo' => 'Jogo']);
    }
}

final class InMemoryJogoRepository implements JogoRepository
{
    /** @var array<string, mixed> */
    public array $created = [];

    public ?string $conflict = null;

    public function localConflict(string $date, int $localId, string $start, string $end, ?int $currentId = null): ?string
    {
        return $this->conflict;
    }

    public function create(array $data): int
    {
        $this->created = $data;
        return 1;
    }
}
