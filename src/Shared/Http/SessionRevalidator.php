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
            "SELECT nivel_usuario, status_usuario, auth_version
             FROM usuarios WHERE id_usuario = ? LIMIT 1",
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
