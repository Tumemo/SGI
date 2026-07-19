<?php
require_once '../config/db.php';
require_once 'auth.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'GET') {
    $idInterclasse = isset($_GET['id_interclasse']) ? (int) $_GET['id_interclasse'] : 0;
    if ($idInterclasse <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "id_interclasse ausente."]);
        exit;
    }

    $sql = "SELECT 
                h.id_historico,
                h.id_turma,
                h.quantidade,
                h.pontos_adicionados,
                h.data_registro,
                h.status_historico,
                t.nome_turma,
                c.nome_categoria,
                u.nome_usuario AS registrado_por_nome
            FROM historico_arrecadacoes h
            INNER JOIN turmas t ON t.id_turma = h.id_turma
            LEFT JOIN categorias c ON c.id_categoria = t.categorias_id_categoria
            LEFT JOIN usuarios u ON u.id_usuario = h.registrado_por
            WHERE h.id_interclasse = ?
            ORDER BY h.data_registro DESC, h.id_historico DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idInterclasse);
    $stmt->execute();
    $result = $stmt->get_result();

    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = [
            'id_historico'      => (int) $row['id_historico'],
            'id_turma'          => (int) $row['id_turma'],
            'quantidade'        => (float) $row['quantidade'],
            'pontos_adicionados'=> (int) $row['pontos_adicionados'],
            'data_registro'     => $row['data_registro'],
            'status_historico'  => $row['status_historico'],
            'nome_turma'        => $row['nome_turma'],
            'nome_categoria'    => $row['nome_categoria'] ?? 'Geral',
            'registrado_por_nome' => $row['registrado_por_nome'] ?? 'Sistema',
        ];
    }

    echo json_encode($registros);
    exit;
}

if ($method === 'POST') {
    requerEscrita();
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->arrecadacoes) || !isset($data->id_interclasse)) {
        echo json_encode(["success" => false, "message" => "Dados incompletos no envio em lote."]);
        exit;
    }

    $conn->begin_transaction();

    try {
        iniciarSessao();
        $idUsuario = (int) ($_SESSION['id'] ?? 0);
        $idInterclasse = (int) $data->id_interclasse;

        $sqlUpdate = "UPDATE turmas t
                INNER JOIN interclasses i ON t.interclasses_id_interclasse = i.id_interclasse
                SET
                    t.qtd_itens_arrecadados = t.qtd_itens_arrecadados + ?,
                    t.pontuacao_turma = t.pontuacao_turma + ROUND(? * i.valor_item_arrecadacao, 2)
                WHERE t.id_turma = ? AND i.id_interclasse = ?";

        $stmtUpdate = $conn->prepare($sqlUpdate);

        $sqlHist = "INSERT INTO historico_arrecadacoes (id_turma, id_interclasse, quantidade, pontos_adicionados, registrado_por)
                    VALUES (?, ?, ?, ROUND(? * (SELECT valor_item_arrecadacao FROM interclasses WHERE id_interclasse = ?)), ?)";

        $stmtHist = $conn->prepare($sqlHist);

        foreach ($data->arrecadacoes as $item) {
            $delta = round((float) $item->quantidade, 2);
            if ($delta == 0.0) {
                continue;
            }

            $id_turma = (int) $item->id_turma;

            $stmtUpdate->bind_param("ddii", $delta, $delta, $id_turma, $idInterclasse);
            $stmtUpdate->execute();

            $stmtHist->bind_param("iiddii", $id_turma, $idInterclasse, $delta, $delta, $idInterclasse, $idUsuario);
            $stmtHist->execute();
        }

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Pontuações somadas com sucesso!"]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    requerEscrita();
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id_historico) || !isset($data->id_interclasse)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "id_historico e id_interclasse são obrigatórios."]);
        exit;
    }

    $idHistorico = (int) $data->id_historico;
    $idInterclasse = (int) $data->id_interclasse;

    $conn->begin_transaction();

    try {
        $sqlFind = "SELECT id_turma, quantidade, pontos_adicionados, status_historico
                    FROM historico_arrecadacoes
                    WHERE id_historico = ? AND id_interclasse = ?";
        $stmtFind = $conn->prepare($sqlFind);
        $stmtFind->bind_param("ii", $idHistorico, $idInterclasse);
        $stmtFind->execute();
        $result = $stmtFind->get_result();
        $registro = $result->fetch_assoc();

        if (!$registro) {
            $conn->rollback();
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Registro não encontrado."]);
            exit;
        }

        if ($registro['status_historico'] === '0') {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Este registro já foi removido anteriormente."]);
            exit;
        }

        $idTurma = (int) $registro['id_turma'];
        $quantidade = (float) $registro['quantidade'];
        $pontosAdicionados = (int) $registro['pontos_adicionados'];

        $sqlReverter = "UPDATE turmas t
                        INNER JOIN interclasses i ON t.interclasses_id_interclasse = i.id_interclasse
                        SET
                            t.qtd_itens_arrecadados = t.qtd_itens_arrecadados - ?,
                            t.pontuacao_turma = t.pontuacao_turma - ?
                        WHERE t.id_turma = ? AND i.id_interclasse = ?";
        $stmtReverter = $conn->prepare($sqlReverter);
        $stmtReverter->bind_param("ddii", $quantidade, $pontosAdicionados, $idTurma, $idInterclasse);
        $stmtReverter->execute();

        $sqlDel = "UPDATE historico_arrecadacoes SET status_historico = '0' WHERE id_historico = ?";
        $stmtDel = $conn->prepare($sqlDel);
        $stmtDel->bind_param("i", $idHistorico);
        $stmtDel->execute();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Registro removido e pontos revertidos com sucesso!"]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método não permitido."]);
