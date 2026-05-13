<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db.php';
require_once 'filtros.php'; 

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {

    case 'GET':
        $filtro = aplicarFiltrosTurmas();

        $sql = "SELECT 
                turmas.id_turma, 
                turmas.nome_turma, 
                turmas.turno_turma, 
                turmas.nome_fantasia_turma, 
                turmas.pontuacao_turma,
                interclasses.nome_interclasse,
                categorias.nome_categoria
            FROM turmas 
            INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
            INNER JOIN categorias ON categorias.id_categoria = turmas.categorias_id_categoria
            WHERE 1=1";

        if (!empty($filtro['sql'])) {
            $sql .= $filtro['sql'];
        }

        $sql .= " ORDER BY turmas.pontuacao_turma DESC, turmas.nome_turma ASC";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            responderErro(500, "Erro na preparação da consulta: " . $conn->error);
        }

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'PUT':
        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data->id_turma)) {
            responderErro(400, "Dados inválidos ou ID da turma ausente.");
        }

        $campos = [];
        $params = [];
        $types = "";

        // Mapeamento dinâmico de campos para o UPDATE
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

// --- Funções Auxiliares ---

function responderErro($codigo, $mensagem) {
    http_response_code($codigo);
    echo json_encode(["success" => false, "message" => $mensagem]);
    exit();
}

/**
 * Função movida para fora do switch para evitar erros de escopo.
 * Se esta função já estiver em 'filtros.php', você pode deletar este bloco.
 */
