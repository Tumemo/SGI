<?php

declare(strict_types=1);

namespace App\Modules\Disciplina\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Disciplina\Application\OcorrenciaService;
use App\Modules\Disciplina\Infrastructure\MysqliOcorrenciaQueries;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class OcorrenciaController
{
    public function __construct(
        private readonly OcorrenciaService $service,
        private readonly MysqliOcorrenciaQueries $queries,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        try {
            if ($request->method() === 'GET') {
                return Response::json($this->queries->list($request->allQuery()));
            }
            if (($denied = $this->access->authorize()) !== null) {
                return $denied;
            }
            if ($request->method() === 'POST') {
                return $this->mutations->run($request, 'ocorrencias.post', function () use ($request): Response {
                    $data = $request->allInput();
                    if (!isset($data['titulo_ocorrencia'], $data['descricao_ocorrencia'], $data['data_ocorrencia'], $data['usuarios_id_usuario'])) {
                        return Response::json(['success' => false, 'message' => 'Dados incompletos.'], 400);
                    }
                    $game = (int) ($data['id_jogo'] ?? 0);
                    $resolved = $this->queries->resolveGame($game, (string) ($data['nome_jogo'] ?? ''), (int) ($data['id_modalidade'] ?? 0));
                    if ($game < 0 && $resolved <= 0) {
                        return Response::json(['success' => false, 'message' => 'A partida temporária ainda não foi materializada. O registro continuará na fila para evitar perda de dados.'], 409);
                    }
                    $data['id_jogo'] = $resolved;
                    $result = $this->service->registrar($data);
                    $payload = ['success' => true, 'message' => 'Ocorrência registrada com sucesso!', 'id' => $result['id']];
                    if ($result['evento'] !== null) {
                        $payload['evento'] = $result['evento'];
                    }
                    return Response::json($payload, 201);
                });
            }
            if ($request->method() === 'PUT') {
                $this->service->atualizar($request->allInput());
                return Response::json(['success' => true, 'message' => 'Ocorrência atualizada com sucesso!']);
            }
            return Response::json(['success' => false, 'message' => 'Método não permitido'], 405);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (\Throwable $exception) {
            error_log('Falha ao processar ocorrência: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => $request->method() === 'PUT' ? 'Não foi possível atualizar ocorrência.' : 'Não foi possível registrar ocorrência.'], 500);
        }
    }
}
