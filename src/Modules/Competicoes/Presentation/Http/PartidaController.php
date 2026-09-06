<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\PartidaService;
use App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class PartidaController
{
    public function __construct(
        private readonly PartidaService $service,
        private readonly MysqliPartidaGateway $queries,
        private readonly CompetitionAccess $access,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() === 'GET') {
            if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
                return $denied;
            }
            try {
                return Response::json($this->queries->list($request->allQuery()));
            } catch (\Throwable $exception) {
                error_log('Falha ao listar partidas: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível consultar partidas.'], 500);
            }
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $data = $request->allInput();
        $id = $data['id_partida'] ?? null;
        if ($request->method() === 'POST') {
            if (!is_numeric($id) || (int) $id <= 0) {
                return Response::json(['success' => true, 'offline' => true, 'message' => 'Partida temporária sincronizada']);
            }
            if (!array_key_exists('resultado_final', $data)) {
                return Response::json(['success' => false, 'message' => 'Dados incompletos.'], 400);
            }
            if ((int) ($_SESSION['nivel'] ?? -1) === 2
                && !$this->belongsToActiveEdition((int) ($data['jogos_id_jogo'] ?? 0))) {
                return Response::json(['success' => false, 'message' => 'A partida não pertence à edição ativa.'], 403);
            }
            try {
                $this->queries->launch((int) ($data['jogos_id_jogo'] ?? 0), null, 0, [[
                    'id_equipe' => (int) ($data['equipes_id_equipe'] ?? 0),
                    'gols' => (int) $data['resultado_final'],
                ]]);
                return Response::json(['success' => true, 'message' => 'Resultado salvo e jogo finalizado!']);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
            } catch (\Throwable $exception) {
                error_log('Falha ao atualizar resultado da partida: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível atualizar a partida.'], 500);
            }
        }
        if ($request->method() === 'PUT') {
            if (!is_numeric($id) || (int) $id <= 0) {
                return Response::json(['success' => true, 'offline' => true, 'message' => 'Partida temporária sincronizada']);
            }
            if ((int) ($_SESSION['nivel'] ?? -1) === 2
                && !$this->belongsToActiveEdition((int) ($data['jogos_id_jogo'] ?? 0))) {
                return Response::json(['success' => false, 'message' => 'A partida não pertence à edição ativa.'], 403);
            }
            try {
                $this->service->atualizar($data);
                return Response::json(['success' => true, 'message' => 'Partida atualizada com sucesso!']);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
            } catch (\Throwable $exception) {
                error_log('Falha ao atualizar partida: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível atualizar a partida.'], 500);
            }
        }
        return Response::json(['message' => 'Método não permitido'], 405);
    }

    private function belongsToActiveEdition(int $gameId): bool
    {
        $active = (int) ($_SESSION['id_interclasse'] ?? 0);
        $edition = $gameId > 0 ? $this->queries->editionOfGame($gameId) : null;
        return $active > 0 && $edition !== null && $edition === $active;
    }
}
