<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\ArtilheiroService;
use App\Modules\Competicoes\Domain\ChaveamentoRules;
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
            if ((int) ($_SESSION['nivel'] ?? -1) === 3) {
                return Response::json([
                    'success' => false,
                    'message' => 'A artilharia será liberada após a premiação.',
                ], 403);
            }
            $filters = $request->allQuery();
            if ((int) ($_SESSION['nivel'] ?? -1) === 2) {
                if ((int) ($_SESSION['id_interclasse'] ?? 0) <= 0) {
                    return Response::json(['success' => false, 'message' => 'Nenhuma edição ativa.'], 403);
                }
                $filters['id_interclasse'] = (int) ($_SESSION['id_interclasse'] ?? 0);
            }
            return Response::json($this->queries->list($filters));
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $action = function () use ($request): Response {
            $data = $request->allInput();
            if (!isset($data['usuarios_id_usuario'], $data['jogos_id_jogo'], $data['num_gol'])) {
                return Response::json(['success' => false, 'message' => 'Dados incompletos.'], 400);
            }
            $submittedGame = (int) $data['jogos_id_jogo'];
            if ($submittedGame < 0) {
                $tag = trim((string) ($data['nome_jogo'] ?? ''));
                $edition = $this->queries->editionOfModality((int) ($data['id_modalidade'] ?? 0));
                if ($edition === null) {
                    return Response::json(['success' => false, 'message' => 'Modalidade não encontrada.'], 404);
                }
                if (ChaveamentoRules::parse($tag) === null) {
                    return Response::json(['success' => false, 'message' => 'A tag do jogo temporário é inválida.'], 422);
                }
                if (($denied = $this->access->authorize($edition)) !== null) {
                    return $denied;
                }
            }
            $id = $this->queries->resolveGame((object) $data);
            if ($id <= 0) {
                return Response::json(['success' => false, 'message' => 'A partida temporária ainda não foi materializada. O gol continuará na fila para evitar perda de dados.'], 409);
            }

            $edition = $this->service->editionOfGame($id);
            if ($edition === null) {
                return Response::json(['success' => false, 'message' => 'Jogo não encontrado.'], 404);
            }
            if (($denied = $this->access->authorize($edition)) !== null) {
                return $denied;
            }

            $userId = (int) $data['usuarios_id_usuario'];
            $role = $this->service->roleOfUser($userId);
            if ($role === null) {
                return Response::json(['success' => false, 'message' => 'Atleta não encontrado.'], 404);
            }
            if ($role === 3) {
                $userEdition = $this->service->editionOfUser($userId);
                if ($userEdition === null || $userEdition !== $edition) {
                    return Response::json(['success' => false, 'message' => 'Atleta e jogo não pertencem à mesma edição.'], 422);
                }
                if (!$this->service->athleteParticipatesInGame($userId, $id)) {
                    return Response::json(['success' => false, 'message' => 'Atleta não participa deste jogo.'], 422);
                }
            }

            try {
                if ($request->method() === 'POST') {
                    $created = $this->service->registrar($userId, $id, (int) $data['num_gol']);
                    return Response::json(['success' => true, 'message' => 'Gols registrados com sucesso!', 'id' => $created]);
                }
                $this->service->atualizar($userId, $id, (int) $data['num_gol']);
                return Response::json(['success' => true, 'message' => 'Artilharia atualizada!']);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
            }
        };
        return $this->mutations->run(
            $request,
            $request->method() === 'POST' ? 'artilheiro.post' : 'artilheiro.put',
            $action,
        );
    }
}
