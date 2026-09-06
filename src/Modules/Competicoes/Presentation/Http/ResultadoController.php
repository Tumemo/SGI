<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ResultadoController
{
    public function __construct(
        private readonly MysqliPartidaGateway $gateway,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
            return $denied;
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $data = $request->allInput();
        if (!isset($data['id_jogo'], $data['resultados']) || !is_array($data['resultados'])) {
            return Response::json(['success' => false, 'message' => 'Dados insuficientes.'], 400);
        }
        if ((int) ($_SESSION['nivel'] ?? -1) === 2 && (int) $data['id_jogo'] > 0
            && !$this->belongsToActiveEdition((int) $data['id_jogo'])) {
            return Response::json(['success' => false, 'message' => 'O jogo não pertence à edição ativa.'], 403);
        }
        return $this->mutations->run($request, 'lancar_resultado', function () use ($data): Response {
            try {
                $result = $this->gateway->launch(
                    (int) $data['id_jogo'],
                    isset($data['nome_jogo']) ? (string) $data['nome_jogo'] : null,
                    (int) ($data['id_modalidade'] ?? 0),
                    array_values(array_filter($data['resultados'], 'is_array')),
                );
                return Response::json($result);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
            } catch (\RuntimeException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
            } catch (\Throwable $exception) {
                error_log('Falha ao lançar resultado: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
            }
        });
    }

    private function belongsToActiveEdition(int $gameId): bool
    {
        $active = (int) ($_SESSION['id_interclasse'] ?? 0);
        $edition = $this->gateway->editionOfGame($gameId);
        return $active > 0 && $edition !== null && $edition === $active;
    }
}
