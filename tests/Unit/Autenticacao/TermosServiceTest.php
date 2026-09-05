<?php

declare(strict_types=1);

namespace Tests\Unit\Autenticacao;

use App\Modules\Acesso\Application\TermosService;
use App\Modules\Acesso\Domain\TermosRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TermosServiceTest extends TestCase
{
    public function testReportsAgreementAndDefaultPasswordRequirement(): void
    {
        $service = new TermosService(new InMemoryTermosRepository([
            'nivel_usuario' => '3',
            'senha_usuario' => password_hash('123', PASSWORD_DEFAULT),
            'aceito_termo' => 'sim',
            'interclasses_id_interclasse' => 4,
        ]));

        self::assertSame([
            'termo_aceito' => true,
            'exige_troca_senha' => true,
        ], $service->consultar(9));
    }

    public function testAssignsActiveEditionBeforeAccepting(): void
    {
        $repository = new InMemoryTermosRepository([
            'nivel_usuario' => '3',
            'senha_usuario' => password_hash('outra', PASSWORD_DEFAULT),
            'aceito_termo' => null,
            'interclasses_id_interclasse' => null,
        ]);

        $result = (new TermosService($repository))->aceitar(9);

        self::assertSame('accepted', $result['status']);
        self::assertSame(12, $repository->assignedEdition);
        self::assertSame(12, $repository->acceptedEdition);
    }

    public function testRequiresAuthenticatedUser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TermosService(new InMemoryTermosRepository()))->consultar(0);
    }
}

final class InMemoryTermosRepository implements TermosRepository
{
    /** @var array<string, mixed>|null */
    private ?array $user;
    public ?int $assignedEdition = null;
    public ?int $acceptedEdition = null;

    /** @param array<string, mixed>|null $user */
    public function __construct(?array $user = null)
    {
        $this->user = $user;
    }

    public function findUser(int $userId): ?array
    {
        return $this->user;
    }

    public function findActiveEdition(): ?int
    {
        return 12;
    }

    public function assignEdition(int $userId, int $interclasseId): void
    {
        $this->assignedEdition = $interclasseId;
    }

    public function accept(int $userId, int $interclasseId, string $dateTime): void
    {
        $this->acceptedEdition = $interclasseId;
    }
}
