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

            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario
                    FROM usuarios WHERE turmas_id_turma = ? AND interclasses_id_interclasse = ? AND competidor_usuario = '1' AND status_usuario = '1'";
            try {
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new RuntimeException('Falha ao preparar consulta: ' . $conn->error);
                }
                $stmt->bind_param('ii', $id_turma, $idInterclasseAtivo);
                $stmt->execute();
                $res = $stmt->get_result();
                $lista = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                sgi_json_saida(['status' => 'sucesso', 'competidores' => $lista]);
            } catch (Throwable $e) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'Falha ao listar competidores: ' . $e->getMessage()]);
            }
        } elseif ($acao === 'listar_colaboradores') {
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario, mesario_usuario
                    FROM usuarios
                    WHERE interclasses_id_interclasse = ?
                      AND competidor_usuario = '0'
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
            $sql = "SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario
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
            $nivel = $isAdmin ? '1' : '0';
            $mesario = $isAdmin ? '1' : (string) ($dados['is_mesario_clicado'] ?? '0');
            $forcarColaborador = (($dados['competidor_usuario'] ?? null) === '0');
            $competidor = $forcarColaborador || $isAdmin ? '0' : '1';
            $sigla = $competidor === '0' ? 'SS' : (string) ($dados['sigla_usuario'] ?? 'RM');
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
            $chaveEdicao = $matriculaNorm . '-' . $idInterclasseAtivo;

            $sql = 'INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, competidor_usuario, mesario_usuario, genero_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario, interclasses_id_interclasse, chave_usuario_edicao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }

            $stmt->bind_param(
                'sssssssssssis',
                $nome,
                $matriculaNorm,
                $senhaHash,
                $nivel,
                $competidor,
                $mesario,
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
                    sgi_json_saida(['status' => 'erro', 'mensagem' => 'Essa matrícula já está cadastrada nesta edição do interclasse.']);
                } else {
                    sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
                }
            }
            $stmt->close();
            break;
        }

        if ($acao === 'atualizar_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do colaborador inválido.']);
                break;
            }
            $isAdmin = (($dados['is_admin_clicado'] ?? '0') === '1');
            $nivel = $isAdmin ? '1' : '0';
            $mesario = $isAdmin ? '1' : (string) ($dados['is_mesario_clicado'] ?? '0');
            $sql = "UPDATE usuarios SET nivel_usuario = ?, mesario_usuario = ?
                    WHERE id_usuario = ? AND interclasses_id_interclasse = ? AND competidor_usuario = '0'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $conn->error]);
                break;
            }
            $stmt->bind_param('ssii', $nivel, $mesario, $idUsuario, $idInterclasseAtivo);
            if ($stmt->execute()) {
                sgi_json_saida(['status' => 'sucesso', 'mensagem' => 'Colaborador atualizado.']);
            } else {
                sgi_json_saida(['status' => 'erro', 'mensagem' => $stmt->error]);
            }
            $stmt->close();
            break;
        }

        if ($acao === 'excluir_colaborador') {
            $idInterclasseAtivo = buscarInterclasseAtivo($conn);
            if ($idInterclasseAtivo === null) {
                sgi_json_saida(erroSemInterclasseAtivo());
                break;
            }
            $dados = !empty($_POST) ? $_POST : $inputData;
            $idUsuario = (int) ($dados['id_usuario'] ?? 0);
            if ($idUsuario <= 0) {
                sgi_json_saida(['status' => 'erro', 'mensagem' => 'ID do colaborador inválido.']);
                break;
            }
            $sql = "UPDATE usuarios SET status_usuario = '0'
                    WHERE id_usuario = ? AND interclasses_id_interclasse = ? AND competidor_usuario = '0'";
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
        // Encerrar edição: desativa o interclasse e o trigger automaticamente desativa os usuários
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
