<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/usuario_validacao.php';
require_once __DIR__ . '/includes/importador_competidores.php';
require_once __DIR__ . '/includes/interclasse_helper.php';

header('Content-Type: application/json; charset=utf-8');

$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);
if (!is_array($inputData)) {
    $inputData = [];
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao = $_POST['acao'] ?? $inputData['acao'] ?? $_REQUEST['acao'] ?? '';

/**
 * @param array<string, mixed> $payload
 */
function sgi_json_saida(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

/**
 * RF05: validação RA + data (sem vazamento de existência de matrícula).
 */
function sgi_validar_inscricao_rf05(mysqli $conn, array $dados): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $idInterclasseAtivo = buscarInterclasseAtivo($conn);
    if ($idInterclasseAtivo === null) {
        return erroSemInterclasseAtivo();
    }

    $matriculaBruta = trim((string) ($dados['matricula_usuario'] ?? $dados['rm'] ?? $dados['ra'] ?? ''));
    $ra = sgi_normalizar_ra($matriculaBruta);
    $dataYmd = sgi_parse_data_nascimento($dados['data_nasc_usuario'] ?? '');

    if ($ra === '' || $dataYmd === null) {
        return ['status' => 'erro', 'mensagem' => 'Não foi possível validar os dados informados.'];
    }

    $usuario = sgi_buscar_competidor_por_ra_e_data($conn, $ra, $dataYmd, $idInterclasseAtivo);
    if ($usuario === null) {
        return ['status' => 'erro', 'mensagem' => 'Não foi possível validar os dados informados.'];
    }

    $_SESSION['logado'] = true;
    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['nivel'] = $usuario['nivel_usuario'];
    $_SESSION['id_interclasse'] = $idInterclasseAtivo;

    return [
        'status' => 'sucesso',
        'mensagem' => 'Dados validados.',
        'permissoes' => [
            'admin' => $usuario['nivel_usuario'] === '0',
            'colaborador' => $usuario['nivel_usuario'] === '1',
            'mesario' => $usuario['nivel_usuario'] === '2',
            'competidor' => $usuario['nivel_usuario'] === '3',
        ],
        'dados' => [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => $usuario['nome_usuario'],
            'sigla' => $usuario['sigla_usuario'],
        ],
    ];
}

switch ($metodo) {
    case 'GET':
        $idInterclasseAtivo = buscarInterclasseAtivo($conn);
        if ($idInterclasseAtivo === null) {
            sgi_json_saida(erroSemInterclasseAtivo());
            break;
        }

        if ($acao === 'listar_competidores') {
            $id_turma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : 0;
            if ($id_turma <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID da turma é obrigatório e deve ser um número válido.']);
                break;
            }

            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario, data_nasc_usuario
                    FROM usuarios WHERE turmas_id_turma = ? AND interclasses_id_interclasse = ? AND nivel_usuario = '3' AND status_usuario = '1'";
            try {
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new RuntimeException('Falha ao preparar consulta: ' . $conn->error);
                }
                $idInterclasseFiltro = isset($_GET['id_interclasse']) ? intval($_GET['id_interclasse']) : $idInterclasseAtivo;
                $stmt->bind_param('ii', $id_turma, $idInterclasseFiltro);
                $stmt->execute();
                $res = $stmt->get_result();
                $lista = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                sgi_json_saida(['status' => 'sucesso', 'competidores' => $lista]);
            } catch (Throwable $e) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Falha ao listar competidores: ' . $e->getMessage()]);
            }
        } elseif ($acao === 'listar_colaboradores') {
            // Traz colaboradores vinculados ao interclasse ativo OU contas mestras do sistema (NULL)
            // Níveis: '0' (Admin), '1' (Colaborador), '2' (Mesário)
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario
                    FROM usuarios
                    WHERE (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)
                      AND nivel_usuario IN ('0', '1', '2')
                      AND status_usuario = '1'
                    ORDER BY nome_usuario ASC";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('i', $idInterclasseAtivo);
            $stmt->execute();
            $res = $stmt->get_result();
            $lista = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            sgi_json_saida(['status' => 'sucesso', 'colaboradores' => $lista]);
        } elseif ($acao === '' || $acao === 'listar_por_turma') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario
                    FROM usuarios WHERE interclasses_id_interclasse = ? AND status_usuario = '1'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('i', $idInterclasseAtivo);
            $stmt->execute();
            $res = $stmt->get_result();
            $lista = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            sgi_json_saida(['status' => 'sucesso', 'usuarios' => $lista]);
        }
        break;

    case 'POST':
        if ($acao === 'cadastrar_competidores') {
            sgi_json_saida(importarCompetidores($conn));
            break;
        }

        if ($acao === 'validar_inscricao') {
            $dados = !empty($_POST) ? $_POST : $inputData;
            sgi_json_saida(sgi_validar_inscricao_rf05($conn, $dados));
            break;
        }

        if ($acao === 'cadastrar_usuario') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }

            $dados = !empty($_POST) ? $_POST : $inputData;

            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $senhaCrua = (string) ($dados['senha_usuario'] ?? '');
            $dataNascInput = trim((string) ($dados['data_nasc_usuario'] ?? ''));
            $dataNasc = sgi_parse_data_nascimento($dataNascInput) ?? $dataNascInput;

            $isAdmin = (($dados['is_admin_clicado'] ?? '0') === '1');
            $isMesario = (($dados['is_mesario_clicado'] ?? '0') === '1');

            // Define o caractere correto baseado no nível selecionado
            if ($isAdmin) {
                $nivel = '0';
            } elseif ($isMesario) {
                $nivel = '2';
            } else {
                $nivel = '1';
            }

            $sigla = ($nivel !== '3') ? 'SS' : (string) ($dados['sigla_usuario'] ?? 'RM');
            $genero = (string) ($dados['genero_usuario'] ?? 'MASC');
            $nomeFotoBanco = 'default.jpg';

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo((string) $_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nomeFotoBanco = 'user_' . $matricula . '_' . (string) time() . '.' . $ext;
                $destino = dirname(__DIR__) . '/uploads/fotosUsuarios/' . $nomeFotoBanco;
                move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
            }

            if ($nome === '' || $matricula === '' || $senhaCrua === '') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Campos incompletos']);
                break;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNasc)) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Data de nascimento inválida.']);
                break;
            }

            $senhaHash = password_hash($senhaCrua, PASSWORD_DEFAULT);
            $status = '1';

            $matriculaNorm = sgi_normalizar_ra($matricula) ?: $matricula;

            $emailsMaster = ['sgi@sgi.com', 'colab@sgi.com', 'mes@sgi.com'];
            $inputLower = mb_strtolower($matricula);
            $normLower = mb_strtolower($matriculaNorm);
            if (in_array($inputLower, $emailsMaster, true) || in_array($normLower, $emailsMaster, true)) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Este email pertence a uma conta padrão do sistema e não pode ser reutilizado.']);
                break;
            }

            $chaveEdicao = $matriculaNorm . '-' . $idInterclasseAtivo;

            $checkSql = 'SELECT COUNT(*) FROM usuarios WHERE chave_usuario_edicao = ?';
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param('s', $chaveEdicao);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();
                if ($count > 0) {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Já existe um usuário com esta matrícula/email nesta edição do Interclasse.']);
                    break;
                }
            }

            // Query estruturada sem colunas mortas
            $sql = 'INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, genero_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario, interclasses_id_interclasse, chave_usuario_edicao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }

            $stmt->bind_param(
                'sssssssssis',
                $nome,
                $matriculaNorm,
                $senhaHash,
                $nivel,
                $genero,
                $nomeFotoBanco,
                $status,
                $dataNasc,
                $sigla,
                $idInterclasseAtivo,
                $chaveEdicao
            );

            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Usuário cadastrado!']);
            } else {
                if ($stmt->errno === 1062) {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Esta matrícula/email já está cadastrada nesta edição do interclasse.']);
                } else {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
                }
            }
            $stmt->close();
            break;
        }

        if ($acao === 'atualizar_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do colaborador inválido.']);
                break;
            }
            
            $isAdmin = (($dados['is_admin_clicado'] ?? '0') === '1');
            $isMesario = (($dados['is_mesario_clicado'] ?? '0') === '1');

            if ($isAdmin) {
                $nivel = '0';
            } elseif ($isMesario) {
                $nivel = '2';
            } else {
                $nivel = '1';
            }

            // Atualiza apenas a coluna unificada nivel_usuario
            $sql = "UPDATE usuarios SET nivel_usuario = ? 
                    WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('sii', $nivel, $idUsuario, $idInterclasseAtivo);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador atualizado.']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }

        if ($acao === 'atualizar_dados_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do colaborador inválido.']);
                break;
            }
            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $genero = (string) ($dados['genero_usuario'] ?? 'MASC');
            $senhaCrua = (string) ($dados['senha_usuario'] ?? '');

            if ($nome === '' || $matricula === '') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Nome e matrícula são obrigatórios.']);
                break;
            }

            $chaveEdicao = $matricula . '-' . $idInterclasseAtivo;
            $checkSql = 'SELECT COUNT(*) FROM usuarios WHERE chave_usuario_edicao = ? AND id_usuario != ?';
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param('si', $chaveEdicao, $idUsuario);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();
                if ($count > 0) {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Já existe outro usuário com esta matrícula/email nesta edição.']);
                    break;
                }
            }

            $campos = [];
            $params = [];
            $types = '';

            $campos[] = "nome_usuario = ?";
            $params[] = $nome;
            $types .= "s";

            $campos[] = "matricula_usuario = ?";
            $params[] = $matricula;
            $types .= "s";

            $campos[] = "genero_usuario = ?";
            $params[] = $genero;
            $types .= "s";

            if ($senhaCrua !== '') {
                $senhaHash = password_hash($senhaCrua, PASSWORD_DEFAULT);
                $campos[] = "senha_usuario = ?";
                $params[] = $senhaHash;
                $types .= "s";
            }

            $params[] = $idUsuario;
            $types .= "i";
            $params[] = $idInterclasseAtivo;
            $types .= "i";

            $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador atualizado.']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }

        if ($acao === 'criar_aluno') {
            require_once __DIR__ . '/auth.php';
            requerNivel([0, 1, 2]);

            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }

            $dados = !empty($_POST) ? $_POST : $inputData;
            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $genero = strtoupper((string) ($dados['genero_usuario'] ?? 'MASC'));
            $dataNascRaw = trim((string) ($dados['data_nasc_usuario'] ?? ''));
            $dataNasc = sgi_parse_data_nascimento($dataNascRaw);
            $idTurma = (int) ($dados['turmas_id_turma'] ?? 0);

            if ($nome === '' || $matricula === '' || $dataNasc === null || $idTurma <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Campos obrigatórios: nome, RM, data de nascimento e turma.']);
                break;
            }

            if (!in_array($genero, ['FEM', 'MASC'], true)) {
                $genero = 'MASC';
            }

            $rmNorm = sgi_normalizar_ra($matricula);
            if ($rmNorm === '') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'RM inválido.']);
                break;
            }

            $chaveEdicao = $rmNorm . '-' . $idInterclasseAtivo;

            $checkStmt = $conn->prepare('SELECT id_usuario FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1');
            if ($checkStmt) {
                $checkStmt->bind_param('s', $chaveEdicao);
                $checkStmt->execute();
                if ($checkStmt->get_result()->fetch_assoc()) {
                    $checkStmt->close();
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Já existe um aluno com este RM nesta edição do interclasse.']);
                    break;
                }
                $checkStmt->close();
            }

            $senhaHash = password_hash('123', PASSWORD_DEFAULT);

            $sql = 'INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }

            $nivel = '3';
            $sigla = 'RM';
            $foto = 'default.jpg';
            $status = '1';
            $stmt->bind_param('sssssssssiis',
                $sigla, $rmNorm, $nome, $senhaHash, $nivel, $genero, $dataNasc, $foto, $status, $idTurma, $idInterclasseAtivo, $chaveEdicao
            );

            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Aluno cadastrado!', 'id_usuario' => $stmt->insert_id]);
            } else {
                if ($stmt->errno === 1062) {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'RM já cadastrado nesta edição.']);
                } else {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
                }
            }
            $stmt->close();
            break;
        }

        if ($acao === 'editar_aluno') {
            require_once __DIR__ . '/auth.php';
            requerNivel([0, 1, 2]);

            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }

            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do aluno inválido.']);
                break;
            }

            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $genero = strtoupper((string) ($dados['genero_usuario'] ?? 'MASC'));
            $dataNascRaw = trim((string) ($dados['data_nasc_usuario'] ?? ''));
            $dataNasc = sgi_parse_data_nascimento($dataNascRaw);

            if ($nome === '' || $matricula === '' || $dataNasc === null) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Campos obrigatórios: nome, RM e data de nascimento.']);
                break;
            }

            if (!in_array($genero, ['FEM', 'MASC'], true)) {
                $genero = 'MASC';
            }

            $rmNorm = sgi_normalizar_ra($matricula);
            if ($rmNorm === '') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'RM inválido.']);
                break;
            }

            $chaveEdicao = $rmNorm . '-' . $idInterclasseAtivo;

            $checkStmt = $conn->prepare('SELECT id_usuario FROM usuarios WHERE chave_usuario_edicao = ? AND id_usuario != ? LIMIT 1');
            if ($checkStmt) {
                $checkStmt->bind_param('si', $chaveEdicao, $idUsuario);
                $checkStmt->execute();
                if ($checkStmt->get_result()->fetch_assoc()) {
                    $checkStmt->close();
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Já existe outro aluno com este RM nesta edição.']);
                    break;
                }
                $checkStmt->close();
            }

            $sql = 'UPDATE usuarios SET nome_usuario = ?, matricula_usuario = ?, genero_usuario = ?, data_nasc_usuario = ?, chave_usuario_edicao = ? WHERE id_usuario = ? AND nivel_usuario = \'3\'';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('sssssi', $nome, $rmNorm, $genero, $dataNasc, $chaveEdicao, $idUsuario);

            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Aluno atualizado!']);
            } else {
                if ($stmt->errno === 1062) {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'RM já cadastrado nesta edição.']);
                } else {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
                }
            }
            $stmt->close();
            break;
        }

        if ($acao === 'excluir_aluno') {
            require_once __DIR__ . '/auth.php';
            requerNivel([0, 1, 2]);

            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do aluno inválido.']);
                break;
            }

            $checkStmt = $conn->prepare('SELECT nivel_usuario FROM usuarios WHERE id_usuario = ? AND nivel_usuario = \'3\'');
            $checkStmt->bind_param('i', $idUsuario);
            $checkStmt->execute();
            $target = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if (!$target) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Aluno não encontrado ou não pode ser removido.']);
                break;
            }

            $sql = "UPDATE usuarios SET status_usuario = '0' WHERE id_usuario = ? AND nivel_usuario = '3'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('i', $idUsuario);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Aluno removido.']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }

        if ($acao === 'excluir_colaborador') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['nivel']) || (int)$_SESSION['nivel'] !== 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Acesso negado. Apenas administradores podem remover colaboradores.']);
                break;
            }

            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do colaborador inválido.']);
                break;
            }

            $checkStmt = $conn->prepare('SELECT nivel_usuario FROM usuarios WHERE id_usuario = ?');
            $checkStmt->bind_param('i', $idUsuario);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $targetUser = $checkResult->fetch_assoc();
            $checkStmt->close();

            if (!$targetUser) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Usuário não encontrado.']);
                break;
            }
            if ($targetUser['nivel_usuario'] === '0') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Não é possível remover um administrador.']);
                break;
            }

            if (isset($_SESSION['id']) && (int)$_SESSION['id'] === $idUsuario) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Você não pode remover a própria conta.']);
                break;
            }
            
            $sql = "UPDATE usuarios SET status_usuario = '0'
                    WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('ii', $idUsuario, $idInterclasseAtivo);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador removido.']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }
        break;

    case 'PUT':
        $dadosPut = !empty($inputData) ? $inputData : $_GET;
        $idAlvo = (int) ($dadosPut['id_interclasse'] ?? 0);
        $novoStatus = (string) ($dadosPut['status_interclasse'] ?? '');

        if ($idAlvo <= 0 || !in_array($novoStatus, ['0', '1'], true)) {
            sgi_json_saida(['status' => 'erro', 'mensagem' => 'Informe id_interclasse e status_interclasse (0 ou 1).']);
            break;
        }

        $stmt = $conn->prepare("UPDATE interclasses SET status_interclasse = ? WHERE id_interclasse = ?");
        if (!$stmt) {
            sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
            break;
        }
        $stmt->bind_param('si', $novoStatus, $idAlvo);
        if ($stmt->execute()) {
            $acaoTxt = $novoStatus === '1' ? 'ativada' : 'encerrada';
            sgi_json_saida(['status' => 'sucesso', 'mensagem' => "Edição $acaoTxt. Usuários atualizados automaticamente."]);
        } else {
            sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
        }
        $stmt->close();
        break;
}