<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Resultados;

use App\Modules\Resultados\Application\ClassificacaoService;
use App\Modules\Resultados\Domain\ClassificacaoRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClassificacaoServiceTest extends TestCase
{
    public function testReturnsPodiumForModality(): void
    {
        $service = new ClassificacaoService(new InMemoryClassificacaoRepository());
        self::assertSame([
            'modalidade_id' => 8,
            'podio' => [['posicao' => 1, 'status' => 'Campeão']],
        ], $service->gerar(8));
    }

    public function testRejectsInvalidModality(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ClassificacaoService(new InMemoryClassificacaoRepository()))->gerar(0);
    }
}

final class InMemoryClassificacaoRepository implements ClassificacaoRepository
{
    public function podium(int $modalityId): array
    {
        return [['posicao' => 1, 'status' => 'Campeão']];
    }
}
