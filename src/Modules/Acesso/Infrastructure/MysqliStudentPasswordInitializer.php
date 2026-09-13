<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Shared\Security\StudentInitialPassword;
use mysqli;
use RuntimeException;

/** Development-only migration helper for student accounts that predate 012. */
final class MysqliStudentPasswordInitializer
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function initialize(): int
    {
        $this->connection->begin_transaction();
        try {
            $result = $this->connection->query(
                "SELECT id_usuario, senha_usuario, senha_troca_pendente, auth_version
                 FROM usuarios WHERE nivel_usuario = '3'
                 ORDER BY id_usuario FOR UPDATE",
            );
            if ($result === false) {
                throw new RuntimeException('Não foi possível consultar os alunos para inicialização.');
            }
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            $update = $this->connection->prepare(
                "UPDATE usuarios
                 SET senha_usuario = ?, senha_troca_pendente = 1, auth_version = auth_version + 1
                 WHERE id_usuario = ? AND nivel_usuario = '3' AND auth_version = ?",
            );
            if ($update === false) {
                throw new RuntimeException('Não foi possível preparar a inicialização das senhas.');
            }

            $updated = 0;
            foreach ($students as $student) {
                $oldHash = (string) ($student['senha_usuario'] ?? '');
                if ((int) ($student['senha_troca_pendente'] ?? 0) === 1
                    && password_verify(StudentInitialPassword::VALUE, $oldHash)) {
                    continue;
                }

                $hash = password_hash(StudentInitialPassword::VALUE, PASSWORD_DEFAULT);
                $id = (int) $student['id_usuario'];
                $version = (int) $student['auth_version'];
                $update->bind_param('sii', $hash, $id, $version);
                if (!$update->execute() || $update->affected_rows !== 1) {
                    throw new RuntimeException('A conta de um aluno mudou durante a inicialização.');
                }
                $updated++;
            }
            $update->close();
            $this->connection->commit();
            return $updated;
        } catch (\Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }
}
