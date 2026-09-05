<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

use App\Interclasse\Application\EdicaoService;
use App\Interclasse\Infrastructure\MysqliEdicaoRepository;
use App\Shared\Storage\StoragePaths;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$service = new EdicaoService(new MysqliEdicaoRepository($conn));
requerNivel([0, 1, 2, 3]);

/** @return array<string, mixed> */
function edicaoPayload(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

/** @param array<string, mixed> $file @return array{success:bool,nome_arquivo?:string,message?:string} */
function uploadRegulamento(array $file): array
{
    $directory = StoragePaths::regulamentos();
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        return ['success' => false, 'message' => 'Falha ao preparar armazenamento do regulamento.'];
    }
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return ['success' => false, 'message' => 'O arquivo deve ser um PDF.'];
    }
    $newName = 'reg_' . bin2hex(random_bytes(12)) . '.pdf';
    $path = $directory . DIRECTORY_SEPARATOR . $newName;
    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $path)) {
        return ['success' => false, 'message' => 'Falha ao salvar arquivo.'];
    }
    return ['success' => true, 'nome_arquivo' => $newName];
}

try {
    switch ($method) {
        case 'GET':
            $details = isset($_GET['regulamento']) && $_GET['regulamento'] === 'true'
                || !empty($_GET['id_interclasse']) || !empty($_GET['id']);
            echo json_encode($service->listar([
                'detalhes' => $details,
                'id_interclasse' => (int) ($_GET['id_interclasse'] ?? $_GET['id'] ?? 0),
                'ano' => (int) ($_GET['ano'] ?? 0),
                'busca' => trim((string) ($_GET['busca'] ?? '')),
            ]), JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            requerEscrita();
            $payload = edicaoPayload();
            $id = (int) ($_GET['id'] ?? 0);
            if ($id > 0) {
                if (isset($_FILES['pdf_regulamento']) && $_FILES['pdf_regulamento']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadRegulamento($_FILES['pdf_regulamento']);
                    if (!$upload['success']) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => $upload['message']], JSON_UNESCAPED_UNICODE);
                        break;
                    }
                    $payload['regulamento_interclasse'] = $upload['nome_arquivo'];
                }
                $service->atualizar($id, $payload);
                echo json_encode(['success' => true, 'message' => 'Atualizado com sucesso!'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $created = $service->criar($payload);
            echo json_encode([
                'success' => true,
                'id' => $created['id'],
                'equipes_padrao_garantidas' => $created['equipes_padrao_garantidas'],
                'erros_equipes' => $created['erros_equipes'],
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Falha em interclasse.php: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível processar a edição.'], JSON_UNESCAPED_UNICODE);
}
