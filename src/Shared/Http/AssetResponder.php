<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Storage\StoragePaths;

final class AssetResponder
{
    private const CONTENT_TYPES = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8', 'svg' => 'image/svg+xml',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'pdf' => 'application/pdf', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf', 'map' => 'application/json; charset=UTF-8',
    ];

    public function __construct(private readonly string $root, private readonly PublicFileResolver $files = new PublicFileResolver())
    {
    }

    public function send(Request $request): void
    {
        $path = ltrim($request->path(), '/');
        $file = null;
        if (str_starts_with($path, 'assets/')) {
            $file = $this->files->resolve($path, $this->root . '/public');
        }
        if ($file === null && str_starts_with($path, 'uploads/')) {
            foreach ([
                'uploads/regulamentos' => StoragePaths::regulamentos(),
                'uploads/fotosUsuarios' => StoragePaths::fotosUsuarios(),
            ] as $prefix => $directory) {
                if (str_starts_with($path, $prefix . '/')) {
                    $file = $this->files->resolve(substr($path, strlen($prefix) + 1), $directory);
                    break;
                }
            }
        }
        $extension = strtolower(pathinfo($file ?? '', PATHINFO_EXTENSION));
        if ($file === null || !isset(self::CONTENT_TYPES[$extension])) {
            (new Response('Recurso não encontrado.', 404))->send();
            return;
        }
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            Response::empty(405, ['Allow' => 'GET, HEAD'])->send();
            return;
        }
        header('Content-Type: ' . self::CONTENT_TYPES[$extension]);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=3600');
        if ($request->method() !== 'HEAD') {
            readfile($file);
        }
    }
}
