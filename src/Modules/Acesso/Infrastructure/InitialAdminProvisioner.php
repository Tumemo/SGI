<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use InvalidArgumentException;
use mysqli;
use RuntimeException;

/** Used only by the CLI installer, never exposed as a web route. */
final class InitialAdminProvisioner
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function create(string $login, string $name, string $password): int
    {
        $login = trim($login);
        $name = trim($name);
        if ($login === '' || $name === '' || mb_strlen($login) > 45 || mb_strlen($name) > 45 || strlen($password) < 12) {
            throw new InvalidArgumentException('Informe login e nome de até 45 caracteres e senha de pelo menos 12 caracteres.');
        }
        $lock = $this->connection->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':initial-admin'), 10)")->fetch_row();
        if ((int) $lock[0] !== 1) {
            throw new RuntimeException('Outra configuração inicial está em andamento.');
        }
        try {
            if ($this->connection->query("SELECT 1 FROM usuarios WHERE nivel_usuario = '0' LIMIT 1")->num_rows > 0) {
                throw new RuntimeException('Já existe administrador. A configuração inicial não altera contas existentes.');
            }
            $statement = $this->connection->prepare('SELECT 1 FROM usuarios WHERE matricula_usuario = ? LIMIT 1');
            $statement->bind_param('s', $login);
            $statement->execute();
            $exists = $statement->get_result()->num_rows > 0;
            $statement->close();
            if ($exists) {
                throw new RuntimeException('Esta matrícula já está cadastrada.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $this->connection->prepare("INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario) VALUES ('SS', ?, ?, ?, '0', 'MASC', '1900-01-01', '', '1')");
            $statement->bind_param('sss', $login, $name, $hash);
            $statement->execute();
            $id = $this->connection->insert_id;
            $statement->close();
            return $id;
        } finally {
            $this->connection->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':initial-admin'))");
        }
    }
}
