<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class ViewRenderer
{
    /** @param array<string, mixed> $data */
    public function render(string $file, array $data = []): Response
    {
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $template, array $context): void {
                extract($context, EXTR_SKIP);
                require $template;
            })($file, $data);
            return new Response((string) ob_get_clean(), 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (\Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $exception;
        }
    }
}
