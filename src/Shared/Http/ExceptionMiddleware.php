<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Throwable;

final class ExceptionMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $next): Response
    {
        try {
            return $next->handle($request);
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Falha não tratada em %s %s: %s',
                $request->method(),
                $request->path(),
                $exception->getMessage(),
            ));

            return Response::json([
                'success' => false,
                'message' => 'Não foi possível processar a requisição.',
            ], 500);
        }
    }
}
