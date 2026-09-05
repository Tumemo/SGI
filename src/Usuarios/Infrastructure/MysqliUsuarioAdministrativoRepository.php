<?php

declare(strict_types=1);

namespace App\Usuarios\Infrastructure;

use App\Usuarios\Domain\UsuarioAdministrativoRepository;
use mysqli;
use RuntimeException;

final class MysqliUsuarioAdministrativoRepository implements UsuarioAdministrativoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function deactivateStudent(int $id): bool
    {
        $statement = $this->connection->prepare("UPDATE usuarios SET status_usuario = '0' WHERE id_usuario = ? AND nivel_usuario = '3'");
        if ($statement === false) {
            throw new RuntimeException('Não foi possível remover aluno.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível remover aluno.');
        }
        $found = $statement->affected_rows > 0 || $this->hasStudent($id);
        $statement->close();
        return $found;
    }

    public function resetStudentPassword(int $id, string $hash): bool
    {
        $statement = $this->connection->prepare("UPDATE usuarios SET senha_usuario = ? WHERE id_usuario = ? AND nivel_usuario = '3'");
        if ($statement === false) {
            throw new RuntimeException('Não foi possível resetar senha de aluno.');
        }
        $statement->bind_param('si', $hash, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível resetar senha de aluno.');
        }
        $found = $statement->affected_rows > 0 || $this->hasStudent($id);
        $statement->close();
        return $found;
    }

    public function findLevel(int $id): ?string
    {
        $statement = $this->connection->prepare('SELECT nivel_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1');
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar usuário.');
        }
        $statement->bind_param('i', $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar usuário.');
        }
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row === null ? null : (string) $row['nivel_usuario'];
    }

    public function deactivateCollaborator(int $id, ?int $interclasseId): bool
    {
        $sql = "UPDATE usuarios SET status_usuario = '0' WHERE id_usuario = ?
                AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)";
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível remover colaborador.');
        }
        $scope = $interclasseId ?? 0;
        $statement->bind_param('ii', $id, $scope);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível remover colaborador.');
        }
        $updated = $statement->affected_rows > 0;
        $statement->close();
        return $updated;
    }

    private function hasStudent(int $id): bool
    {
        $statement = $this->connection->prepare("SELECT 1 FROM usuarios WHERE id_usuario = ? AND nivel_usuario = '3' LIMIT 1");
        if ($statement === false) {
            return false;
        }
        $statement->bind_param('i', $id);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
}
