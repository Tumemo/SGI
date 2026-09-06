<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Presentation\Http;

use App\Modules\Sincronizacao\Domain\MutationConflict;
use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class MutationAction
{
    public function __construct(private readonly MysqliMutationStore $store)
    {
    }

    /** @param callable():Response $action */
    public function run(Request $request, string $route, callable $action): Response
    {
        try {
            $identity = MutationIdentity::create(
                (string) $request->header('X-SGI-Mutation-Id', ''),
                (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
                $request->rawBody(),
            );
            $previous = $this->store->begin($route, $identity);
            if ($previous !== null) {
                return Response::json($previous['payload'], $previous['status']);
            }
            $response = $action();
            if ($response->status() < 400) {
                $this->store->complete($route, $identity, $response->status(), json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR));
            }
            return $response;
        } catch (MutationConflict $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 409);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } finally {
            $this->store->cancel();
        }
    }
}
