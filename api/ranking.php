<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {

    case 'GET':
        // Mapeia todas as chaves possíveis de sessão, incluindo nivel_usuario da sua tabela
        $nivelRaw = $_SESSION['nivel_usuario'] ?? $_SESSION['nivel'] ?? $_SESSION['usuario_nivel'] ?? $_SESSION['nivel_acesso'] ?? $_SESSION['perfil'] ?? 99;

        // No seu BD o Admin é nível '0' ou '1'
        if (is_numeric($nivelRaw)) {
            $nivelUsuario = (int)$nivelRaw;
            $eAdmin = ($nivelUsuario === 0 || $nivelUsuario === 1);
        } else {
            $eAdmin = (strtolower((string)$nivelRaw) === 'admin');
        }

        $filtro = aplicarFiltrosTurmas();

        $sql = "SELECT 
                turmas.id_turma, 
                turmas.nome_turma, 
                turmas.turno_turma, 
                turmas.nome_fantasia_turma, 
                turmas.pontuacao_turma AS pontuacao_sem_penalidade,
                (turmas.pontuacao_turma - COALESCE(penalidades.total_penalidades, 0)) AS pontuacao_turma,
                interclasses.nome_interclasse,
                interclasses.status_interclasse AS status_interclasse,
                categorias.nome_categoria
            FROM turmas 
            INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
            INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
            LEFT JOIN (
                SELECT turmas_id_turma, SUM(total) AS total_penalidades FROM (
                    SELECT ot.turmas_id_turma, SUM(ot.pontos_descontados) AS total
                    FROM ocorrencias_turmas ot
                    GROUP BY ot.turmas_id_turma
                    UNION ALL
                    SELECT u.turmas_id_turma, SUM(o.penalidade) AS total
                    FROM ocorrencias o
                    INNER JOIN usuarios u ON o.usuarios_id_usuario = u.id_usuario
                    WHERE o.status_ocorrencia = '1'
                    GROUP BY u.turmas_id_turma
                ) sub
                GROUP BY turmas_id_turma
            ) penalidades ON penalidades.turmas_id_turma = turmas.id_turma
            WHERE 1=1";

        if (!empty($filtro['sql'])) {
            $sql .= $filtro['sql'];
        }

        $sql .= " ORDER BY pontuacao_turma DESC, turmas.nome_turma ASC";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            responderErro(500, "Erro na preparação da consulta: " . $conn->error);
        }

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $dados = $res->fetch_all(MYSQLI_ASSOC);

        // Se for um usuário comum e o interclasse estiver ativo, retorna aviso amigável sem quebrar o servidor
        if (!empty($dados) && !$eAdmin) {
            $status = $dados[0]['status_interclasse'] ?? 'ativo';
            if ($status === 'ativo' || $status === '1' || $status === 1 || $status === true) {
                echo json_encode([
                    "bloqueado" => true,
                    "message" => "O ranking deste interclasse está restrito apenas para os administradores."
                ]);
                exit();
            }
        }

        echo json_encode($dados);
        break;

    case 'PUT':
        requerEscrita();
        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data->id_turma)) {
            responderErro(400, "Dados inválidos ou ID da turma ausente.");
        }

        $campos = [];
        $params = [];
        $types = "";

        $mapeamento = [
            'interclasses_id_interclasse' => 'i',
            'nome_turma'                  => 's',
            'turno_turma'                 => 's',
            'nome_fantasia_turma'         => 's',
            'categorias_id_categoria'     => 'i',
            'status_turma'                => 's',
            'pontuacao_turma'             => 'i'
        ];

        foreach ($mapeamento as $campo => $tipo) {
            if (isset($data->$campo)) {
                $campos[] = "$campo = ?";
                $params[] = $data->$campo;
                $types .= $tipo;
            }
        }

        if (empty($campos)) {
            responderErro(400, "Nenhum campo enviado para atualização.");
        }

        $sql = "UPDATE turmas SET " . implode(", ", $campos) . " WHERE id_turma = ?";
        $params[] = $data->id_turma;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            responderErro(500, "Erro interno: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Turma atualizada com sucesso!"]);
            } else {
                echo json_encode(["success" => true, "message" => "Nenhuma alteração realizada (dados idênticos ou ID inexistente)."]);
            }
        } else {
            responderErro(500, "Erro ao atualizar: " . $stmt->error);
        }
        break;

    default:
        responderErro(405, "Método $method não permitido.");
        break;
}

function responderErro($codigo, $mensagem) {
    http_response_code($codigo);
    echo json_encode(["success" => false, "message" => $mensagem]);
    exit();
}