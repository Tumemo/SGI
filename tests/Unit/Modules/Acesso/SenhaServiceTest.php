<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\SenhaService;
use App\Modules\Acesso\Domain\SenhaRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SenhaServiceTest extends TestCase
{
    public function testHashesAndPersistsNewPassword(): void
    {
        $repository = new InMemorySenhaRepository();
        (new SenhaService($repository))->trocar(7, 'senha-segura', 'senha-segura', 'senha-atual', false, 4);

        self::assertNotSame('senha-segura', $repository->hash);
        self::assertTrue(password_verify('senha-segura', (string) $repository->hash));
        self::assertSame(4, $repository->expectedVersion);
        self::assertFalse($repository->initialChange);
    }

    public function testRejectsSharedInitialPasswordAndMismatch(): void
    {
        $service = new SenhaService(new InMemorySenhaRepository());
        $this->expectException(InvalidArgumentException::class);
        $service->trocar(7, 'sesi-senai', 'sesi-senai', 'sesi-senai', true, 1);
    }

    public function testRejectsChangeWithoutCurrentPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SenhaService(new InMemorySenhaRepository()))->trocar(7, 'senha-segura', 'senha-segura');
    }

    public function testAllowsFirstLoginWithoutCurrentPasswordAndConsumesPendingState(): void
    {
        $repository = new InMemorySenhaRepository();
        $service = new SenhaService($repository);

        $service->trocar(7, 'senha-pessoal', 'senha-pessoal', '', true, 8);

        self::assertTrue(password_verify('senha-pessoal', (string) $repository->hash));
        self::assertSame(8, $repository->expectedVersion);
        self::assertTrue($repository->initialChange);
    }

    public function testRejectsNewPasswordThatMatchesCurrentPassword(): void
    {
        $repository = new InMemorySenhaRepository('senha-atual');
        $this->expectException(InvalidArgumentException::class);

        (new SenhaService($repository))->trocar(7, 'senha-atual', 'senha-atual', 'senha-atual', false, 2);
    }

    public function testRejectsAStaleVersionWithoutPersistingTheNewHash(): void
    {
        $repository = new InMemorySenhaRepository();
        $repository->updateAllowed = false;
        $this->expectException(InvalidArgumentException::class);

        (new SenhaService($repository))->trocar(7, 'senha-segura', 'senha-segura', 'senha-atual', false, 9);
    }
}

final class InMemorySenhaRepository implements SenhaRepository
{
    public ?string $hash = null;
    public int $expectedVersion = 0;
    public bool $initialChange = false;
    public bool $updateAllowed = true;

    public function __construct(private readonly string $currentPassword = 'senha-atual')
    {
    }

    public function senhaAtualValida(int $usuarioId, string $senha): bool
    {
        return $senha !== '' && $senha === $this->currentPassword;
    }

    public function alterarSenha(int $usuarioId, string $hash, int $authVersion, bool $trocaInicial): bool
    {
        if (!$this->updateAllowed) {
            return false;
        }
        $this->hash = $hash;
        $this->expectedVersion = $authVersion;
        $this->initialChange = $trocaInicial;
        return $usuarioId === 7;
    }
}
