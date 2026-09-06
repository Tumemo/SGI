<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Disciplina\Application\OcorrenciaTurmaNaoEncontradaException;
use App\Modules\Disciplina\Application\OcorrenciaTurmaService;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class OcorrenciaTurmaController
{
    public function __construct(
        private readonly OcorrenciaTurmaService $service,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() === 'OPTIONS') {
            return new Response('', 204);
        }
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        try {
            if ($request->method() === 'GET') {
                $edition = (int) $request->query('id_interclasse', 0);
                return Response::json($edition > 0 ? $this->service->listar([
                    'id_interclasse' => $edition,
                    'id_turma' => (int) $request->query('id_turma', 0),
                ]) : []);
            }
            if (($denied = $this->access->authorize()) !== null) {
                return $denied;
            }
            if ($request->method() === 'POST') {
                return $this->mutations->run($request, 'ocorrencias_turmas.post', function () use ($request): Response {
                    $data = $request->allInput();
                    if ((int) $_SESSION['nivel'] === 2
                        && !empty($data['interclasses_id_interclasse'])
                        && (int) $data['interclasses_id_interclasse'] !== (int) $_SESSION['id_interclasse']) {
                        return Response::json(['success' => false, 'message' => 'Mesários só podem registrar ocorrências na edição ativa.'], 403);
                    }
                    $data['usuarios_id_usuario'] ??= (int) ($_SESSION['id_usuario'] ?? 0);
                    $id = $this->service->registrar($data);
                    return Response::json(['success' => true, 'message' => 'Ocorrência registrada!', 'id' => $id], 201);
                });
            }
            if ($request->method() === 'DELETE') {
                $this->service->excluir((int) $request->input('id_ocorrencia_turma', $request->query('id_ocorrencia_turma', 0)));
                return Response::json(['success' => true, 'message' => 'Ocorrência removida!']);
            }
            return Response::json(['success' => false, 'message' => 'Método não permitido'], 405);
        } catch (OcorrenciaTurmaNaoEncontradaException) {
            return Response::json(['success' => false, 'message' => 'Ocorrência não encontrada.'], 404);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (\Throwable $exception) {
            error_log('Falha ao processar ocorrência da turma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível processar a ocorrência da turma.'], 500);
        }
    }
}
