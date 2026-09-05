<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\SenhaRepository;
use mysqli;
use RuntimeException;

final class MysqliSenhaRepository implements SenhaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function alterarSenha(int $usuarioId, string $hash): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE usuarios SET senha_usuario = ? WHERE id_usuario = ? AND status_usuario = '1'",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível alterar a senha.');
        }
        $statement->bind_param('si', $hash, $usuarioId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível alterar a senha.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }
}
