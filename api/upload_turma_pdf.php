<?php
ob_start();
header('Content-Type: application/json');

$resposta = [
    'success' => false,
    'message' => ''
];

try {
    if (!isset($_FILES['pdf_arquivo'])) {
        throw new Exception('Nenhum arquivo enviado. Campo esperado: pdf_arquivo');
    }

    $file = $_FILES['pdf_arquivo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo. Código: ' . $file['error']);
    }

    $id_turma = isset($_POST['id_turma']) ? (int)$_POST['id_turma'] : 0;
    $id_interclasse = isset($_POST['id_interclasse']) ? (int)$_POST['id_interclasse'] : 0;

    if ($id_turma <= 0) {
        throw new Exception('Campo id_turma inválido.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Apenas arquivos PDF são permitidos.');
    }

    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/includes/pdf_helper.php';

    require_once __DIR__ . '/../config/db.php';

    $stTurma = $conn->prepare('SELECT nome_turma, interclasses_id_interclasse FROM turmas WHERE id_turma = ? LIMIT 1');
    if (!$stTurma) {
        throw new Exception('Falha ao consultar turma.');
    }
    $stTurma->bind_param('i', $id_turma);
    $stTurma->execute();
    $rowTurma = $stTurma->get_result()->fetch_assoc();
    $stTurma->close();

    if (!$rowTurma) {
        throw new Exception('Turma não encontrada no banco.');
    }

    $nomeTurma = (string) $rowTurma['nome_turma'];
    $idInterclasseTurma = (int) $rowTurma['interclasses_id_interclasse'];

    if ($id_interclasse <= 0) {
        $id_interclasse = $idInterclasseTurma;
    }

    if ($id_interclasse <= 0) {
        $resAtivo = $conn->query("SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' LIMIT 1");
        if ($resAtivo) {
            $rowAtivo = $resAtivo->fetch_assoc();
            $id_interclasse = isset($rowAtivo['id_interclasse']) ? (int) $rowAtivo['id_interclasse'] : 0;
        }
    }

    if ($id_interclasse <= 0) {
        throw new Exception('Nenhum interclasse ativo encontrado. Informe id_interclasse no upload.');
    }

    $destDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'lista_alunos' . DIRECTORY_SEPARATOR;
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0777, true)) {
            throw new Exception('Falha ao criar pasta de destino.');
        }
        chmod($destDir, 0777);
    }

    $destFilename = 'turma_' . $id_turma . '.pdf';
    $destPath = $destDir . $destFilename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Falha ao salvar o arquivo no servidor.');
    }

    $csvFilename = 'turma_' . $id_turma . '.csv';
    $csvPath = $destDir . $csvFilename;

    $csvOk = sgi_pdf_para_csv($destPath, $csvPath);

    if (!$csvOk) {
        $resposta['message'] = 'Não foi possível extrair alunos do PDF. O PDF pode ser uma imagem (digitalizada). Tente usar um conversor online: https://www.ilovepdf.com/pt';
        $resposta['fallback_converter'] = true;
        ob_end_clean();
        echo json_encode($resposta);
        exit;
    }

    $alunos = sgi_extrair_alunos_do_csv($csvPath);

    if (empty($alunos)) {
        $resposta['message'] = 'PDF convertido para CSV, mas nenhum aluno foi extraído. Verifique o formato do PDF.';
        $resposta['fallback_converter'] = true;
        ob_end_clean();
        echo json_encode($resposta);
        exit;
    }

    foreach ($alunos as &$aluno) {
        $aluno['turma'] = $nomeTurma;
    }
    unset($aluno);

    $resultado = sgi_inserir_alunos_na_turma($conn, $alunos, $id_turma, $id_interclasse);

    if ($resultado['status'] === 'sucesso') {
        $resposta['success'] = true;
        $partes = [];
        if ($resultado['cadastrados'] > 0) {
            $partes[] = $resultado['cadastrados'] . ' registros inseridos';
        }
        if ($resultado['duplicados'] > 0) {
            $partes[] = $resultado['duplicados'] . ' duplicados ignorados';
        }
        $resposta['message'] = 'Importação concluída: ' . ($partes ? implode(', ', $partes) : '0 registros inseridos');

        if (!empty($resultado['erros'])) {
            $resposta['avisos'] = $resultado['erros'];
        }
    } else {
        $resposta['message'] = $resultado['mensagem'] ?? 'Erro desconhecido na importação.';
    }

} catch (Exception $e) {
    $resposta['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($resposta);
exit;
