<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\UsuarioConsultaRepository;
use mysqli;
use RuntimeException;

final class MysqliUsuarioConsultaRepository implements UsuarioConsultaRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function competitors(int $classId, int $editionId, string $gender = '', bool $includeSensitive = true): array
    {
        if ($classId <= 0 || $editionId <= 0) {
            throw new RuntimeException('ID da turma é obrigatório e deve ser um número válido.');
        }

        $sensitiveColumns = $includeSensitive ? ', u.data_nasc_usuario' : '';
        $sql = "SELECT u.id_usuario, u.nome_usuario, u.matricula_usuario,
                       u.genero_usuario, u.nivel_usuario{$sensitiveColumns},
                       m.id_modalidade AS inscricao_modalidade_id,
                       m.nome_modalidade AS inscricao_modalidade_nome,
                       CASE WHEN EXISTS (
                           SELECT 1 FROM equipes_has_usuarios eu
                           INNER JOIN equipes eq ON eq.id_equipe = eu.equipes_id_equipe
                           WHERE eu.usuarios_id_usuario = u.id_usuario
                             AND eq.turmas_id_turma = u.turmas_id_turma
                       ) THEN 1 ELSE 0 END AS inscrito
                FROM usuarios u
                LEFT JOIN equipes_has_usuarios vinculo ON vinculo.usuarios_id_usuario = u.id_usuario
                LEFT JOIN equipes equipe_inscrita ON equipe_inscrita.id_equipe = vinculo.equipes_id_equipe
                    AND equipe_inscrita.turmas_id_turma = u.turmas_id_turma AND equipe_inscrita.status_equipe = '1'
                LEFT JOIN modalidades m ON m.id_modalidade = equipe_inscrita.modalidades_id_modalidade
                    AND m.interclasses_id_interclasse = u.interclasses_id_interclasse
                WHERE u.turmas_id_turma = ? AND u.interclasses_id_interclasse = ?
                  AND u.nivel_usuario = '3' AND u.status_usuario = '1'";
        $gender = strtoupper(trim($gender));
        $hasGender = in_array($gender, ['FEM', 'MASC'], true);
        if ($hasGender) {
            $sql .= ' AND u.genero_usuario = ?';
        }
        $sql .= ' ORDER BY u.nome_usuario ASC, u.id_usuario ASC, m.nome_modalidade ASC, m.id_modalidade ASC';
        $statement = $this->prepare($sql);
        if ($hasGender) {
            $statement->bind_param('iis', $classId, $editionId, $gender);
        } else {
            $statement->bind_param('ii', $classId, $editionId);
        }
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $competitors = [];
        foreach ($rows as $row) {
            $userId = (int) $row['id_usuario'];
            $modalityId = (int) ($row['inscricao_modalidade_id'] ?? 0);
            $modalityName = (string) ($row['inscricao_modalidade_nome'] ?? '');
            if (!isset($competitors[$userId])) {
                unset($row['inscricao_modalidade_id'], $row['inscricao_modalidade_nome']);
                $row['modalidades'] = [];
                $competitors[$userId] = $row;
            }
            if ($modalityId > 0) {
                $competitors[$userId]['modalidades'][$modalityId] = [
                    'id_modalidade' => $modalityId,
                    'nome_modalidade' => $modalityName,
                ];
            }
        }
        foreach ($competitors as &$competitor) {
            $competitor['modalidades'] = array_values($competitor['modalidades']);
        }
        unset($competitor);

        return ['status' => 'sucesso', 'competidores' => array_values($competitors)];
    }

    public function collaborators(int $editionId): array
    {
        $statement = $this->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario
            FROM usuarios WHERE (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)
              AND nivel_usuario IN ('0', '1', '2') AND status_usuario = '1' ORDER BY nome_usuario ASC");
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return ['status' => 'sucesso', 'colaboradores' => $rows];
    }

    public function allUsers(int $editionId): array
    {
        $statement = $this->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, genero_usuario, nivel_usuario
            FROM usuarios WHERE interclasses_id_interclasse = ? AND status_usuario = '1'");
        $statement->bind_param('i', $editionId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return ['status' => 'sucesso', 'usuarios' => $rows];
    }

    public function findCompetitorForValidation(string $registration, string $birth, int $editionId): ?array
    {
        $statement = $this->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, sigla_usuario, foto_usuario
            FROM usuarios WHERE matricula_usuario = ? AND data_nasc_usuario = ? AND interclasses_id_interclasse = ?
              AND nivel_usuario = '3' AND status_usuario = '1' LIMIT 1");
        $statement->bind_param('ssi', $registration, $birth, $editionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $row;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar consulta de usuário.');
        }

        return $statement;
    }
}
