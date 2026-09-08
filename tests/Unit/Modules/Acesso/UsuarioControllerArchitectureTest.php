<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Presentation\Http\UsuarioController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class UsuarioControllerArchitectureTest extends TestCase
{
    public function testControllerDoesNotDependOnMysqliOrEventInfrastructure(): void
    {
        $reflection = new ReflectionClass(UsuarioController::class);
        $parameterTypes = array_map(
            static fn (\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $reflection->getConstructor()?->getParameters() ?? [],
        );

        self::assertNotContains('mysqli', $parameterTypes);

        $source = file_get_contents($reflection->getFileName());
        self::assertIsString($source);
        self::assertStringNotContainsString('Infrastructure\\MysqliEdicaoConsulta', $source);
        self::assertStringNotContainsString('Infrastructure\\MysqliUsuarioGateway', $source);
    }
}
