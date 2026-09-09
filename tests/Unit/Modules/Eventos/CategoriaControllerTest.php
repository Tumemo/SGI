<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Eventos;

use App\Modules\Eventos\Domain\CategoriaRepository;
use App\Modules\Eventos\Application\CategoriaService;
use App\Modules\Eventos\Presentation\Http\CategoriaController;
use App\Shared\Http\Request;
use App\Shared\Http\SessionManager;
use PHPUnit\Framework\TestCase;

final class CategoriaControllerTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $previousSession = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSession = isset($_SESSION) ? $_SESSION : null;
        SessionManager::start();
        $_SESSION = ['nivel' => 0];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession ?? [];
        parent::tearDown();
    }

    public function testMapsDuplicateCategoryToConflictResponse(): void
    {
        $controller = new CategoriaController(new CategoriaService(new ControllerCategoriaRepository()));
        $request = new Request(
            'POST',
            '/api/v1/categorias',
            [],
            [],
            [],
            [],
            [],
            '{"nome_categoria":"Categoria I","interclasses_id_interclasse":10}',
        );

        $response = $controller($request);

        self::assertSame(409, $response->status());
        self::assertStringContainsString('já existe', mb_strtolower($response->body()));
    }
}

final class ControllerCategoriaRepository implements CategoriaRepository
{
    public function listActive(array $filters): array
    {
        return [];
    }

    public function create(array $data): int
    {
        return 2;
    }

    public function find(int $id): ?array
    {
        return [
            'status_categoria' => '1',
            'interclasses_id_interclasse' => 10,
        ];
    }

    public function duplicateExists(int $editionId, string $name, int $exceptId = 0): bool
    {
        return $editionId === 10 && strcasecmp(trim($name), 'Categoria I') === 0 && $exceptId === 0;
    }

    public function update(int $id, array $data): void
    {
    }

    public function deactivateCascade(int $id): void
    {
    }
}
