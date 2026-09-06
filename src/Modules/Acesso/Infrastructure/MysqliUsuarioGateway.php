<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Infrastructure;

use App\Modules\Eventos\Infrastructure\MysqliEdicaoConsulta;
use App\Modules\Participantes\Domain\MatriculaRules;
use mysqli;
use RuntimeException;

final class MysqliUsuarioGateway
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function activeEdition(): ?int
    {
        return MysqliEdicaoConsulta::buscarInterclasseAtivo($this->connection);
    }

    /** @return array<string, mixed> */
    public function competitors(int $classId, ?int $editionId = null, string $gender = ''): array
    {
        $editionId ??= $this->activeEdition() ?? 0;
        if ($classId <= 0 || $editionId <= 0) {
            throw new RuntimeException('ID da turma é obrigatório e deve ser um número válido.');
        }
        $sql = "SELECT u.id_usuario, u.nome_usuario, u.matricula_usuario,
                       u.genero_usuario, u.nivel_usuario, u.data_nasc_usuario,
                       CASE WHEN EXISTS (
                           SELECT 1 FROM equipes_has_usuarios eu
                           INNER JOIN equipes eq ON eq.id_equipe = eu.equipes_id_equipe
                           WHERE eu.usuarios_id_usuario = u.id_usuario
                             AND eq.turmas_id_turma = u.turmas_id_turma
                       ) THEN 1 ELSE 0 END AS inscrito
                FROM usuarios u
                WHERE u.turmas_id_turma = ? AND u.interclasses_id_interclasse = ?
                  AND u.nivel_usuario = '3' AND u.status_usuario = '1'";
        $gender = strtoupper(trim($gender));
        if (in_array($gender, ['FEM', 'MASC'], true)) {
            $sql .= ' AND u.genero_usuario = ?';
        }
        $sql .= ' ORDER BY u.nome_usuario ASC';
        $statement = $this->prepare($sql);
        if (in_array($gender, ['FEM', 'MASC'], true)) {
            $statement->bind_param('iis', $classId, $editionId, $gender);
        } else {
            $statement->bind_param('ii', $classId, $editionId);
        }
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return ['status' => 'sucesso', 'competidores' => $rows];
    }

    /** @return array<string, mixed> */
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

    /** @return array<string, mixed> */
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

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createStudent(array $data, int $editionId): array
    {
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $matricula = MatriculaRules::normalizarRa((string) ($data['matricula_usuario'] ?? ''));
        $birth = MatriculaRules::parseDataNascimento((string) ($data['data_nasc_usuario'] ?? ''));
        $classId = (int) ($data['turmas_id_turma'] ?? 0);
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if ($name === '' || $matricula === '' || $birth === null || $classId <= 0) {
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
        $password = password_hash('123', PASSWORD_DEFAULT);
        $statement = $this->prepare('INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao) VALUES (\'RM\', ?, ?, ?, \'3\', ?, ?, \'default.jpg\', \'1\', ?, ?, ?)');
        $statement->bind_param('sssssiis', $matricula, $name, $password, $gender, $birth, $classId, $editionId, $key);
        if (!$statement->execute()) {
            $message = $statement->errno === 1062 ? 'RM já cadastrado nesta edição.' : 'Não foi possível cadastrar aluno.';
            $statement->close();
            throw new RuntimeException($message);
        }
        $id = (int) $statement->insert_id;
        $statement->close();
        return ['status' => 'sucesso', 'mensagem' => 'Aluno cadastrado!', 'id_usuario' => $id];
    }

    public function assignStudent(int $userId, int $classId, int $editionId): void
    {
        $statement = $this->prepare('UPDATE usuarios SET turmas_id_turma = ?, interclasses_id_interclasse = ?, chave_usuario_edicao = CONCAT(matricula_usuario, \'-\', ?) WHERE id_usuario = ? AND nivel_usuario = \'3\'');
        $statement->bind_param('iiii', $classId, $editionId, $editionId, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar o vínculo do aluno.');
        }
        $statement->close();
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $photo */
    public function createStaff(array $data, int $editionId, ?array $photo = null): array
    {
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = trim((string) ($data['matricula_usuario'] ?? ''));
        $password = (string) ($data['senha_usuario'] ?? '');
        $birth = MatriculaRules::parseDataNascimento((string) ($data['data_nasc_usuario'] ?? ''));
        if ($name === '' || $registration === '' || $password === '' || $birth === null) {
            throw new RuntimeException('Campos incompletos');
        }
        $level = ($data['is_admin_clicado'] ?? '0') === '1' ? '0' : (($data['is_mesario_clicado'] ?? '0') === '1' ? '2' : '1');
        $gender = strtoupper((string) ($data['genero_usuario'] ?? 'MASC'));
        if (!in_array($gender, ['FEM', 'MASC'], true)) {
            $gender = 'MASC';
        }
        $normalised = MatriculaRules::normalizarRa($registration) ?: $registration;
        $master = ['sgi@sgi.com', 'colab@sgi.com', 'mes@sgi.com'];
        if (in_array(strtolower($registration), $master, true) || in_array(strtolower($normalised), $master, true)) {
            throw new RuntimeException('Este email pertence a uma conta padrão do sistema e não pode ser reutilizado.');
        }
        $key = $normalised . '-' . $editionId;
        if ($this->one('SELECT 1 FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1', 's', [$key]) !== null) {
            throw new RuntimeException('Já existe um usuário com esta matrícula/email nesta edição do Interclasse.');
        }
        $photoName = $this->storePhoto($photo);
        $sigla = 'SS';
        $status = '1';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $this->prepare('INSERT INTO usuarios (nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, genero_usuario, foto_usuario, status_usuario, data_nasc_usuario, sigla_usuario, interclasses_id_interclasse, chave_usuario_edicao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('sssssssssis', $name, $normalised, $hash, $level, $gender, $photoName, $status, $birth, $sigla, $editionId, $key);
        if (!$statement->execute()) {
            $duplicate = $statement->errno === 1062;
            $statement->close();
            throw new RuntimeException($duplicate ? 'Esta matrícula/email já está cadastrada nesta edição do interclasse.' : 'Não foi possível cadastrar usuário.');
        }
        $id = (int) $statement->insert_id;
        $statement->close();
        return ['status' => 'sucesso', 'mensagem' => 'Usuário cadastrado!', 'id_usuario' => $id];
    }

    /** @param array<string, mixed> $data */
    public function updateStaffRole(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID do colaborador inválido.');
        }
        $level = ($data['is_admin_clicado'] ?? '0') === '1' ? '0' : (($data['is_mesario_clicado'] ?? '0') === '1' ? '2' : '1');
        $statement = $this->prepare('UPDATE usuarios SET nivel_usuario = ? WHERE id_usuario = ? AND (interclasses_id_interclasse = ? OR interclasses_id_interclasse IS NULL)');
        $statement->bind_param('sii', $level, $id, $editionId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar colaborador.');
        }
        $statement->close();
    }

    /** @param array<string, mixed> $data */
    public function updateStaffDetails(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = trim((string) ($data['matricula_usuario'] ?? ''));
        if ($id <= 0 || $name === '' || $registration === '') {
            throw new RuntimeException('Nome e matrícula são obrigatórios.');
        }
        $normalised = MatriculaRules::normalizarRa($registration) ?: $registration;
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

    /** @param array<string, mixed> $data */
    public function updateStudent(array $data, int $editionId): void
    {
        $id = (int) ($data['id_usuario'] ?? 0);
        $name = trim((string) ($data['nome_usuario'] ?? ''));
        $registration = trim((string) ($data['matricula_usuario'] ?? ''));
        $birth = MatriculaRules::parseDataNascimento((string) ($data['data_nasc_usuario'] ?? ''));
        if ($id <= 0 || $name === '' || $registration === '' || $birth === null) {
            throw new RuntimeException('Campos obrigatórios: nome, RM e data de nascimento.');
        }
        $normalised = MatriculaRules::normalizarRa($registration);
        if ($normalised === '') {
            throw new RuntimeException('RM inválido.');
        }
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

    /** @param array<string, mixed>|null $photo */
    private function storePhoto(?array $photo): string
    {
        if ($photo === null || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'default.jpg';
        }
        $extension = strtolower(pathinfo((string) ($photo['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException('Formato de foto inválido. Use JPG, PNG, GIF ou WebP.');
        }
        $directory = \App\Shared\Storage\StoragePaths::fotosUsuarios();
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento da foto.');
        }
        $name = 'user_' . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file((string) ($photo['tmp_name'] ?? ''), $directory . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException('Não foi possível salvar a foto enviada.');
        }
        return $name;
    }

    public function setEditionStatus(int $editionId, string $status): void
    {
        if (!in_array($status, ['0', '1'], true)) {
            throw new RuntimeException('Informe id_interclasse e status_interclasse (0 ou 1).');
        }
        $statement = $this->prepare('UPDATE interclasses SET status_interclasse = ? WHERE id_interclasse = ?');
        $statement->bind_param('si', $status, $editionId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível atualizar a edição.');
        }
        $statement->close();
    }

    /** @return array<string, mixed>|null */
    public function findCompetitorForValidation(string $registration, string $birth, int $editionId): ?array
    {
        $normalised = MatriculaRules::normalizarRa($registration);
        if ($normalised === '' || $birth === '' || $editionId <= 0) {
            return null;
        }
        $statement = $this->prepare("SELECT id_usuario, nome_usuario, matricula_usuario, senha_usuario, nivel_usuario, sigla_usuario, foto_usuario
            FROM usuarios WHERE matricula_usuario = ? AND data_nasc_usuario = ? AND interclasses_id_interclasse = ?
              AND (nivel_usuario = '3' OR competidor_usuario = '3') AND status_usuario = '1' LIMIT 1");
        $statement->bind_param('ssi', $normalised, $birth, $editionId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
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
