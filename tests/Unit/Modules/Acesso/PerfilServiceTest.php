<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\PerfilService;
use App\Modules\Acesso\Domain\PerfilRepository;
use PHPUnit\Framework\TestCase;

final class PerfilServiceTest extends TestCase
{
    public function testWrongCurrentPasswordDoesNotChangeTheProfile(): void
    {
        $repository = $this->createMock(PerfilRepository::class);
        $repository->method('find')->willReturn(['senha_usuario' => password_hash('original', PASSWORD_DEFAULT), 'auth_version' => 1]);
        $repository->expects(self::never())->method('update');
        $this->expectException(\InvalidArgumentException::class);
        (new PerfilService($repository))->update(1, 'Novo nome', 'errada', 'nova-segura');
    }

    public function testNameChangeKeepsTheCurrentPassword(): void
    {
        $repository = $this->createMock(PerfilRepository::class);
        $repository->method('find')->willReturn(['nome_usuario' => 'Antigo']);
        $repository->expects(self::once())->method('update')->with(7, 'Novo nome', null, null)->willReturn(true);
        (new PerfilService($repository))->update(7, ' Novo nome ', '', '');
    }

    public function testPasswordIsHashedBeforePersistence(): void
    {
        $repository = $this->createMock(PerfilRepository::class);
        $repository->method('find')->willReturn(['senha_usuario' => password_hash('original', PASSWORD_DEFAULT), 'auth_version' => 1]);
        $repository->expects(self::once())->method('update')->with(7, 'Nome', self::callback(static fn ($hash) => password_verify('nova-segura', $hash)), 1)->willReturn(true);
        (new PerfilService($repository))->update(7, 'Nome', 'original', 'nova-segura');
    }

    public function testStudentCannotChooseTheSharedInitialPasswordAfterOnboarding(): void
    {
        $repository = $this->createMock(PerfilRepository::class);
        $repository->method('find')->willReturn([
            'nivel_usuario' => '3',
            'senha_troca_pendente' => '0',
            'senha_usuario' => password_hash('senha-pessoal', PASSWORD_DEFAULT),
            'auth_version' => '2',
        ]);
        $repository->expects(self::never())->method('update');
        $this->expectException(\InvalidArgumentException::class);
        (new PerfilService($repository))->update(7, 'Aluno', 'senha-pessoal', 'sesi-senai');
    }

    public function testStaleAuthorizationVersionDoesNotOverwritePassword(): void
    {
        $repository = $this->createMock(PerfilRepository::class);
        $repository->method('find')->willReturn([
            'nivel_usuario' => '0',
            'senha_usuario' => password_hash('original', PASSWORD_DEFAULT),
            'auth_version' => 4,
        ]);
        $repository->expects(self::once())->method('update')->with(
            7,
            'Nome',
            self::callback(static fn ($hash) => password_verify('nova-segura', $hash)),
            4,
        )->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        (new PerfilService($repository))->update(7, 'Nome', 'original', 'nova-segura');
    }
}
