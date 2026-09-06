<?php

declare(strict_types=1);

try {
    $app = require dirname(__DIR__) . '/bootstrap/app.php';
    $app->run(\App\Shared\Http\Request::fromGlobals());
} catch (Throwable $exception) {
    error_log('Falha HTTP: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a requisição.'], JSON_UNESCAPED_UNICODE);
}
