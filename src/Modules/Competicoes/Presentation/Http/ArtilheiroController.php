<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\ArtilheiroService;
use App\Modules\Competicoes\Infrastructure\MysqliArtilheiroQueries;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ArtilheiroController
{
    public function __construct(
        private readonly ArtilheiroService $service,
        private readonly MysqliArtilheiroQueries $queries,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        if ($request->method() === 'GET') {
            return Response::json($this->queries->list($request->allQuery()));
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $action = function () use ($request): Response {
            $data = $request->allInput();
            if (!isset($data['usuarios_id_usuario'], $data['jogos_id_jogo'], $data['num_gol'])) {
                return Response::json(['success' => false, 'message' => 'Dados incompletos.'], 400);
            }
            $id = $this->queries->resolveGame((object) $data);
            if ($id <= 0) {
                return Response::json(['success' => false, 'message' => 'A partida temporária ainda não foi materializada. O gol continuará na fila para evitar perda de dados.'], 409);
            }
            try {
                if ($request->method() === 'POST') {
                    $created = $this->service->registrar((int) $data['usuarios_id_usuario'], $id, (int) $data['num_gol']);
                    return Response::json(['success' => true, 'message' => 'Gols registrados com sucesso!', 'id' => $created]);
                }
                $this->service->atualizar((int) $data['usuarios_id_usuario'], $id, (int) $data['num_gol']);
                return Response::json(['success' => true, 'message' => 'Artilharia atualizada!']);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
            }
        };
        return $request->method() === 'POST' ? $this->mutations->run($request, 'artilheiro.post', $action) : $action();
    }
}
