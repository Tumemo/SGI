<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Acesso;

use App\Modules\Acesso\Application\FotoService;
use App\Modules\Acesso\Domain\FotoStorage;
use App\Modules\Acesso\Domain\PerfilRepository;
use PHPUnit\Framework\TestCase;

final class FotoServiceTest extends TestCase
{
    public function testDatabaseFailureRemovesTheNewFileAndPreservesPreviousPhoto(): void
    {
        $profiles = $this->createMock(PerfilRepository::class);
        $profiles->method('find')->willReturn(['foto_usuario' => 'old.png']);
        $profiles->method('setPhoto')->willThrowException(new \RuntimeException('database unavailable'));
        $storage = $this->createMock(FotoStorage::class);
        $storage->method('save')->willReturn('new.png');
        $storage->expects(self::once())->method('remove')->with('new.png');
        $this->expectException(\RuntimeException::class);
        (new FotoService($profiles, $storage))->replace(1, '/tmp/upload');
    }

    public function testRemovalStoresAnEmptyFilenameBeforeRemovingTheFile(): void
    {
        $profiles = $this->createMock(PerfilRepository::class);
        $profiles->method('find')->willReturn(['foto_usuario' => 'old.png']);
        $profiles->expects(self::once())->method('setPhoto')->with(1, '');
        $storage = $this->createMock(FotoStorage::class);
        $storage->expects(self::once())->method('remove')->with('old.png');
        (new FotoService($profiles, $storage))->remove(1);
    }
}
