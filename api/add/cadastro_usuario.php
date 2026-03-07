    <?php
   require_once "../../config/db.php";

    header("Content-Type: application/json");

    // Recebe JSON
    $data = json_decode(file_get_contents("php://input"), true);

    // Verifica se veio JSON válido
    if (!$data) {
        echo json_encode(["success" => false, "message" => "JSON inválido."]);
        exit;
    }

    // Verifica parâmetros obrigatórios
    $required = ['sigla', 'matricula', 'senha', 'nome', 'genero', 'dt_nascimento'];

    foreach ($required as $field) {
        if (!isset($data[$field])) {
            echo json_encode([
                "success" => false,
                "message" => "Parâmetro obrigatório ausente: $field"
            ]);
            exit;
        }
    }

    // Sanitização básica
    $sigla     = trim($data['sigla']);
    $matricula = trim($data['matricula']);
    $senha     = trim($data['senha']);
    $nome      = trim($data['nome']);
    $genero    = trim($data['genero']);
    $dt        = $data['dt_nascimento'];

    // Campos opcionais com valores padrão
    $nivel_usuario = isset($data['nivel_usuario']) ? (int)$data['nivel_usuario'] : 0;
    $competidor    = isset($data['competidor']) ? (int)$data['competidor'] : 1;
    $mesario       = isset($data['mesario']) ? (int)$data['mesario'] : 0;
    $turma         = isset($data['turma']) ? (int)$data['turma'] : null;

    // Verifica campos vazios
    if (empty($sigla) || empty($matricula) || empty($senha) || empty($nome)) {
        echo json_encode([
            "success" => false,
            "message" => "Campos obrigatórios não podem estar vazios."
        ]);
        exit;
    }

    // Valida formato da data (YYYY-MM-DD)
    $dateObj = DateTime::createFromFormat('Y-m-d', $dt);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $dt) {
        echo json_encode([
            "success" => false,
            "message" => "Data inválida. Use o formato YYYY-MM-DD."
        ]);
        exit;
    }

    // Criptografa a senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Prepared Statement (Seguro contra SQL Injection)
    $stmt = $conn->prepare("
        INSERT INTO usuarios 
        (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, 
        foto_ususario, nivel_usuario, competidor_usuario, mesario_usuario, 
        genero_usuario, data_nasc_usuario, turmas_id_turma1) 
        VALUES (?, ?, ?, ?, 'default.jpg', ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Erro na preparação da query.",
            "error" => $conn->error
        ]);
        exit;
    }

    // Bind dos parâmetros
    $stmt->bind_param(
        "ssssiiissi",
        $sigla,
        $matricula,
        $nome,
        $senhaHash,
        $nivel_usuario,
        $competidor,
        $mesario,
        $genero,
        $dt,
        $turma
    );

    // Executa
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Usuário cadastrado com sucesso."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Erro ao cadastrar usuário.",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    ?>