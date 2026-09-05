<?php

declare(strict_types=1);

namespace App\Autenticacao\Infrastructure;

use App\Autenticacao\Domain\InterclasseRepository;
use mysqli;

final class MysqliInterclasseRepository implements InterclasseRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findActiveId(): ?int
    {
        $statement = $this->connection->prepare(
            "SELECT id_interclasse
             FROM interclasses
             WHERE status_interclasse = '1'
             ORDER BY id_interclasse DESC
             LIMIT 1",
        );

        if ($statement === false) {
            throw new \RuntimeException('Não foi possível consultar a edição ativa.');
        }

        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row === null ? null : (int) $row['id_interclasse'];
    }
}
