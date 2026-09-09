<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Modules\Acesso\Presentation\Http\UsuarioController;
use App\Modules\Competicoes\Presentation\Http\ResultadoController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PresentationBoundaryTest extends TestCase
{
    public function testControllersDoNotExecuteSql(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src'));
        $count = 0;
        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Controller.php')) {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:prepare|execute|begin_transaction|commit|rollback)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/[\'\"]\s*(?:SELECT\s|INSERT\s+INTO\s|UPDATE\s+\w+\s+SET\s|DELETE\s+FROM\s)/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(10, $count);
    }

    public function testAllPresentationHelpersStayAtTheHttpBoundary(): void
    {
        $root = dirname(__DIR__, 3) . '/src/Modules';
        $count = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!$file->isFile() || $file->getExtension() !== 'php' || !str_contains($path, '/Presentation/')) {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:prepare|execute|begin_transaction|commit|rollback)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/[\'\"]\s*(?:SELECT\s|INSERT\s+INTO\s|UPDATE\s+\w+\s+SET\s|DELETE\s+FROM\s)/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(10, $count);
    }

    public function testViewsContainNeitherDatabaseQueriesNorInlinePagePrograms(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/resources/views/pages'));
        $count = 0;
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $count++;
            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/->(?:query|prepare|execute)\s*\(|\bnew\s+mysqli\b/i', $source, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/<script\s*>/i', $source, $file->getPathname());
        }
        self::assertGreaterThan(20, $count);
    }

    public function testCanonicalPageUrlsResolveOnlyToExistingPrivateTemplates(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = require $root . '/config/routes/web.php';
        foreach ($routes as $url => $template) {
            self::assertStringStartsWith('/', $url);
            self::assertStringNotContainsString('/views/', $url);
            self::assertStringStartsWith('resources/views/pages/', $template);
            self::assertFileExists($root . '/' . $template);
        }
    }

    public function testCriticalMigratedControllersDoNotReceiveConcreteInfrastructure(): void
    {
        foreach ([ResultadoController::class, UsuarioController::class] as $controller) {
            $reflection = new ReflectionClass($controller);
            foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
                $type = (string) $parameter->getType();
                self::assertNotSame('mysqli', $type, $controller . ' recebe mysqli diretamente.');
                self::assertStringNotContainsString('Infrastructure\\', $type, $controller . ' recebe infraestrutura concreta.');
            }

            $source = file_get_contents($reflection->getFileName());
            self::assertIsString($source);
            self::assertStringNotContainsString('new mysqli', $source, $controller);
            foreach (self::imports($source) as $import) {
                self::assertDoesNotMatchRegularExpression('~^App\\\\Modules\\\\[^\\\\]+\\\\Infrastructure\\\\~', $import, $controller);
            }
        }
    }

    public function testCriticalRoutesComposeTheMigratedUseCases(): void
    {
        $root = dirname(__DIR__, 3);
        $router = require $root . '/config/routes.php';
        $property = new \ReflectionProperty($router, 'routes');
        $property->setAccessible(true);
        $routes = $property->getValue($router);
        self::assertIsArray($routes);

        $patterns = array_map(static fn (array $route): string => (string) $route['pattern'], $routes);
        self::assertContains('/api/v1/resultados', $patterns);
        self::assertContains('/api/v1/usuarios', $patterns);

        $source = (string) file_get_contents($root . '/config/routes.php');
        $resultRoute = self::routeSource($source, "/api/v1/resultados");
        $userRoute = self::routeSource($source, "/api/v1/usuarios");
        foreach ([
            'ResultadoController',
            'ResultadoService',
            'MysqliPartidaGateway',
            'MysqliTransactionRunner',
            'PontuacaoService',
            'MutationAction',
        ] as $symbol) {
            self::assertStringContainsString($symbol, $resultRoute, $symbol . ' não está composto na rota de resultados.');
        }
        foreach ([
            'UsuarioController',
            'UsuarioService',
            'UsuarioAdministrativoService',
            'MysqliUsuarioConsultaRepository',
            'MysqliUsuarioManagementRepository',
            'MysqliEdicaoConsultaRepository',
        ] as $symbol) {
            self::assertStringContainsString($symbol, $userRoute, $symbol . ' não está composto na rota de usuários.');
        }
    }

    /** @return list<string> */
    private static function imports(string $source): array
    {
        $tokens = token_get_all($source);
        $imports = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_USE) {
                continue;
            }
            $next = self::nextToken($tokens, $index + 1);
            if (is_array($next) && $next[0] === T_VARIABLE) {
                continue;
            }
            $statement = '';
            for ($cursor = $index + 1; isset($tokens[$cursor]); $cursor++) {
                $part = $tokens[$cursor];
                $statement .= is_array($part) ? $part[1] : $part;
                if ($part === ';') {
                    break;
                }
            }
            $statement = trim($statement, " \t\r\n;");
            $statement = preg_replace('/\s+as\s+[A-Za-z_][A-Za-z0-9_]*$/i', '', $statement) ?? $statement;
            if ($statement !== '' && !str_starts_with($statement, 'function ')) {
                $imports[] = $statement;
            }
        }

        return $imports;
    }

    /** @param list<array<int, mixed>|string> $tokens */
    private static function nextToken(array $tokens, int $start): array|string|null
    {
        for ($index = $start; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $token;
        }

        return null;
    }

    private static function routeSource(string $source, string $path): string
    {
        $start = strpos($source, "'" . $path . "'");
        self::assertNotFalse($start, 'Rota ausente: ' . $path);
        $end = strpos($source, '$router->', $start + strlen($path) + 2);
        self::assertNotFalse($end, 'Fim da rota ausente: ' . $path);

        return substr($source, $start, $end - $start);
    }
}
