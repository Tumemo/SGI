<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\PerfilRepository;
use mysqli;

final class MysqliPerfilRepository implements PerfilRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, foto_usuario, nivel_usuario, senha_usuario, senha_troca_pendente, auth_version FROM usuarios WHERE id_usuario = ? AND status_usuario = '1' LIMIT 1");
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row;
    }

    public function update(int $id, string $name, ?string $passwordHash, ?int $expectedAuthVersion = null): bool
    {
        if ($passwordHash === null) {
            $statement = $this->connection->prepare("UPDATE usuarios SET nome_usuario = ? WHERE id_usuario = ? AND status_usuario = '1'");
        } else {
            if ($expectedAuthVersion === null) {
                return false;
            }
            $statement = $this->connection->prepare(
                "UPDATE usuarios
                 SET nome_usuario = ?, senha_usuario = ?, auth_version = auth_version + 1
                 WHERE id_usuario = ? AND status_usuario = '1'
                   AND auth_version = ? AND senha_troca_pendente = 0",
            );
        }
        if ($statement === false) {
            return false;
        }
        if ($passwordHash === null) {
            $statement->bind_param('si', $name, $id);
        } else {
            $statement->bind_param('ssii', $name, $passwordHash, $id, $expectedAuthVersion);
        }
        if (!$statement->execute()) {
            $statement->close();
            return false;
        }
        $updated = $passwordHash === null || $statement->affected_rows === 1;
        $statement->close();
        return $updated;
    }

    public function setPhoto(int $id, ?string $filename): void
    {
        $filename ??= '';
        $statement = $this->connection->prepare('UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?');
        $statement->bind_param('si', $filename, $id);
        $statement->execute();
        $statement->close();
    }
}
