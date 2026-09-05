<?php

declare(strict_types=1);

require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/idempotencia.php';

use App\Modules\Interclasses\Application\OcorrenciaService;
use App\Modules\Interclasses\Infrastructure\MysqliOcorrenciaRepository;

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ocorrenciaService = new OcorrenciaService(new MysqliOcorrenciaRepository($conn));
requerNivel([0, 1, 2, 3]);

/**
 * Converte o identificador negativo de uma partida criada no IndexedDB para o
 * jogo definitivo. A resolução é pela tag da chave e pela modalidade, nunca
 * pelo último jogo do atleta (que poderia pertencer a outra partida).
 */
function sgi_resolver_jogo_temporario_ocorrencia(mysqli $conn, object $data): int
{
    $idJogo = (int) ($data->id_jogo ?? 0);
    if ($idJogo >= 0) {
        return $idJogo;
    }

    $nomeJogo = trim((string) ($data->nome_jogo ?? ''));
    $idModalidade = (int) ($data->id_modalidade ?? 0);
    if ($nomeJogo === '' || $idModalidade <= 0) {
        return 0;
    }

    require_once __DIR__ . '/includes/mata_mata_engine.php';
    $jogo = sgi_mm_buscar_jogo_por_tag($conn, $idModalidade, $nomeJogo);
    return $jogo ? (int) $jogo['id_jogo'] : 0;
}

