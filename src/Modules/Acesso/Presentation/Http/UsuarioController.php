<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Infrastructure\MysqliUsuarioGateway;
use App\Modules\Acesso\Application\UsuarioAdministrativoService;
use App\Modules\Acesso\Infrastructure\MysqliUsuarioAdministrativoRepository;
use App\Modules\Eventos\Domain\EdicaoRules;
use App\Modules\Eventos\Infrastructure\MysqliEdicaoConsulta;
use App\Modules\Participantes\Domain\MatriculaRules;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Http\SessionManager;

final class UsuarioController
{
    public function __construct(
        private readonly MysqliUsuarioGateway $gateway,
        private readonly \mysqli $connection,
        private readonly ?UsuarioAdministrativoService $administrative = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $action = (string) $request->query('acao', $request->input('acao', ''));
        try {
            if ($request->method() === 'GET') {
                if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(['status' => 'erro', 'mensagem' => 'Nenhuma edição ativa.']);
                }
                return match ($action) {
                    'listar_competidores' => Response::json($this->gateway->competitors((int) $request->query('id_turma', 0), (int) $request->query('id_interclasse', $edition), (string) $request->query('genero', ''))),
                    'listar_colaboradores' => Response::json($this->gateway->collaborators($edition)),
                    default => Response::json($this->gateway->allUsers($edition)),
                };
            }
            if ($request->method() === 'PUT') {
                if (($denied = AccessGuard::authorize([0, 1])) !== null) {
                    return $denied;
                }
                $data = $request->allInput();
                $this->gateway->setEditionStatus((int) ($data['id_interclasse'] ?? 0), (string) ($data['status_interclasse'] ?? ''));
                return Response::json(['status' => 'sucesso', 'mensagem' => 'Edição atualizada.']);
            }
            if ($request->method() !== 'POST') {
                return Response::json(['status' => 'erro', 'mensagem' => 'Método não permitido.'], 405);
            }
            $data = $request->allInput();
            if ($action === 'validar_inscricao') {
                $edition = MysqliEdicaoConsulta::buscarInterclasseAtivo($this->connection);
                if ($edition === null) {
                    return Response::json(EdicaoRules::erroSemInterclasseAtivo());
                }
                $birth = MatriculaRules::parseDataNascimento((string) ($data['data_nasc_usuario'] ?? ''));
                $registration = (string) ($data['matricula_usuario'] ?? $data['rm'] ?? $data['ra'] ?? '');
                $user = $birth === null ? null : $this->gateway->findCompetitorForValidation($registration, $birth, $edition);
                if ($user === null) {
                    return Response::json(['status' => 'erro', 'mensagem' => 'Não foi possível validar os dados informados.']);
                }
                SessionManager::start();
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['logado'] = true;
                $_SESSION['id'] = (int) $user['id_usuario'];
                $_SESSION['id_usuario'] = (int) $user['id_usuario'];
                $_SESSION['nivel'] = (string) $user['nivel_usuario'];
                $_SESSION['id_interclasse'] = $edition;
                OfflineSession::definirChaveCacheOfflineUsuario((int) $user['id_usuario'], (string) $user['senha_usuario']);
                $level = (string) $user['nivel_usuario'];
                return Response::json([
                    'status' => 'sucesso',
                    'mensagem' => 'Dados validados.',
                    'permissoes' => ['admin' => $level === '0', 'colaborador' => $level === '1', 'mesario' => $level === '2', 'competidor' => $level === '3'],
                    'dados' => ['id_usuario' => (int) $user['id_usuario'], 'nome' => $user['nome_usuario'], 'sigla' => $user['sigla_usuario']],
                ]);
            }
            if ($action === 'criar_aluno') {
                if (($denied = AccessGuard::authorize([0, 1])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(['status' => 'erro', 'mensagem' => 'Nenhuma edição ativa.']);
                }
                return Response::json($this->gateway->createStudent($data, $edition));
            }
            if ($action === 'cadastrar_usuario') {
                if (($denied = AccessGuard::authorize([0])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(EdicaoRules::erroSemInterclasseAtivo());
                }
                return Response::json($this->gateway->createStaff($data, $edition, is_array($request->file('foto')) ? $request->file('foto') : null));
            }
            if ($action === 'atualizar_colaborador' || $action === 'atualizar_dados_colaborador') {
                if (($denied = AccessGuard::authorize([0])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(EdicaoRules::erroSemInterclasseAtivo());
                }
                if ($action === 'atualizar_colaborador') {
                    $this->gateway->updateStaffRole($data, $edition);
                } else {
                    $this->gateway->updateStaffDetails($data, $edition);
                }
                return Response::json(['status' => 'sucesso', 'mensagem' => 'Colaborador atualizado.']);
            }
            if ($action === 'editar_aluno') {
                if (($denied = AccessGuard::authorize([0, 1])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(EdicaoRules::erroSemInterclasseAtivo());
                }
                $this->gateway->updateStudent($data, $edition);
                return Response::json(['status' => 'sucesso', 'mensagem' => 'Aluno atualizado!']);
            }
            if (in_array($action, ['excluir_aluno', 'resetar_senha_aluno', 'excluir_colaborador'], true)) {
                if (($denied = AccessGuard::authorize([0])) !== null) {
                    return $denied;
                }
                if ($this->administrative === null) {
                    return Response::json(['status' => 'erro', 'mensagem' => 'Serviço administrativo indisponível.'], 500);
                }
                $id = (int) ($data['id_usuario'] ?? 0);
                if ($action === 'excluir_aluno') {
                    $this->administrative->excluirAluno($id);
                    return Response::json(['status' => 'sucesso', 'mensagem' => 'Aluno removido.']);
                }
                if ($action === 'resetar_senha_aluno') {
                    $this->administrative->resetarSenhaAluno($id);
                    return Response::json(['status' => 'sucesso', 'mensagem' => 'Senha do aluno resetada para o padrão (123).']);
                }
                $this->administrative->excluirColaborador($id, $this->gateway->activeEdition(), (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0));
                return Response::json(['status' => 'sucesso', 'mensagem' => 'Colaborador removido.']);
            }
            if ($request->query('id', null) !== null) {
                if (($denied = AccessGuard::authorize([0, 1])) !== null) {
                    return $denied;
                }
                $edition = $this->gateway->activeEdition();
                if ($edition === null) {
                    return Response::json(['status' => 'erro', 'mensagem' => 'Nenhuma edição ativa.']);
                }
                $this->gateway->assignStudent((int) $request->query('id'), (int) ($data['turmas_id_turma'] ?? 0), (int) ($data['interclasses_id_interclasse'] ?? $edition));
                return Response::json(['status' => 'sucesso', 'mensagem' => 'Aluno atualizado.']);
            }
            if ($action === 'cadastrar_competidores') {
                if (($denied = AccessGuard::authorize([0, 1])) !== null) {
                    return $denied;
                }
                return Response::json(\App\Modules\Participantes\Infrastructure\ImportacaoArquivo::importarCompetidores($this->connection));
            }
            return Response::json(['status' => 'erro', 'mensagem' => 'Ação inválida.'], 400);
        } catch (\Throwable $exception) {
            error_log('Falha ao processar usuário: ' . $exception->getMessage());
            return Response::json(['status' => 'erro', 'mensagem' => $exception->getMessage()], 400);
        }
    }

}
