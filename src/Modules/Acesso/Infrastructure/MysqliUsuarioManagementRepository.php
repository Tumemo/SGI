<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Acesso\Domain\UsuarioManagementRepository;
use mysqli;
use RuntimeException;

final class MysqliUsuarioManagementRepository implements UsuarioManagementRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function createStudent(array $data, int $editionId): array
    {
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $matricula = (string) ($data['matricula_usuario'] ?? '');
        $birth = (string) ($data['data_nasc_usuario'] ?? '');
        $classId = (int) ($data['turmas_id_turma'] ?? 0);
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if ($name === '' || $matricula === '' || $birth === '' || $classId <= 0) {
            throw new RuntimeException('Campos obrigatórios: nome, RM, data de nascimento e turma.');
        }
        if (!in_array($gender, ['FEM', 'MASC'], true)) {
            $gender = 'MASC';
        }
        if ($this->one('SELECT 1 FROM turmas WHERE id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1', 'ii', [$classId, $editionId]) === null) {
            throw new RuntimeException('Turma não pertence à edição ativa.');
        }
        $key = $matricula . '-' . $editionId;
        if ($this->one('SELECT 1 FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1', 's', [$key]) !== null) {
            throw new RuntimeException('Já existe um aluno com este RM nesta edição do interclasse.');
        }

        $temporaryPassword = rtrim(strtr(base64_encode(random_bytes(9)), '+/', '-_'), '=');
        $password = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $statement = $this->prepare('INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao) VALUES (\'RM\', ?, ?, ?, \'3\', ?, ?, \'default.jpg\', \'1\', ?, ?, ?)');
        $statement->bind_param('sssssiis', $matricula, $name, $password, $gender, $birth, $classId, $editionId, $key);
        if (!$statement->execute()) {
            $message = $statement->errno === 1062 ? 'RM já cadastrado nesta edição.' : 'Não foi possível cadastrar aluno.';
            $statement->close();
            throw new RuntimeException($message);
        }
        $id = (int) $statement->insert_id;
        $statement->close();

        return [
            'status' => 'sucesso',
            'mensagem' => 'Aluno cadastrado. Entregue a senha temporária por um canal seguro.',
            'id_usuario' => $id,
            'senha_temporaria' => $temporaryPassword,
        ];
    }

    public function assignStudent(int $userId, int $classId, int $editionId): void
    {
        if ($this->one(
            "SELECT u.id_usuario
             FROM usuarios u
             INNER JOIN turmas t ON t.id_turma = ?
             WHERE u.id_usuario = ? AND u.nivel_usuario = '3'
               AND u.interclasses_id_interclasse = ?
               AND t.interclasses_id_interclasse = ? LIMIT 1",
            'iiii',
            [$classId, $userId, $editionId, $editionId],
        ) === null) {
            throw new RuntimeException('Aluno ou turma não pertence à edição ativa.');
        }
        $statement = $this->prepare('UPDATE usuarios SET turmas_id_turma = ?, interclasses_id_interclasse = ?, chave_usuario_edicao = CONCAT(matricula_usuario, \'-\', ?) WHERE id_usuario = ? AND nivel_usuario = \'3\'');
        $statement->bind_param('iiii', $classId, $editionId, $editionId, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar o vínculo do aluno.');
        }
        $statement->close();
    }

    public function createStaff(array $data, int $editionId, string $photoFilename = 'default.jpg'): array
    {
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = trim((string) ($data['matricula_usuario'] ?? ''));
        $password = (string) ($data['senha_usuario'] ?? '');
        $birth = (string) ($data['data_nasc_usuario'] ?? '');
        if ($name === '' || $registration === '' || $password === '' || $birth === '') {
            throw new RuntimeException('Campos incompletos');
        }
        $level = ($data['is_admin_clicado'] ?? '0') === '1' ? '0' : (($data['is_mesario_clicado'] ?? '0') === '1' ? '2' : '1');
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if (!in_array($gender, ['FEM', 'MASC'], true)) {
            $gender = 'MASC';
        }
        $normalised = (string) ($data['matricula_usuario'] ?? $registration);
        $master = ['sgi@sgi.com', 'colab@sgi.com', 'mes@sgi.com'];
        if (in_array(strtolower($registration), $master, true) || in_array(strtolower($normalised), $master, true)) {
            throw new RuntimeException('Este email pertence a uma conta padrão do sistema e não pode ser reutilizado.');
        }
        $key = $normalised . '-' . $editionId;
        if ($this->one('SELECT 1 FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1', 's', [$key]) !== null) {
            throw new RuntimeException('Já existe um usuário com esta matrícula/email nesta edição do Interclasse.');
        }
        $photoFilename = basename($photoFilename) ?: 'default.jpg';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $this->prepare('INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, genero_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario, interclasses_id_interclasse, chave_usuario_edicao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $status = '1';
        $sigla = 'SS';
        $statement->bind_param('sssssssssis', $name, $normalised, $hash, $level, $gender, $photoFilename, $status, $birth, $sigla, $editionId, $key);
        if (!$statement->execute()) {
            $duplicate = $statement->errno === 1062;
            $statement->close();
            throw new RuntimeException($duplicate ? 'Esta matrícula/email já está cadastrada nesta edição do interclasse.' : 'Não foi possível cadastrar usuário.');
        }
        $id = (int) $statement->insert_id;
        $statement->close();

        return ['status' => 'sucesso', 'mensagem' => 'Usuário cadastrado!', 'id_usuario' => $id];
    }

    public function updateStaffRole(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID do colaborador inválido.');
        }
        $level = ($data['is_admin_clicado'] ?? '0') === '1' ? '0' : (($data['is_mesario_clicado'] ?? '0') === '1' ? '2' : '1');
        $statement = $this->prepare('UPDATE usuarios SET nivel_usuario = ?, auth_version = auth_version + 1 WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)');
        $statement->bind_param('sii', $level, $id, $editionId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar colaborador.');
        }
        $statement->close();
    }

    public function updateStaffDetails(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = trim((string) ($data['matricula_usuario'] ?? ''));
        if ($id <= 0 || $name === '' || $registration === '') {
            throw new RuntimeException('Nome e matrícula são obrigatórios.');
        }
        $normalised = (string) ($data['matricula_usuario'] ?? $registration);
        $key = $normalised . '-' . $editionId;
        if ($this->one('SELECT 1 FROM usuarios WHERE chave_usuario_edicao = ? AND id_usuario <> ? LIMIT 1', 'si', [$key, $id]) !== null) {
            throw new RuntimeException('Já existe outro usuário com esta matrícula/email nesta edição.');
        }
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if (!in_array($gender, ['FEM', 'MASC'], true)) {
            $gender = 'MASC';
        }
        $fields = ['nome_usuario = ?', 'matricula_usuario = ?', 'genero_usuario = ?', 'chave_usuario_edicao = ?'];
        $values = [$name, $normalised, $gender, $key];
        $types = 'ssss';
        $password = (string) ($data['senha_usuario'] ?? '');
        if ($password !== '') {
            $fields[] = 'senha_usuario = ?';
            $values[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= 's';
        }
        if ($password !== '') {
            $fields[] = 'auth_version = auth_version + 1';
        }
        $values[] = $id;
        $values[] = $editionId;
        $types .= 'ii';
        $statement = $this->prepare('UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)');
        $statement->bind_param($types, ...$values);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar colaborador.');
        }
        $statement->close();
    }

    public function updateStudent(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = (string) ($data['matricula_usuario'] ?? '');
        $birth = (string) ($data['data_nasc_usuario'] ?? '');
        if ($id <= 0 || $name === '' || $registration === '' || $birth === '') {
            throw new RuntimeException('Campos obrigatórios: nome, RM e data de nascimento.');
        }
        $normalised = $registration;
        $key = $normalised . '-' . $editionId;
        if ($this->one('SELECT 1 FROM usuarios WHERE chave_usuario_edicao = ? AND id_usuario <> ? LIMIT 1', 'si', [$key, $id]) !== null) {
            throw new RuntimeException('Já existe outro aluno com este RM nesta edição.');
        }
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if (!in_array($gender, ['FEM', 'MASC'], true)) {
            $gender = 'MASC';
        }
        $statement = $this->prepare("UPDATE usuarios SET nome_usuario = ?, matricula_usuario = ?, genero_usuario = ?, data_nasc_usuario = ?, chave_usuario_edicao = ? WHERE id_usuario = ? AND nivel_usuario = '3' AND interclasses_id_interclasse = ?");
        $statement->bind_param('sssssii', $name, $normalised, $gender, $birth, $key, $id, $editionId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar aluno.');
        }
        $statement->close();
    }

    /** @param list<int|string> $params @return array<string, mixed>|null */
    private function one(string $sql, string $types, array $params): ?array
    {
        $statement = $this->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $row;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Não foi possível preparar operação de usuário.');
        }

        return $statement;
    }
}
