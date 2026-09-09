<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Web;

use App\Presentation\Web\PageController;
use App\Shared\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PageControllerTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $previousSession = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSession = isset($_SESSION) ? $_SESSION : null;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession ?? [];
        parent::tearDown();
    }

    #[DataProvider('nonAdminLevels')]
    public function testOnlyAdministratorsCanOpenClassStudentManagementPage(int $level): void
    {
        $_SESSION['nivel'] = $level;

        $response = (new PageController())->show(
            new Request('GET', '/turmas/alunos'),
            dirname(__DIR__, 4) . '/resources/views/pages/participantes/turma-alunos.php',
        );

        self::assertSame(302, $response->status());
        self::assertSame($level === 3 ? '/aluno/inicio' : ($level === 2 ? '/painel' : '/edicoes'), $response->headers()['Location'] ?? null);
        self::assertSame('', $response->body());
    }

    /** @dataProvider forbiddenPagesForMesario */
    public function testMesarioCannotOpenAdministrativePages(string $path, string $template): void
    {
        $_SESSION['nivel'] = 2;

        $response = (new PageController())->show(
            new Request('GET', $path),
            dirname(__DIR__, 4) . '/' . $template,
        );

        self::assertSame(302, $response->status(), $path);
        self::assertSame('/painel', $response->headers()['Location'] ?? null, $path);
        self::assertSame('', $response->body(), $path);
    }

    /** @return array<string, array{string, string}> */
    public static function forbiddenPagesForMesario(): array
    {
        return [
            'colaboradores' => ['/colaboradores', 'resources/views/pages/acesso/colaboradores.php'],
            'modalidades' => ['/edicoes/modalidades', 'resources/views/pages/eventos/configurar-modalidades.php'],
            'pontuacao' => ['/edicoes/pontuacao', 'resources/views/pages/eventos/configurar-pontuacao.php'],
            'equipes' => ['/edicoes/equipes', 'resources/views/pages/eventos/configurar-equipes.php'],
            'alunos da turma' => ['/turmas/alunos', 'resources/views/pages/participantes/turma-alunos.php'],
        ];
    }

    /** @return array<string, array{int}> */
    public static function nonAdminLevels(): array
    {
        return [
            'colaborador' => [1],
            'mesario' => [2],
            'competidor' => [3],
        ];
    }
}
