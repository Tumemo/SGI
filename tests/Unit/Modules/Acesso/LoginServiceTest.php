<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\LoginService;
use App\Modules\Acesso\Domain\InterclasseRepository;
use App\Modules\Acesso\Domain\UsuarioRepository;
use PHPUnit\Framework\TestCase;

final class LoginServiceTest extends TestCase
{
    public function testPrioritizesTheActiveEditionForCompetitors(): void
    {
        $interclasses = new class () implements InterclasseRepository {
            public function findActiveId(): ?int
            {
                return 42;
            }
        };

        $users = new class () implements UsuarioRepository {
            public ?int $receivedEdition = null;

            public function findActiveByMatricula(string $matricula, ?int $activeInterclasseId): ?array
            {
                $this->receivedEdition = $activeInterclasseId;

                return [
                    'id_usuario' => 7,
                    'nivel_usuario' => '3',
                    'senha_usuario' => password_hash('123', PASSWORD_DEFAULT),
                ];
            }
        };

        $result = (new LoginService($users, $interclasses))->autenticar('2879', '123');

        self::assertNotNull($result);
        self::assertSame(42, $users->receivedEdition);
        self::assertTrue($result['exige_troca_senha']);
    }

    public function testRejectsAnInvalidPasswordWithoutLeakingUserExistence(): void
    {
        $interclasses = new class () implements InterclasseRepository {
            public function findActiveId(): ?int
            {
                return null;
            }
        };

        $users = new class () implements UsuarioRepository {
            public function findActiveByMatricula(string $matricula, ?int $activeInterclasseId): ?array
            {
                return [
                    'nivel_usuario' => '0',
                    'senha_usuario' => password_hash('correct-password', PASSWORD_DEFAULT),
                ];
            }
        };

        $result = (new LoginService($users, $interclasses))->autenticar('admin', 'wrong-password');

        self::assertNull($result);
    }
}
