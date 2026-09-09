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
        $statement = $this->connection->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, foto_usuario, nivel_usuario, senha_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = '1' LIMIT 1");
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row;
    }

    public function update(int $id, string $name, ?string $passwordHash): void
    {
        $statement = $this->connection->prepare('UPDATE usuarios SET nome_usuario = ?, senha_usuario = COALESCE(?, senha_usuario), auth_version = auth_version + IF(? IS NULL, 0, 1) WHERE id_usuario = ?');
        $statement->bind_param('sssi', $name, $passwordHash, $passwordHash, $id);
        $statement->execute();
        $statement->close();
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
