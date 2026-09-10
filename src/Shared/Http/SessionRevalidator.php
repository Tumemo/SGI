<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Database\ConnectionFactory;
use RuntimeException;

/** Revalidates the identity stored in the PHP session. */
final class SessionRevalidator
{
    private function __construct()
    {
    }

    public static function valid(): bool
    {
        SessionManager::start();
        $id = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $connection = ConnectionFactory::get();
        $statement = $connection->prepare(
            "SELECT u.nivel_usuario, u.status_usuario, u.auth_version,
                    CASE
                        WHEN u.nivel_usuario <> '3' THEN 1
                        WHEN EXISTS (
                            SELECT 1
                            FROM usuarios_has_interclasses ui
                            WHERE ui.usuarios_id_usuario = u.id_usuario
                              AND ui.interclasses_id_interclasse = u.interclasses_id_interclasse
                              AND ui.aceito_termo = 'sim'
                        ) THEN 1
                        ELSE 0
                    END AS termo_aceito
             FROM usuarios u
             WHERE u.id_usuario = ? LIMIT 1",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a sessão.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a sessão.');
        }
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        if ($row === null || (string) ($row['status_usuario'] ?? '0') !== '1') {
            return false;
        }

        $sessionLevel = (int) ($_SESSION['nivel'] ?? -1);
        $sessionVersion = (int) ($_SESSION['auth_version'] ?? 0);
        $valid = $sessionLevel === (int) ($row['nivel_usuario'] ?? -1)
            && $sessionVersion > 0
            && $sessionVersion === (int) ($row['auth_version'] ?? 0);
        if (!$valid) {
            return false;
        }

        // O aceite é uma autorização de negócio, não um dado confiável do
        // navegador. Recalcule-o em toda requisição protegida para que uma
        // sessão antiga nunca mantenha acesso depois de perder o aceite.
        $_SESSION['termo_aceito'] = $sessionLevel !== 3
            || (int) ($row['termo_aceito'] ?? 0) === 1;

        // The active edition may change while a mesário keeps the browser
        // open. Refresh that operational context on every protected request
        // instead of trusting the value captured at login time.
        if ($sessionLevel === 2) {
            $active = $connection->query(
                "SELECT id_interclasse FROM interclasses
                 WHERE status_interclasse = '1'
                 ORDER BY id_interclasse DESC LIMIT 1",
            );
            $activeId = $active === false ? null : $active->fetch_column();
            if ($active !== false) {
                $active->free();
            }
            if ($activeId === null || $activeId === false) {
                unset($_SESSION['id_interclasse']);
            } else {
                $_SESSION['id_interclasse'] = (int) $activeId;
            }
        }

        return true;
    }
}
