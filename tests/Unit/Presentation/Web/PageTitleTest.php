<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Web;

use PHPUnit\Framework\TestCase;

final class PageTitleTest extends TestCase
{
    public function testCanonicalTitleComponentContainsOnlySgi(): void
    {
        $component = file_get_contents($this->path('resources/views/components/page-title.php'));

        self::assertIsString($component);
        self::assertSame('<title>SGI</title>', trim($component));
    }

    public function testAllHtmlHeadsIncludeTheCanonicalTitleComponent(): void
    {
        foreach ([
            'resources/views/components/admin-head.php',
            'resources/views/components/aluno-head.php',
            'resources/views/pages/acesso/login.php',
        ] as $file) {
            $source = file_get_contents($this->path($file));

            self::assertIsString($source, $file);
            self::assertStringContainsString('components/page-title.php', $source, $file);
        }
    }

    public function testPageTemplatesDoNotDeclareTheirOwnTitles(): void
    {
        $files = glob($this->path('resources/views/pages/**/*.php')) ?: [];
        $files[] = $this->path('resources/views/pages/acesso/login.php');

        foreach (array_unique($files) as $file) {
            $source = file_get_contents($file);

            self::assertIsString($source, $file);
            self::assertStringNotContainsString('<title', $source, $file);
            self::assertStringNotContainsString('$tituloPagina', $source, $file);
        }
    }

    public function testClientNavigationKeepsTheCanonicalTitle(): void
    {
        $adminHeader = file_get_contents($this->path('resources/views/components/admin-header.php'));
        $turmaAlunos = file_get_contents($this->path('resources/js/pages/participantes/turma-alunos.js'));
        $offline = file_get_contents($this->path('resources/js/offline/mesario-offline.js'));

        self::assertIsString($adminHeader);
        self::assertIsString($turmaAlunos);
        self::assertIsString($offline);
        self::assertStringContainsString("document.title = 'SGI';", $adminHeader);
        self::assertStringNotContainsString('SGI - Alunos da Turma', $turmaAlunos);
        self::assertStringNotContainsString('document.title = TELA_TITULO', $offline);
        self::assertStringContainsString("document.title = 'SGI';", $offline);
    }

    private function path(string $relative): string
    {
        return dirname(__DIR__, 4) . '/' . $relative;
    }
}
