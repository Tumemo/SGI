<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class HealthController
{
    public function __invoke(Request $request, array $parameters = []): Response
    {
        return Response::json([
            'success' => true,
            'status' => 'ok',
            'service' => 'sgi',
        ]);
    }
}
