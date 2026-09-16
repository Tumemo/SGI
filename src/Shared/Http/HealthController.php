<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class HealthController
{
    public function __invoke(Request $request, array $parameters = []): Response
    {
        $payload = [
            'success' => true,
            'status' => 'ok',
            'service' => 'sgi',
        ];
        if (\App\Shared\Config\Env::get('SGI_APP_ENV') === 'test') {
            $payload['test_environment'] = [
                'database' => \App\Shared\Config\Env::get('SGI_DB_NAME'),
                'database_runtime' => \App\Shared\Config\Env::get('SGI_TEST_DB_RUNTIME'),
            ];
        }
        return Response::json($payload, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
