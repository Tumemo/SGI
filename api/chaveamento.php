<?php

declare (strict_types=1);
require_once dirname(__DIR__) . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
\App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0, 1, 2, 3]);
$data = json_decode(file_get_contents('php://input') ?: '{}');
$tipoModalidade = $data->tipo_modalidade ?? $_GET['tipo_modalidade'] ?? null;
if ($tipoModalidade === 'individual') {
    switch ($method) {
        case 'GET':
            $idModalidade = isset($_GET['id_modalidade']) ? (int) $_GET['id_modalidade'] : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da modalidade é obrigatório.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $acao = $_GET['acao'] ?? 'ranking';
            if ($acao === 'participantes') {
                $participantes = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::buscarParticipantes($conn, $idModalidade);
                echo json_encode(['success' => true, 'participantes' => $participantes], JSON_UNESCAPED_UNICODE);
            } else {
                $resultado = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::montarJsonRanking($conn, $idModalidade);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'POST':
            // O ranking de modalidades individuais é uma operação de campo;
            // o mesário precisa conseguir registrá-lo tanto online quanto na
            // fila offline. A criação estrutural de chaveamentos coletivos
            // continua restrita a administrador/colaborador abaixo.
            \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerOperacaoJogo();
            $idInterclasseAtiva = \App\Modules\Acesso\Presentation\Http\LegacyAccess::garantirInterclasseAtivo($conn);
            $idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Informe o ID da modalidade.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            if ((int) ($_SESSION['nivel'] ?? -1) === 2) {
                $stEdicao = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
                $stEdicao->bind_param('i', $idModalidade);
                $stEdicao->execute();
                $modalidadeEdicao = $stEdicao->get_result()->fetch_assoc();
                $stEdicao->close();
                if (!$modalidadeEdicao || (int) $modalidadeEdicao['interclasses_id_interclasse'] !== (int) $idInterclasseAtiva) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Mesários só podem registrar resultados da edição ativa.'], JSON_UNESCAPED_UNICODE);
                    break;
                }
            }
            $ranking = $data->ranking ?? null;
            if ($ranking && isset($ranking->primeiro, $ranking->segundo, $ranking->terceiro)) {
                $conn->begin_transaction();
                try {
                    $resultado = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::salvarRanking($conn, $idModalidade, ['primeiro' => (int) $ranking->primeiro, 'segundo' => (int) $ranking->segundo, 'terceiro' => (int) $ranking->terceiro]);
                    $conn->commit();
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } catch (Throwable $e) {
                    $conn->rollback();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $conn->begin_transaction();
                try {
                    $resultado = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogoAgenda($conn, $idModalidade);
                    $conn->commit();
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } catch (Throwable $e) {
                    $conn->rollback();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                }
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            break;
    }
} else {
    switch ($method) {
        case 'GET':
            $idModalidade = isset($_GET['id_modalidade']) ? (int) $_GET['id_modalidade'] : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID da modalidade é obrigatório.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            $acao = $_GET['acao'] ?? 'arvore';
            if ($acao === 'historico') {
                $resultado = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::montarHistorico($conn, $idModalidade);
            } elseif ($acao === 'classificacao') {
                $resultado = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::montarHistorico($conn, $idModalidade);
                unset($resultado['confrontos']);
            } else {
                $resultado = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::montarJsonArvore($conn, $idModalidade);
            }
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
        case 'POST':
            \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerEscrita();
            $idModalidade = isset($data->id_modalidade) ? (int) $data->id_modalidade : 0;
            if ($idModalidade <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Informe o ID da modalidade.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            // Verificar o tipo real da modalidade
            $stMod = $conn->prepare('SELECT tipos_modalidades_id_tipo_modalidade FROM modalidades WHERE id_modalidade = ? LIMIT 1');
            $stMod->bind_param('i', $idModalidade);
            $stMod->execute();
            $rowMod = $stMod->get_result()->fetch_assoc();
            $stMod->close();
            $isIndividual = $rowMod && (int) $rowMod['tipos_modalidades_id_tipo_modalidade'] === 2;
            $conn->begin_transaction();
            try {
                if ($isIndividual) {
                    $resultado = \App\Modules\Competicoes\Infrastructure\MysqliIndividualRepository::criarJogoAgenda($conn, $idModalidade);
                    $conn->commit();
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                    break;
                }
                $equipes = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::buscarEquipesValidadas($conn, $idModalidade);
                if (count($equipes) < 2) {
                    throw new RuntimeException('É necessário ao menos duas equipes ativas com competidores vinculados (elenco).');
                }
                $res = \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::criarChaveamentoInicial($conn, $idModalidade, $equipes);
                foreach ($res['bye_jogos'] as $idBye) {
                    \App\Modules\Competicoes\Infrastructure\MysqliChaveamentoRepository::chaveamentoProcessarAvanco($conn, (int) $idBye);
                }
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Chaveamento mata-mata gerado.', 'jogos_criados' => $res['jogos_criados'], 'bye_inicial' => count($res['bye_jogos'])], JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
