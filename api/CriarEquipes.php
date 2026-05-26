<?php
require_once '../config/db.php';
header('Content-Type: application/json');

    $sqlTurmas = "SELECT id_turma, categorias_id_categoria FROM turmas WHERE status_turma = '1'";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->execute();
    $resultTurmas = $stmtTurmas->get_result()->fetch_all(MYSQLI_ASSOC);

    $sqlModalidades = "SELECT id_modalidade, categorias_id_categoria FROM modalidades WHERE status_modalidade = '1'";
    $stmtModalidades = $conn->prepare($sqlModalidades);
    $stmtModalidades->execute();
    $resultModalidades = $stmtModalidades->get_result()->fetch_all(MYSQLI_ASSOC);

    $equipesGeradas = 0;

    foreach ($resultTurmas as $turma) {
        foreach ($resultModalidades as $modalidade) {
            
            $idTurma = $turma['id_turma'];
            $idModalidade = $modalidade['id_modalidade'];
            
            $sqlCheck = "SELECT id_equipe FROM equipes WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("ii", $idModalidade, $idTurma);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            
            if ($resCheck->num_rows === 0) {
                
                if ($turma['categorias_id_categoria'] == $modalidade['categorias_id_categoria']) {
                    
                    $sqlInsert = "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma) VALUES ('1', ?, ?)";
                    $stmtInsert = $conn->prepare($sqlInsert);
                    $stmtInsert->bind_param("ii", $idModalidade, $idTurma);
                    $stmtInsert->execute();
                    
                    $equipesGeradas++;
                }
            }
        }
    }

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Processamento concluído com sucesso.",
        "equipes_criadas_nesta_rodada" => $equipesGeradas
    ]);
