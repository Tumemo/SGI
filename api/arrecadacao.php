<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->arrecadacoes) || !isset($data->id_interclasse)) {
        echo json_encode(["success" => false, "message" => "Dados incompletos no envio em lote."]);
        exit;
    }

    $conn->begin_transaction();

    try {
        /* ALTERAÇÃO CHAVE:
           t.qtd_itens_arrecadados = t.qtd_itens_arrecadados + ?
           t.pontuacao_turma = t.pontuacao_turma + (? * i.valor_item_arrecadacao)
        */
        $sql = "UPDATE turmas t
                INNER JOIN interclasses i ON t.interclasses_id_interclasse = i.id_interclasse
                SET 
                    t.qtd_itens_arrecadados = t.qtd_itens_arrecadados + ?, 
                    t.pontuacao_turma = t.pontuacao_turma + (? * i.valor_item_arrecadacao)
                WHERE t.id_turma = ? AND i.id_interclasse = ?";

        $stmt = $conn->prepare($sql);

        foreach ($data->arrecadacoes as $item) {
            $quantidade = (int)$item->quantidade;
            
            // Se a quantidade enviada for 0, pula para não processar desnecessariamente
            if ($quantidade === 0) continue;

            $id_turma = (int)$item->id_turma;
            $id_interclasse = (int)$data->id_interclasse;

            $stmt->bind_param("iiii", $quantidade, $quantidade, $id_turma, $id_interclasse);
            $stmt->execute();
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