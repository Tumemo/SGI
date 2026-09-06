<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\JogoConflitoException;
use App\Modules\Competicoes\Application\JogoService;
use App\Modules\Competicoes\Infrastructure\MysqliJogoGateway;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class JogoController
{
    public function __construct(
        private readonly JogoService $service,
        private readonly MysqliJogoGateway $queries,
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
                error_log('Falha ao listar jogos: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível consultar jogos.'], 500);
            }
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        if ($request->method() === 'POST') {
            try {
                $data = $request->allInput();
                if (!$this->resourceBelongsToActiveEdition((int) ($data['modalidades_id_modalidade'] ?? 0), false)) {
                    return Response::json(['success' => false, 'message' => 'O jogo não pertence à edição ativa.'], 403);
                }
                $id = $this->service->agendar($data);
                return Response::json(['success' => true, 'message' => 'Jogo cadastrado com sucesso!', 'id' => $id, 'id_jogo' => $id], 201);
            } catch (JogoConflitoException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
            } catch (\Throwable $exception) {
                error_log('Falha ao criar jogo: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível criar jogo.'], 500);
            }
        }
        if ($request->method() === 'PUT') {
            $data = $request->allInput();
            $id = (int) ($data['id_jogo'] ?? 0);
            if ($id < 0) {
                return Response::json(['success' => true, 'offline' => true, 'message' => 'Jogo temporário offline registrado.']);
            }
            if ($id <= 0) {
                return Response::json(['success' => false, 'message' => 'O ID do jogo é obrigatório.'], 400);
            }
            if (!$this->resourceBelongsToActiveEdition($id, true)) {
                return Response::json(['success' => false, 'message' => 'O jogo não pertence à edição ativa.'], 403);
            }
            if ((int) ($_SESSION['nivel'] ?? -1) === 2
                && (isset($data['data_jogo']) || isset($data['locais_id_local']) || isset($data['modalidades_id_modalidade']))) {
                return Response::json(['success' => false, 'message' => 'Mesários só podem alterar o status ou placar do jogo.'], 403);
            }
            try {
                if (!$this->queries->update($id, $data)) {
                    return Response::json(['success' => true, 'offline' => true, 'message' => 'Jogo temporário registrado localmente.']);
                }
                return Response::json(['success' => true, 'message' => 'Jogo atualizado com sucesso!']);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
            } catch (\Throwable $exception) {
                error_log('Falha ao atualizar jogo: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível atualizar jogo.'], 500);
            }
        }
        return Response::json(['success' => false, 'message' => 'Método não permitido'], 405);
    }

    private function resourceBelongsToActiveEdition(int $id, bool $game): bool
    {
        if ((int) ($_SESSION['nivel'] ?? -1) !== 2) {
            return true;
        }
        $active = (int) ($_SESSION['id_interclasse'] ?? 0);
        if ($active <= 0 || $id <= 0) {
            return false;
        }
        $edition = $game ? $this->queries->editionOfGame($id) : $this->queries->editionOfModality($id);
        return $edition !== null && $edition === $active;
    }
}
