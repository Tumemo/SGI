<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Presentation\Http;

use App\Modules\Sincronizacao\Domain\MutationConflict;
use App\Modules\Sincronizacao\Domain\MutationIdentity;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use App\Shared\Http\Response;
use mysqli;

/** Compatibility adapter for the procedural HTTP handlers during extraction. */
final class IdempotencyResponder
{
    private static ?MysqliMutationStore $store = null;
    private static ?MutationIdentity $identity = null;

    public static function buscarRespostaIdempotente(mysqli $connection, string $route): ?array
    {
        try {
            self::$identity = MutationIdentity::create(
                (string) ($_SERVER['HTTP_X_SGI_MUTATION_ID'] ?? ''),
                (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
                (string) file_get_contents('php://input'),
            );
            self::$store = new MysqliMutationStore($connection);
            register_shutdown_function(static function (): void {
                self::$store?->cancel();
            });
            return self::$store->begin($route, self::$identity);
        } catch (MutationConflict $exception) {
            Response::json(['success' => false, 'message' => $exception->getMessage()], 409)->send();
            exit;
        } catch (\InvalidArgumentException $exception) {
            Response::json(['success' => false, 'message' => $exception->getMessage()], 400)->send();
            exit;
        }
    }

    public static function enviarRespostaIdempotente(mysqli $connection, string $route, int $status, array $payload): void
    {
        if (self::$store === null || self::$identity === null) {
            throw new \LogicException('Sincronização não iniciada.');
        }
        self::$store->complete($route, self::$identity, $status, $payload);
        Response::json($payload, $status)->send();
    }
}
