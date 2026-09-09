<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Infrastructure;

use App\Modules\Eventos\Domain\EdicaoConsulta;
use mysqli;

final class MysqliEdicaoConsultaRepository implements EdicaoConsulta
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function findActiveId(): ?int
    {
        $result = $this->connection->query(
            "SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' ORDER BY id_interclasse DESC LIMIT 1",
        );
        if ($result === false || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        return $row === null ? null : (int) $row['id_interclasse'];
    }

    public function isActive(int $editionId): bool
    {
        $statement = $this->connection->prepare(
            "SELECT status_interclasse FROM interclasses WHERE id_interclasse = ? LIMIT 1",
        );
        if ($statement === false) {
            return false;
        }
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return isset($row['status_interclasse']) && $row['status_interclasse'] === '1';
    }

    public function isRankingPublished(int $editionId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT ranking_publicado_em FROM interclasses WHERE id_interclasse = ? LIMIT 1',
        );
        if ($statement === false) {
            return false;
        }
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row !== null && $row['ranking_publicado_em'] !== null;
    }

    public function isUserEditionClosed(int $userId): bool
    {
        $statement = $this->connection->prepare(
            "SELECT i.status_interclasse
             FROM usuarios u
             JOIN interclasses i ON u.interclasses_id_interclasse = i.id_interclasse
             WHERE u.id_usuario = ? LIMIT 1",
        );
        if ($statement === false) {
            return true;
        }
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return !isset($row['status_interclasse']) || $row['status_interclasse'] === '0';
    }
}