switch ($method) {
    case 'GET':
        if (!empty($_GET['acao']) && $_GET['acao'] === 'listar_atletas') {
            $idJogo = isset($_GET['id_jogo']) ? intval($_GET['id_jogo']) : 0;
            $idTurma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : 0;
            if ($idTurma <= 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "id_turma é obrigatório."]);
                break;
            }
            if ($idJogo <= 0) {
                $sql = "SELECT DISTINCT u.id_usuario, u.nome_usuario, u.matricula_usuario
                        FROM usuarios u
                        INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario
                        INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe
                        WHERE e.turmas_id_turma = ?
                          AND u.status_usuario = '1' AND u.nivel_usuario = '3'
                          AND u.id_usuario NOT IN (
                            SELECT o2.usuarios_id_usuario
                            FROM ocorrencias o2
                            WHERE o2.titulo_ocorrencia = 'Suspensao'
                              AND o2.status_ocorrencia = '1'
                          )
                        ORDER BY u.nome_usuario ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $idTurma);
                $stmt->execute();
                $res = $stmt->get_result();
                echo json_encode(["success" => true, "atletas" => $res->fetch_all(MYSQLI_ASSOC)]);
                break;
            }
            $likeJogo = '%[JOGO:' . $idJogo . ']%';
            $sql = "SELECT DISTINCT u.id_usuario, u.nome_usuario, u.matricula_usuario
                    FROM usuarios u
                    INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario
                    INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe
                    INNER JOIN partidas p ON p.equipes_id_equipe = e.id_equipe
                    WHERE p.jogos_id_jogo = ? AND e.turmas_id_turma = ?
                      AND u.status_usuario = '1' AND u.nivel_usuario = '3'
                      AND u.id_usuario NOT IN (
                        SELECT o2.usuarios_id_usuario
                        FROM ocorrencias o2
                        WHERE o2.titulo_ocorrencia = 'Suspensao'
                          AND o2.status_ocorrencia = '1'
                      )
                      AND u.id_usuario NOT IN (
                        SELECT o3.usuarios_id_usuario
                        FROM ocorrencias o3
                        WHERE o3.titulo_ocorrencia = 'Vermelho'
                          AND o3.descricao_ocorrencia LIKE ?
                          AND o3.status_ocorrencia = '1'
                      )
                    ORDER BY u.nome_usuario ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $idJogo, $idTurma, $likeJogo);
            $stmt->execute();
            $res = $stmt->get_result();
            echo json_encode(["success" => true, "atletas" => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        $filtro = aplicarFiltrosOcorrencias();

        $sql = "SELECT 
                    ocorrencias.id_ocorrencia, 
                    ocorrencias.titulo_ocorrencia, 
                    ocorrencias.descricao_ocorrencia, 
                    ocorrencias.data_ocorrencia, 
                    ocorrencias.hora_ocorrencia, 
                    ocorrencias.penalidade,
                    usuarios.nome_usuario,
                    usuarios.id_usuario,
                    usuarios.turmas_id_turma
                FROM ocorrencias 
                INNER JOIN usuarios ON ocorrencias.usuarios_id_usuario = usuarios.id_usuario 
                WHERE 1=1" . $filtro['sql'];

        if (!empty($_GET['id_jogo'])) {
            $buscaJogo = '%[JOGO:' . intval($_GET['id_jogo']) . ']%';
            $sql .= " AND ocorrencias.descricao_ocorrencia LIKE ?";
            if (!empty($filtro['params'])) {
                $filtro['types'] .= 's';
                $filtro['params'][] = $buscaJogo;
            } else {
                $filtro['types'] = 's';
                $filtro['params'] = [$buscaJogo];
            }
        }

        $sql .= " ORDER BY ocorrencias.data_ocorrencia DESC, ocorrencias.hora_ocorrencia DESC";

        $stmt = $conn->prepare($sql);

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

   case 'POST':
        // Permite Admin e Mesário registrarem ocorrências (cartões/punições)
        requerOperacaoJogo();
        garantirInterclasseAtivo($conn);
        $respostaAnterior = sgi_buscar_resposta_idempotente($conn, 'ocorrencias.post');
        if ($respostaAnterior !== null) {
            http_response_code($respostaAnterior['status']);
            echo json_encode($respostaAnterior['payload'], JSON_UNESCAPED_UNICODE);
            break;
        }
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->titulo_ocorrencia, $data->descricao_ocorrencia, $data->data_ocorrencia, $data->usuarios_id_usuario)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $penalidade = isset($data->penalidade) ? intval($data->penalidade) : 0;

        $descricao = $data->descricao_ocorrencia;
        $idJogo = isset($data->id_jogo) ? intval($data->id_jogo) : 0;
        $idTurma = isset($data->id_turma) ? intval($data->id_turma) : 0;
        if ($idJogo < 0) {
            $idJogoResolvido = sgi_resolver_jogo_temporario_ocorrencia($conn, $data);
            if ($idJogoResolvido <= 0) {
                http_response_code(409);
                echo json_encode([
                    "success" => false,
                    "message" => "A partida temporária ainda não foi materializada. O registro continuará na fila para evitar perda de dados."
                ]);
                break;
            }
            $idJogo = $idJogoResolvido;
        }
        if ($idJogo > 0) {
            $descricao = '[JOGO:' . $idJogo . ']' . ($idTurma > 0 ? '[TURMA:' . $idTurma . ']' : '') . $data->descricao_ocorrencia;
        }

        try {
            $resultado = $ocorrenciaService->registrar([
                'titulo_ocorrencia' => $data->titulo_ocorrencia,
                'descricao_ocorrencia' => (string) $data->descricao_ocorrencia,
                'data_ocorrencia' => $data->data_ocorrencia,
                'usuarios_id_usuario' => (int) $data->usuarios_id_usuario,
                'penalidade' => $penalidade,
                'id_jogo' => $idJogo,
                'id_turma' => $idTurma,
            ]);
            $response = [
                "success" => true,
                "message" => "Ocorrência registrada com sucesso!",
                "id" => $resultado['id'],
            ];
            if ($resultado['evento'] !== null) {
                $response['evento'] = $resultado['evento'];
            }
            sgi_enviar_resposta_idempotente($conn, 'ocorrencias.post', 201, $response);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $exception) {
            error_log('Falha ao registrar ocorrência: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Não foi possível registrar ocorrência."], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        requerOperacaoJogo();
        garantirInterclasseAtivo($conn);
        $data = json_decode(file_get_contents("php://input"));

        $payload = is_object($data) ? get_object_vars($data) : [];
        try {
            $ocorrenciaService->atualizar($payload);
            echo json_encode(["success" => true, "message" => "Ocorrência atualizada com sucesso!"], JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $exception) {
            error_log('Falha ao atualizar ocorrência: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Não foi possível atualizar ocorrência."], JSON_UNESCAPED_UNICODE);
        }
        break;
}
