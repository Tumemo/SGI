<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/includes/usuario_validacao.php';
require_once __DIR__ . '/includes/importador_competidores.php';

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

    $matriculaBruta = trim((string) ($dados['matricula_usuario'] ?? $dados['rm'] ?? $dados['ra'] ?? ''));
    $ra = sgi_normalizar_ra($matriculaBruta);
    $dataYmd = sgi_parse_data_nascimento($dados['data_nasc_usuario'] ?? '');

    if ($ra === '' || $dataYmd === null) {
        return ['status' => 'erro', 'mensagem' => 'Não foi possível validar os dados informados.'];
    }

    $usuario = sgi_buscar_competidor_por_ra_e_data($conn, $ra, $dataYmd);
    if ($usuario === null) {
        return ['status' => 'erro', 'mensagem' => 'Não foi possível validar os dados informados.'];
    }

    $_SESSION['logado'] = true;
    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['nivel'] = $usuario['nivel_usuario'];

    return [
        'status' => 'sucesso',
        'mensagem' => 'Dados validados.',
        'permissoes' => [
            'admin' => (int) $usuario['nivel_usuario'] > 0,
            'mesario' => (int) $usuario['mesario_usuario'] === 1,
            'competidor' => (int) $usuario['competidor_usuario'] === 1,
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
        if ($acao === 'listar_competidores' || $acao === '') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario
                    FROM usuarios WHERE status_usuario = '1'";
            $res = $conn->query($sql);
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
            $dados = !empty($_POST) ? $_POST : $inputData;

            $nome = trim((string) ($dados['nome_usuario'] ?? ''));
            $matricula = trim((string) ($dados['matricula_usuario'] ?? ''));
            $senhaCrua = (string) ($dados['senha_usuario'] ?? '');
            $dataNascInput = trim((string) ($dados['data_nasc_usuario'] ?? ''));
            $dataNasc = sgi_parse_data_nascimento($dataNascInput) ?? $dataNascInput;

            $isAdmin = (($dados['is_admin_clicado'] ?? '0') === '1');
            $nivel = $isAdmin ? '1' : '0';
            $mesario = $isAdmin ? '1' : (string) ($dados['is_mesario_clicado'] ?? '0');
            $competidor = $isAdmin ? '0' : '1';
            $sigla = $competidor === '0' ? 'SS' : (string) ($dados['sigla_usuario'] ?? 'RM');

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

            $sql = 'INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, competidor_usuario, mesario_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }

            $stmt->bind_param(
                'ssssssssss',
                $nome,
                $matricula,
                $senhaHash,
                $nivel,
                $competidor,
                $mesario,
                $nomeFotoBanco,
                $status,
                $dataNasc,
                $sigla
            );

            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Usuário cadastrado!']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }
        break;

    case 'PUT':
        if (date('d/m') === '01/01') {
            $ano = (int) date('Y');
            $sql = "UPDATE usuarios SET matricula_usuario = (" . $ano . " * 1000000) + matricula_usuario, nome_usuario = CONCAT(nome_usuario, ' (', " . $ano . ", ')'), senha_usuario = CONCAT(senha_usuario, '_old'), status_usuario = '0' WHERE status_usuario = '1'";
            if ($conn->query($sql)) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Reset concluído']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
            }
        } else {
            sgi_json_saida(['status' => 'erro', 'mensagem' => 'Apenas em 01/01']);
        }
        break;
}
