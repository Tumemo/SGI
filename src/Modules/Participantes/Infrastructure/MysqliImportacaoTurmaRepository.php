<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

use App\Modules\Participantes\Domain\ImportacaoTurmaRepository;
use mysqli;

final class MysqliImportacaoTurmaRepository implements ImportacaoTurmaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findClass(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT nome_turma, interclasses_id_interclasse FROM turmas WHERE id_turma = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $result = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $result;
    }

    public function findActiveEdition(): ?int
    {
        $row = $this->connection->query("SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' LIMIT 1")->fetch_assoc();
        return isset($row['id_interclasse']) ? (int) $row['id_interclasse'] : null;
    }

    public function import(array $students, int $class, int $edition): array
    {
        return PdfAlunoImporter::inserirAlunosNaTurma($this->connection, $students, $class, $edition);
    }
}
