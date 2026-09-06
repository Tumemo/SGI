<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Presentation\Web\PageController;
use App\Shared\Config\Env;

final class Kernel
{
    /** @param array<string,string> $webRoutes @param array<string,string> $apiAliases */
    public function __construct(
        private readonly string $root,
        private readonly RequestHandler $api,
        private readonly AssetResponder $assets,
        private readonly array $webRoutes,
        private readonly array $apiAliases,
    ) {
    }

    public function run(Request $request): void
    {
        $path = $request->path();
        $base = $this->basePath($request);
        Url::configure($base);
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        if (str_contains($path, "\0") || str_contains($path, '\\') || array_intersect(['.', '..'], explode('/', $path)) !== []) {
            (new Response('Requisição inválida.', 400))->send();
            return;
        }
        CsrfGuard::protectCurrentApiMutation();
        if ($path === '/' || $path === '/index.php') {
            Response::empty(302, ['Location' => Url::to('views/index.php')])->send();
            return;
        }
        if ($path === '/views/src/pages/upload_turma_pdf.php') {
            $path = '/api/upload_turma_pdf.php';
            if (isset($_FILES['pdf']) && !isset($_FILES['pdf_arquivo'])) {
                $_FILES['pdf_arquivo'] = $_FILES['pdf'];
            }
        }
        $path = $this->apiAliases[$path] ?? $path;
        $request = $request->withPath($path);
        if (str_starts_with($path, '/api/v1/')) {
            $this->api->handle($request)->send();
        } elseif (isset($this->webRoutes[$path])) {
            (new PageController())->show($request, $this->root . '/' . $this->webRoutes[$path])->send();
        } else {
            $this->assets->send($request);
        }
    }

    private function basePath(Request $request): string
    {
        $configured = trim(Env::get('SGI_BASE_PATH', ''), '/');
        if ($configured !== '') {
            return '/' . $configured;
        }
        $script = str_replace('\\', '/', (string) $request->server('SCRIPT_NAME', '/index.php'));
        if ($script === $request->path()) {
            return '';
        }
        $marker = strrpos($script, '/public/index.php');
        if ($marker !== false) {
            return substr($script, 0, $marker);
        }
        return str_ends_with($script, '/index.php') ? rtrim(substr($script, 0, -10), '/') : '';
    }
}
