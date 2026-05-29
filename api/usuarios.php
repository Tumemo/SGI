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
 * Função centralizada para processar upload de foto
 */
function sgi_upload_foto_usuario(?array $file, string $matricula): string 
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return 'default.jpg';
    }

    $pastaDestino = dirname(__DIR__) . '/uploads/fotosUsuarios/';
    if (!is_dir($pastaDestino)) {
        mkdir($pastaDestino, 0755, true);
    }

    $ext = pathinfo((string) $file['name'], PATHINFO_EXTENSION);
    $nomeFoto = 'user_' . $matricula . '_' . (string) time() . '.' . $ext;
    $destino = $pastaDestino . $nomeFoto;

    if (move_uploaded_file($file['tmp_name'], $destino)) {
        return $nomeFoto;
    }

    return 'default.jpg';
}

/**
 * RF05: validação RA + data
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
    $_SESSION['matricula'] = $usuario['matricula_usuario']; // Guardado para upload posterior

    return [
        'status' => 'sucesso',
        'mensagem' => 'Dados validados.',
        'permissoes' => [
            'nivel_usuario' => $usuario['nivel_usuario']
        ],
        'dados' => [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => $usuario['nome_usuario'],
            'sigla' => $usuario['sigla_usuario'],
            'foto' => $usuario['foto_usuario'] ?? 'default.jpg'
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
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID da turma é obrigatório.']);
                break;
            }

            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, foto_usuario
                    FROM usuarios WHERE turmas_id_turma = ? AND interclasses_id_interclasse = ? AND nivel_usuario = '3' AND status_usuario = '1'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $id_turma, $idInterclasseAtivo);
            $stmt->execute();
            sgi_json_saida(['status' => 'sucesso', 'competidores' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        } 
        
        elseif ($acao === 'listar_colaboradores') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario, foto_usuario
                    FROM usuarios
                    WHERE interclasses_id_interclasse = ?
                      AND (nivel_usuario = '0' OR nivel_usuario = '1' OR nivel_usuario = '2')
                      AND status_usuario = '1'
                    ORDER BY nome_usuario ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idInterclasseAtivo);
            $stmt->execute();
            sgi_json_saida(['status' => 'sucesso', 'colaboradores' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        } 
        
        elseif ($acao === '' || $acao === 'listar_por_turma') {
            $id_turma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : 0;
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, foto_usuario
                    FROM usuarios
                    WHERE interclasses_id_interclasse = ? AND turmas_id_turma = ? AND status_usuario = '1'
                    ORDER BY nome_usuario ASC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $idInterclasseAtivo, $id_turma);
            $stmt->execute();
            sgi_json_saida(['status' => 'sucesso', 'usuarios' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        }
        break;

    case 'POST':
        if ($acao === 'cadastrar_competidores') {
            sgi_json_saida(importarCompetidores($conn));
        } 
        
        elseif ($acao === 'validar_inscricao') {
            $dados = !empty($_POST) ? $_POST : $inputData;
            sgi_json_saida(sgi_validar_inscricao_rf05($conn, $dados));
        }

        // AÇÃO NOVA: Usuário logado atualiza sua própria foto
        elseif ($acao === 'atualizar_perfil') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['id_usuario'])) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Sessão expirada.']);
                break;
            }

            $idUsuario = (int)$_SESSION['id_usuario'];
            $matricula = $_SESSION['matricula'] ?? 'user';

            if (isset($_FILES['foto'])) {
                $nomeFoto = sgi_upload_foto_usuario($_FILES['foto'], $matricula);
                $stmt = $conn->prepare("UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?");
                $stmt->bind_param('si', $nomeFoto, $idUsuario);
                
                if ($stmt->execute()) {
                    sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Foto atualizada!', 'foto' => $nomeFoto]);
                } else {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Erro ao salvar no banco.']);
                }
            }
        }

        elseif ($acao === 'cadastrar_usuario') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }

            $dados = !empty($_POST) ? $_POST : $inputData;
            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $senhaCrua = (string) ($dados['senha_usuario'] ?? '');
            $nivel = (string) ($dados['nivel_usuario'] ?? '3');
            $genero = (string) ($dados['genero_usuario'] ?? '');
            $sigla = (string) ($dados['sigla_usuario'] ?? 'SS');
            $dataNascInput = trim((string) ($dados['data_nasc_usuario'] ?? ''));
            $dataNasc = sgi_parse_data_nascimento($dataNascInput) ?? $dataNascInput;

            // Uso da função de foto
            $nomeFotoBanco = sgi_upload_foto_usuario($_FILES['foto'] ?? null, $matricula);

            if ($nome === '' || $matricula === '' || $senhaCrua === '') {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Campos incompletos']);
                break;
            }

            $senhaHash = password_hash($senhaCrua, PASSWORD_DEFAULT);
            $matriculaNorm = sgi_normalizar_ra($matricula) ?: $matricula;
            $chaveEdicao = $matriculaNorm . '-' . $idInterclasseAtivo;

            $sql = 'INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, genero_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario, interclasses_id_interclasse, chave_usuario_edicao) VALUES (?, ?, ?, ?, ?, ?, "1", ?, ?, ?, ?)';

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssis', $nome, $matriculaNorm, $senhaHash, $nivel, $genero, $nomeFotoBanco, $dataNasc, $sigla, $idInterclasseAtivo, $chaveEdicao);

            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Usuário cadastrado!']);
            } else {
                $msg = ($stmt->errno === 1062) ? 'Matrícula já vinculada a esta edição.' : $stmt->error;
                sgi_json_saida(['status' => 'erro', 'mensagem' => $msg]);
            }
            $stmt->close();
        }

        elseif ($acao === 'atualizar_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            $isAdmin = (($dados['is_admin_clicado'] ?? '0') === '1');
            $nivel = $isAdmin ? '1' : '0';
            $mesario = $isAdmin ? '1' : (string) ($dados['is_mesario_clicado'] ?? '0');

            $sql = "UPDATE usuarios SET nivel_usuario = ?, mesario_usuario = ? 
                    WHERE id_usuario = ? AND interclasses_id_interclasse = ? AND competidor_usuario = '0'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssii', $nivel, $mesario, $idUsuario, $idInterclasseAtivo);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador atualizado.']);
            }
            $stmt->close();
        }

        elseif ($acao === 'excluir_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);

            $sql = "UPDATE usuarios SET status_usuario = '0' 
                    WHERE id_usuario = ? AND interclasses_id_interclasse = ? AND competidor_usuario = '0'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $idUsuario, $idInterclasseAtivo);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador removido.']);
            }
            $stmt->close();
        }
        break;

    case 'PUT':
        $idAlvo = (int) ($inputData['id_interclasse'] ?? 0);
        $novoStatus = (string) ($inputData['status_interclasse'] ?? '');

        $stmt = $conn->prepare("UPDATE interclasses SET status_interclasse = ? WHERE id_interclasse = ?");
        $stmt->bind_param('si', $novoStatus, $idAlvo);
        if ($stmt->execute()) {
            sgi_json_saida(['status' => 'sucesso', 'mensagem' => "Status atualizado."]);
        }
        $stmt->close();
        break;
}