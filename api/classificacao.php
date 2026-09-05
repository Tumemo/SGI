<?php

declare(strict_types=1);

require_once '../config/db.php';
require_once __DIR__ . '/auth.php';

requerNivel([0, 1, 2, 3]);

use App\Modules\Interclasses\Application\ClassificacaoService;
use App\Modules\Interclasses\Infrastructure\MysqliClassificacaoRepository;

header('Content-Type: application/json; charset=utf-8');

$service = new ClassificacaoService(new MysqliClassificacaoRepository($conn));
$modalityId = (int) ($_GET['id_modalidade'] ?? 0);

try {
    $result = $service->gerar($modalityId);
    echo json_encode([
        'success' => true,
        'modalidade_id' => $result['modalidade_id'],
        'podio' => $result['podio'],
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em classificacao.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a classificação.'], JSON_UNESCAPED_UNICODE);
}
