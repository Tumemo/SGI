<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\UsuarioRepository;
use mysqli;

final class MysqliUsuarioRepository implements UsuarioRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findActiveByMatricula(string $matricula, ?int $activeInterclasseId): ?array
    {
        $activeId = $activeInterclasseId ?? 0;
        $statement = $this->connection->prepare(
            "SELECT u.*,
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
             WHERE u.matricula_usuario = ?
               AND u.status_usuario = '1'
             ORDER BY
                 CASE
                     WHEN u.nivel_usuario IN ('0', '1', '2') THEN 0
                     WHEN u.nivel_usuario = '3'
                          AND u.interclasses_id_interclasse = ? THEN 1
                     WHEN u.nivel_usuario = '3' THEN 2
                     ELSE 3
                 END,
                 u.id_usuario DESC
             LIMIT 1",
        );

        if ($statement === false) {
            throw new \RuntimeException('Não foi possível consultar o usuário.');
        }

        $statement->bind_param('si', $matricula, $activeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row === null ? null : $row;
    }
}
