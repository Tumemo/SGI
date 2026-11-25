<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use Throwable;

final class DompdfRelatorioFinalRenderer
{
    public function __construct(private readonly string $projectRoot)
    {
    }

    /** @param array<string, mixed> $report */
    public function render(array $report): string
    {
        $publicRoot = $this->projectRoot . DIRECTORY_SEPARATOR . 'public';
        $stylesheets = [
            $publicRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'bootstrap-theme.css',
            $publicRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'shared.css',
            $publicRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin.css',
        ];
        foreach ($stylesheets as $stylesheet) {
            if (!is_file($stylesheet)) {
                throw new RuntimeException('Os estilos do relatório não estão compilados. Gere os assets antes de exportar o PDF.');
            }
        }

        $templatePath = $this->projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . 'relatorio-final-pdf.php';
        if (!is_file($templatePath)) {
            throw new RuntimeException('O modelo do relatório PDF não está disponível.');
        }

        $links = array_map(self::fileUri(...), $stylesheets);
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $template, array $report, array $stylesheetUris): void {
                require $template;
            })($templatePath, $report, $links);
            $html = (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $exception;
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', [$publicRoot]);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultMediaType', 'print');
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        return $dompdf->output();
    }

    private static function fileUri(string $path): string
    {
        $realPath = realpath($path);
        if ($realPath === false) {
            throw new RuntimeException('Não foi possível localizar os estilos do relatório.');
        }
        $normalised = str_replace('\\', '/', $realPath);
        return 'file://' . $normalised;
    }
}
