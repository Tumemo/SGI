<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\AcessoEdicaoNegadoException;
use App\Modules\Acesso\Application\EdicaoAccessPolicy;
use App\Modules\Acesso\Domain\ContextoOperador;
use App\Modules\Acesso\Domain\InterclasseRepository;
use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Shared\Http\SessionManager;
use PHPUnit\Framework\TestCase;

final class EdicaoAccessPolicyTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $previousSession = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSession = isset($_SESSION) ? $_SESSION : null;
        SessionManager::start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession ?? [];
        parent::tearDown();
    }

    public function testAdministratorAndCollaboratorCanAccessAnyExistingEdition(): void
    {
        $policy = new EdicaoAccessPolicy();

        $policy->assertAllowed(new ContextoOperador(10, 0, null), 11);
        $policy->assertAllowed(new ContextoOperador(20, 1, null), 22);

        self::assertTrue($policy->allows(new ContextoOperador(10, 0, null), 11));
    }

    public function testMesarioCanAccessOnlyTheEditionCapturedAsActive(): void
    {
        $policy = new EdicaoAccessPolicy();
        $operator = new ContextoOperador(30, 2, 11);

        $policy->assertAllowed($operator, 11);

        $this->expectException(AcessoEdicaoNegadoException::class);
        $policy->assertAllowed($operator, 22);
    }

    public function testMesarioIsDeniedWhenActiveEditionIsMissingOrStale(): void
    {
        $policy = new EdicaoAccessPolicy();

        self::assertFalse($policy->allows(new ContextoOperador(30, 2, null), 11));
        self::assertFalse($policy->allows(new ContextoOperador(30, 2, 0), 11));
        self::assertFalse($policy->allows(new ContextoOperador(30, 2, 22), 11));
    }

    public function testOperationalFlowRejectsOtherProfiles(): void
    {
        $policy = new EdicaoAccessPolicy();

        foreach ([3, -1] as $level) {
            self::assertFalse($policy->allows(new ContextoOperador(40, $level, 11), 11));
        }
    }

    public function testMissingOrNonPositiveResourceEditionIsNeverAuthorized(): void
    {
        $policy = new EdicaoAccessPolicy();

        foreach ([null, 0, -1] as $editionId) {
            self::assertFalse($policy->allows(new ContextoOperador(10, 0, null), $editionId));
            self::assertFalse($policy->allows(new ContextoOperador(30, 2, 0), $editionId));
        }
    }

    public function testHttpAdapterBuildsContextFromAuthenticatedSessionAndDatabase(): void
    {
        $_SESSION = [
            'id_usuario' => 42,
            'nivel' => 2,
            'id_interclasse' => 999,
        ];
        $access = new CompetitionAccess($this->repositoryReturning(11));

        $context = $access->context();

        self::assertSame(42, $context->userId);
        self::assertSame(2, $context->nivel);
        self::assertSame(11, $context->edicaoAtivaId);
        self::assertSame(11, $_SESSION['id_interclasse']);
    }

    public function testHttpAdapterAuthorizesHistoricalEditionForAdminAndDeniesStaleMesario(): void
    {
        $_SESSION = ['id_usuario' => 7, 'nivel' => 0];
        $adminAccess = new CompetitionAccess($this->repositoryReturning(22));
        self::assertNull($adminAccess->authorize(11));

        $_SESSION = ['id_usuario' => 8, 'nivel' => 2];
        $mesarioAccess = new CompetitionAccess($this->repositoryReturning(22));
        $response = $mesarioAccess->authorize(11);

        self::assertNotNull($response);
        self::assertSame(403, $response->status());
    }

    public function testHttpAdapterRejectsMesarioWhenThereIsNoActiveEdition(): void
    {
        $_SESSION = ['id_usuario' => 9, 'nivel' => 2];
        $response = (new CompetitionAccess($this->repositoryReturning(null)))->authorize();

        self::assertNotNull($response);
        self::assertSame(403, $response->status());
    }

    private function repositoryReturning(?int $editionId): InterclasseRepository
    {
        return new class ($editionId) implements InterclasseRepository {
            public function __construct(private readonly ?int $editionId)
            {
            }

            public function findActiveId(): ?int
            {
                return $this->editionId;
            }
        };
    }
}
