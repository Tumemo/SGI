<?php

declare(strict_types=1);

namespace Tests\Unit\Autenticacao;

use App\Modules\Acesso\Application\SenhaService;
use App\Modules\Acesso\Domain\SenhaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SenhaServiceTest extends TestCase
{
    public function testHashesAndPersistsNewPassword(): void
    {
        $repository = new InMemorySenhaRepository();
        (new SenhaService($repository))->trocar(7, 'senha-segura', 'senha-segura');

        self::assertNotSame('senha-segura', $repository->hash);
        self::assertTrue(password_verify('senha-segura', (string) $repository->hash));
    }

    public function testRejectsDefaultPasswordAndMismatch(): void
    {
        $service = new SenhaService(new InMemorySenhaRepository());
        $this->expectException(InvalidArgumentException::class);
        $service->trocar(7, '123', '123');
    }
}

final class InMemorySenhaRepository implements SenhaRepository
{
    public ?string $hash = null;

    public function alterarSenha(int $usuarioId, string $hash): bool
    {
        $this->hash = $hash;
        return true;
    }
}
