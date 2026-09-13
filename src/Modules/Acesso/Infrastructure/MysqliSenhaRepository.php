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

    public function alterarSenha(int $usuarioId, string $hash, int $authVersion, bool $trocaInicial): bool
    {
        $pending = $trocaInicial ? 1 : 0;
        $statement = $this->connection->prepare(
            "UPDATE usuarios
             SET senha_usuario = ?, senha_troca_pendente = 0, auth_version = auth_version + 1
             WHERE id_usuario = ? AND status_usuario = '1'
               AND auth_version = ? AND senha_troca_pendente = ?",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível alterar a senha.');
        }
        $statement->bind_param('siii', $hash, $usuarioId, $authVersion, $pending);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível alterar a senha.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    public function senhaAtualValida(int $usuarioId, string $senha): bool
    {
        if ($usuarioId <= 0 || $senha === '') {
            return false;
        }
        $statement = $this->connection->prepare(
            "SELECT senha_usuario FROM usuarios WHERE id_usuario = ? AND status_usuario = '1' LIMIT 1",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível validar a senha atual.');
        }
        $statement->bind_param('i', $usuarioId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível validar a senha atual.');
        }
        $hash = (string) ($statement->get_result()->fetch_column() ?: '');
        $statement->close();
        return $hash !== '' && password_verify($senha, $hash);
    }
}
