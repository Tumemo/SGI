<?php

declare(strict_types=1);

namespace App\Autenticacao\Infrastructure;

use App\Autenticacao\Domain\TermosRepository;
use mysqli;
use RuntimeException;

final class MysqliTermosRepository implements TermosRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findUser(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT u.id_usuario, u.interclasses_id_interclasse, u.senha_usuario, u.nivel_usuario,
                    ui.aceito_termo
             FROM usuarios u
             LEFT JOIN usuarios_has_interclasses ui
               ON u.id_usuario = ui.usuarios_id_usuario
              AND u.interclasses_id_interclasse = ui.interclasses_id_interclasse
             WHERE u.id_usuario = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar os termos.');
        }
        $statement->bind_param('i', $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar os termos.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row === null ? null : $row;
    }

    public function findActiveEdition(): ?int
    {
        $result = $this->connection->query(
            "SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' ORDER BY id_interclasse DESC LIMIT 1",
        );
        if ($result === false) {
            throw new RuntimeException('Não foi possível consultar a edição ativa.');
        }
        $row = $result->fetch_assoc();
        return $row === null ? null : (int) $row['id_interclasse'];
    }

    public function assignEdition(int $userId, int $interclasseId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE usuarios SET interclasses_id_interclasse = ? WHERE id_usuario = ?',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível vincular a edição ao usuário.');
        }
        $statement->bind_param('ii', $interclasseId, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível vincular a edição ao usuário.');
        }
        $statement->close();
    }

    public function accept(int $userId, int $interclasseId, string $dateTime): void
    {
        $check = $this->connection->prepare(
            'SELECT 1 FROM usuarios_has_interclasses WHERE usuarios_id_usuario = ? AND interclasses_id_interclasse = ? LIMIT 1',
        );
        if ($check === false) {
            throw new RuntimeException('Não foi possível salvar o aceite dos termos.');
        }
        $check->bind_param('ii', $userId, $interclasseId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $statement = $this->connection->prepare(
                "UPDATE usuarios_has_interclasses SET aceito_termo = 'sim', dt_hr_aceita = ?, status_termo = 'Ativo'
                 WHERE usuarios_id_usuario = ? AND interclasses_id_interclasse = ?",
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível salvar o aceite dos termos.');
            }
            $statement->bind_param('sii', $dateTime, $userId, $interclasseId);
        } else {
            $statement = $this->connection->prepare(
                "INSERT INTO usuarios_has_interclasses
                    (usuarios_id_usuario, interclasses_id_interclasse, dt_hr_aceita, aceito_termo, status_termo)
                 VALUES (?, ?, ?, 'sim', 'Ativo')",
            );
            if ($statement === false) {
                throw new RuntimeException('Não foi possível salvar o aceite dos termos.');
            }
            $statement->bind_param('iis', $userId, $interclasseId, $dateTime);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível salvar o aceite dos termos.');
        }
        $statement->close();
    }
}
