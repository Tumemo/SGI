<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
require_once __DIR__ . '/includes/equipes_helper.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (!empty($_GET['id_equipe']) && empty($_GET['id_turma'])) {
            $id_equipe = intval($_GET['id_equipe']);
            $sql = "SELECT u.id_usuario, u.nome_usuario, u.matricula_usuario
                    FROM usuarios u
                    INNER JOIN equipes_has_usuarios eu ON eu.usuarios_id_usuario = u.id_usuario
                    WHERE eu.equipes_id_equipe = ? AND u.status_usuario = '1'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id_equipe);
            $stmt->execute();
            $res = $stmt->get_result();
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
            break;
        }
        
        $filtro = aplicarFiltrosEquipes();
        $sql = "SELECT 
                    equipes.id_equipe, 
                    equipes.nome_equipe,
                    equipes.status_equipe,
                    equipes.modalidades_id_modalidade,
                    equipes.turmas_id_turma,
                    modalidades.nome_modalidade, 
                    modalidades.max_inscrito_modalidade AS limite_maximo,
                    turmas.nome_turma,
                    interclasses.nome_interclasse
                FROM equipes 
                INNER JOIN modalidades ON modalidades.id_modalidade = equipes.modalidades_id_modalidade 
                INNER JOIN turmas ON turmas.id_turma = equipes.turmas_id_turma
                INNER JOIN interclasses ON interclasses.id_interclasse = turmas.interclasses_id_interclasse
                WHERE 1=1" . $filtro['sql'] . "
                ORDER BY equipes.id_equipe ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            break;
        }
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            break;
        }
        $res = $stmt->get_result();
        if (!$res) {
            echo json_encode(["success" => false, "message" => "Erro ao obter resultados."]);
            break;
        }
        $equipes = $res->fetch_all(MYSQLI_ASSOC);

        // RF05/RF03: expõe total de inscritos, limite da modalidade e a flag excedeu_limite.
        foreach ($equipes as &$equipe) {
            $total = (int) ($equipe['total_alunos'] ?? 0);
            $limite = (int) ($equipe['limite_maximo'] ?? 0);
            $equipe['total_alunos'] = $total;
            $equipe['limite_maximo'] = $limite;
            $equipe['excedeu_limite'] = $limite > 0 && $total > $limite;
        }
        unset($equipe);

        echo json_encode($equipes);
        break;

    case 'POST':
        requerEscrita();
        
        // Tenta ler JSON enviado via fetch body
        $data = json_decode(file_get_contents("php://input"));
        
        // Pega a ação vinda de JSON ou de Formulário POST convencional
        $acao = $data->acao ?? $_POST['acao'] ?? '';

        if ($acao === 'criar_equipe') {
            if (!isset($data->modalidades_id_modalidade, $data->turmas_id_turma)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Dados incompletos: modalidade e turma são obrigatórios."]);
                break;
            }

            $modalidade = intval($data->modalidades_id_modalidade);
            $turma = intval($data->turmas_id_turma);
            $status = isset($data->status_equipe) ? (string)$data->status_equipe : '1';

            // Permite customizar o nome ou gera automaticamente caso não informado
            if (!empty($data->nome_equipe)) {
                $nomeEquipe = trim($data->nome_equipe);
            } else {
                // Busca nome da modalidade e da turma
                $stmtM = $conn->prepare("SELECT nome_modalidade FROM modalidades WHERE id_modalidade = ?");
                $stmtM->bind_param("i", $modalidade);
                $stmtM->execute();
                $resM = $stmtM->get_result()->fetch_assoc();
                $nomeModalidade = $resM['nome_modalidade'] ?? '';
                $stmtM->close();

                $stmtT = $conn->prepare("SELECT nome_turma FROM turmas WHERE id_turma = ?");
                $stmtT->bind_param("i", $turma);
                $stmtT->execute();
                $resT = $stmtT->get_result()->fetch_assoc();
                $nomeTurma = $resT['nome_turma'] ?? '';
                $stmtT->close();

                // Conta equipes para definir o sequencial ("- 2", "- 3", ...)
                $stmtC = $conn->prepare("SELECT COUNT(*) as total FROM equipes WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'");
                $stmtC->bind_param("ii", $modalidade, $turma);
                $stmtC->execute();
                $resC = $stmtC->get_result()->fetch_assoc();
                $numEquipe = ((int) ($resC['total'] ?? 0)) + 1;
                $stmtC->close();

                // Limite máximo de equipes por turma/modalidade (max_equipes)
                $maxEquipes = $resM['max_equipes'] ?? null;
                if ($maxEquipes !== null && $maxEquipes !== '' && (int) $maxEquipes > 0 && $numEquipe > (int) $maxEquipes) {
                    http_response_code(400);
                    echo json_encode(["success" => false, "message" => "Limite de " . $maxEquipes . " equipes por turma atingido para esta modalidade."]);
                    break;
                }

                $nomeEquipe = $nomeModalidade !== '' ? sgi_nome_equipe_turma($nomeTurma, $nomeModalidade, $numEquipe) : null;
            }

            $sql = "INSERT INTO equipes (modalidades_id_modalidade, turmas_id_turma, status_equipe, nome_equipe) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $modalidade, $turma, $status, $nomeEquipe);

            if ($stmt->execute()) {
                echo json_encode([
                    "success" => true, 
                    "message" => "Equipe criada com sucesso!", 
                    "id_equipe" => $conn->insert_id,
                    "nome_equipe" => $nomeEquipe
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao inserir: " . $conn->error]);
            }

        } elseif ($acao === 'adicionar_usuarios') {
            if (!isset($data->id_equipe, $data->usuarios) || !is_array($data->usuarios)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID da equipe e lista de IDs de usuários são obrigatórios."]);
                break;
            }

            $id_equipe = intval($data->id_equipe);
            $usuarios = $data->usuarios;
            $sucesso = true;

            $conn->begin_transaction();

            $sql = "INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            
            $param_user_id = 0;
            $stmt->bind_param("ii", $id_equipe, $param_user_id);

            foreach ($usuarios as $id_usuario) {
                $param_user_id = intval($id_usuario);
                if (!$stmt->execute()) {
                    $sucesso = false;
                    break; 
                }
            }

            if ($sucesso) {
                $conn->commit(); 
                echo json_encode(["success" => true, "message" => "Usuários vinculados à equipe com sucesso!"]);
            } else {
                $conn->rollback(); 
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Houve erro ao vincular os usuários. Alterações revertidas."]);
            }

        } elseif ($acao === 'remover_aluno') {
            // Suporta dados via JSON ou FormData
            $id_equipe = intval($data->id_equipe ?? $_POST['id_equipe'] ?? 0);
            $id_usuario = intval($data->id_usuario ?? $_POST['id_usuario'] ?? 0);

            if ($id_equipe <= 0 || $id_usuario <= 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID da equipe e ID do usuário são obrigatórios."]);
                break;
            }

            $sql = "DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id_equipe, $id_usuario);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Aluno removido da equipe com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao remover aluno: " . $conn->error]);
            }

        } elseif ($acao === 'redistribuir') {
            $modalidadeId = intval($data->modalidades_id_modalidade ?? $_POST['modalidades_id_modalidade'] ?? 0);
            $turmaId = intval($data->turmas_id_turma ?? $_POST['turmas_id_turma'] ?? 0);

            if ($modalidadeId <= 0 || $turmaId <= 0) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "modalidades_id_modalidade e turmas_id_turma são obrigatórios."]);
                break;
            }

            $resultado = sgi_redistribuir_equipe($conn, $modalidadeId, $turmaId);
            if (!$resultado['success']) {
                http_response_code(400);
            }
            echo json_encode($resultado);
            break;

        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Ação inválida ou não informada."]);
        }
        break;

    case 'PUT':
        requerEscrita();
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_equipe)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID da equipe é obrigatório."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->nome_equipe)) {
            $campos[] = "nome_equipe = ?";
            $params[] = trim((string)$data->nome_equipe);
            $types .= "s";
        }
        if (isset($data->modalidades_id_modalidade)) {
            $campos[] = "modalidades_id_modalidade = ?";
            $params[] = intval($data->modalidades_id_modalidade);
            $types .= "i";
        }
        if (isset($data->turmas_id_turma)) {
            $campos[] = "turmas_id_turma = ?";
            $params[] = intval($data->turmas_id_turma);
            $types .= "i";
        }
        if (isset($data->status_equipe)) {
            $campos[] = "status_equipe = ?";
            $params[] = (string)$data->status_equipe;
            $types .= "s";
        }

        if (empty($campos)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nenhum campo válido enviado para atualização."]);
            break;
        }

        $sql = "UPDATE equipes SET " . implode(", ", $campos) . " WHERE id_equipe = ?";
        $params[] = intval($data->id_equipe);
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Equipe atualizada com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $conn->error]);
        }
        break;

    case 'DELETE':
        requerExclusao();
        $data = json_decode(file_get_contents("php://input"));
        $id = intval($data->id_equipe ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da equipe é obrigatório."]);
            break;
        }
        $stmt = $conn->prepare("UPDATE equipes SET status_equipe = '0' WHERE id_equipe = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Equipe excluída com sucesso!"]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Equipe não encontrada."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}