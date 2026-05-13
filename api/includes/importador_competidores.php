<?php

declare(strict_types=1);

require_once __DIR__ . '/usuario_validacao.php';

/**
 * Importação RF01/RF02 com transação e pré-processamento de turmas.
 */
final class ImportadorCompetidores
{
    public function __construct(private mysqli $conn)
    {
    }

    /**
     * Caminho padrão do JSON gerado pelo conversor (relativo à pasta api/).
     */
    public static function caminhoJsonPadrao(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'json_turmas' . DIRECTORY_SEPARATOR . 'info_alunos.json';
    }

    /**
     * @param ?int $idInterclasseForcado ID da edição (pode estar inativa). Se null, usa interclasse ativo.
     * @param ?int $idCategoriaForcado Categoria para criar/vincular turmas do PDF. Se null, usa a primeira do interclasse.
     * @return array{status:string, mensagem?:string, interclasse_vinculado?:int, cadastrados?:int, erros?:list<string>}
     */
    public function importarDeArquivo(?string $caminhoJson = null, ?int $idInterclasseForcado = null, ?int $idCategoriaForcado = null): array
    {
        $caminhoJson = $caminhoJson ?? self::caminhoJsonPadrao();

        $idInterclasse = $this->resolverInterclassePreferido($idInterclasseForcado);
        if ($idInterclasse === null) {
            return ['status' => 'erro', 'mensagem' => 'Interclasse inválido ou nenhuma edição ativa. Informe id_interclasse no upload ou marque uma edição como ativa.'];
        }

        if (!is_readable($caminhoJson)) {
            return ['status' => 'erro', 'mensagem' => 'Arquivo JSON não encontrado.'];
        }

        $raw = file_get_contents($caminhoJson);
        if ($raw === false) {
            return ['status' => 'erro', 'mensagem' => 'Não foi possível ler o arquivo JSON.'];
        }

        $alunos = json_decode($raw, true);
        if (!is_array($alunos)) {
            return ['status' => 'erro', 'mensagem' => 'JSON inválido ou corrompido.'];
        }

        $idCategoria = $this->resolverCategoriaPreferida($idInterclasse, $idCategoriaForcado);
        if ($idCategoria === null) {
            return ['status' => 'erro', 'mensagem' => 'Não existe categoria para este interclasse. Cadastre uma categoria ou informe id_categoria no upload.'];
        }

        $prepared = $this->prepararLinhas($alunos);
        if ($prepared['fatal'] !== null) {
            return ['status' => 'erro', 'mensagem' => $prepared['fatal']];
        }

        $linhas = $prepared['linhas'];
        if ($linhas === []) {
            return [
                'status' => 'sucesso',
                'interclasse_vinculado' => $idInterclasse,
                'cadastrados' => 0,
                'erros' => $prepared['avisos'],
            ];
        }

        $this->conn->begin_transaction();
        try {
            $mapaTurmas = $this->sincronizarTurmasEmLote($idInterclasse, $idCategoria, $linhas);
            $cadastrados = 0;
            $erros = $prepared['avisos'];

            $sqlUser = 'INSERT INTO usuarios (
                sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario,
                competidor_usuario, mesario_usuario, genero_usuario, data_nasc_usuario,
                foto_usuario, status_usuario, turmas_id_turma
            ) VALUES (\'RM\', ?, ?, ?, \'0\', \'1\', \'0\', ?, ?, \'default.jpg\', \'1\', ?)';

            $stmtU = $this->conn->prepare($sqlUser);
            if (!$stmtU) {
                throw new RuntimeException('Falha ao preparar inserção de usuários: ' . $this->conn->error);
            }

            foreach ($linhas as $linha) {
                $idTurma = $mapaTurmas[$linha['turma']] ?? null;
                if ($idTurma === null) {
                    $erros[] = "Turma não resolvida para RM {$linha['rm']}.";
                    throw new RuntimeException('Inconsistência no mapa de turmas.');
                }

                $senhaHash = password_hash($linha['rm'], PASSWORD_DEFAULT);
                $stmtU->bind_param(
                    'sssssi',
                    $linha['rm'],
                    $linha['nome'],
                    $senhaHash,
                    $linha['genero'],
                    $linha['data_nasc'],
                    $idTurma
                );

                if (!$stmtU->execute()) {
                    if ($stmtU->errno === 1062) {
                        continue;
                    }
                    throw new RuntimeException($stmtU->error ?: 'Erro ao inserir competidor.');
                }
                $cadastrados++;
            }

            $stmtU->close();
            $this->conn->commit();

            return [
                'status' => 'sucesso',
                'interclasse_vinculado' => $idInterclasse,
                'cadastrados' => $cadastrados,
                'erros' => $erros,
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return [
                'status' => 'erro',
                'mensagem' => 'Importação cancelada para manter o banco consistente: ' . $e->getMessage(),
            ];
        }
    }

    private function resolverInterclasseAtiva(): ?int
    {
        $res = $this->conn->query("SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' LIMIT 1");
        if (!$res) {
            return null;
        }
        $row = $res->fetch_assoc();
        return isset($row['id_interclasse']) ? (int) $row['id_interclasse'] : null;
    }

    /** Usa ID informado no upload (qualquer status) ou, se inválido, o interclasse ativo. */
    private function resolverInterclassePreferido(?int $forcado): ?int
    {
        if ($forcado !== null && $forcado > 0) {
            $st = $this->conn->prepare('SELECT id_interclasse FROM interclasses WHERE id_interclasse = ? LIMIT 1');
            if ($st) {
                $st->bind_param('i', $forcado);
                $st->execute();
                $row = $st->get_result()->fetch_assoc();
                $st->close();
                if ($row) {
                    return (int) $row['id_interclasse'];
                }
            }
        }
        return $this->resolverInterclasseAtiva();
    }

    /** Categoria explícita (deve pertencer ao interclasse) ou primeira categoria da edição. */
    private function resolverCategoriaPreferida(int $idInterclasse, ?int $forcado): ?int
    {
        if ($forcado !== null && $forcado > 0) {
            $st = $this->conn->prepare(
                'SELECT id_categoria FROM categorias WHERE id_categoria = ? AND interclasses_id_interclasse = ? LIMIT 1'
            );
            if ($st) {
                $st->bind_param('ii', $forcado, $idInterclasse);
                $st->execute();
                $row = $st->get_result()->fetch_assoc();
                $st->close();
                if ($row) {
                    return (int) $row['id_categoria'];
                }
            }
        }
        return $this->resolverPrimeiraCategoriaDoInterclasse($idInterclasse);
    }

    private function resolverPrimeiraCategoriaDoInterclasse(int $idInterclasse): ?int
    {
        $stmt = $this->conn->prepare(
            'SELECT id_categoria FROM categorias WHERE interclasses_id_interclasse = ? ORDER BY id_categoria ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $idInterclasse);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return isset($row['id_categoria']) ? (int) $row['id_categoria'] : null;
    }

    /**
     * @return array{linhas: list<array{rm:string,nome:string,genero:string,data_nasc:string,turma:string,turno:string}>, avisos: list<string>, fatal: ?string}
     */
    private function prepararLinhas(array $alunos): array
    {
        $linhas = [];
        $avisos = [];

        foreach ($alunos as $idx => $aluno) {
            if (!is_array($aluno)) {
                $avisos[] = 'Registro ignorado (índice ' . $idx . '): formato inválido.';
                continue;
            }

            $nome = trim((string) ($aluno['nome'] ?? ''));
            $nomeTurma = trim((string) ($aluno['turma'] ?? ''));
            $rm = sgi_normalizar_ra($aluno['rm'] ?? '');
            $genero = (isset($aluno['genero']) && strtoupper((string) $aluno['genero']) === 'FEM') ? 'FEM' : 'MASC';
            $dataRaw = $aluno['data_nascimento'] ?? '';
            $dataNasc = sgi_parse_data_nascimento(is_string($dataRaw) ? $dataRaw : '');

            if ($nome === '' || $rm === '' || $nomeTurma === '') {
                return [
                    'linhas' => [],
                    'avisos' => [],
                    'fatal' => 'Linha ' . ($idx + 1) . ': nome, RM ou turma ausente.',
                ];
            }
            if ($dataNasc === null) {
                return [
                    'linhas' => [],
                    'avisos' => [],
                    'fatal' => 'Linha ' . ($idx + 1) . ': data de nascimento inválida ou ausente.',
                ];
            }

            $turno = $this->normalizarTurno($aluno['turno'] ?? null);
            $linhas[] = [
                'rm' => $rm,
                'nome' => $nome,
                'genero' => $genero,
                'data_nasc' => $dataNasc,
                'turma' => $nomeTurma,
                'turno' => $turno,
            ];
        }

        return ['linhas' => $linhas, 'avisos' => $avisos, 'fatal' => null];
    }

    private function normalizarTurno(mixed $turno): string
    {
        $t = strtolower(trim((string) $turno));
        $map = [
            'manhã' => 'manha',
            'manha' => 'manha',
            'm' => 'manha',
            'tarde' => 'tarde',
            't' => 'tarde',
            'noite' => 'noite',
            'n' => 'noite',
            'integral' => 'integral',
            'integral ' => 'integral',
        ];
        return $map[$t] ?? 'integral';
    }

    /**
     * RF02: cria turmas ausentes, atualiza vínculo com o Interclasse ativo em lote.
     *
     * @param list<array{turma:string, turno:string}> $linhas
     * @return array<string, int> nome_turma => id_turma
     */
    private function sincronizarTurmasEmLote(int $idInterclasse, int $idCategoria, array $linhas): array
    {
        $porTurma = [];
        foreach ($linhas as $l) {
            $nome = $l['turma'];
            if (!isset($porTurma[$nome])) {
                $porTurma[$nome] = $l['turno'];
            }
        }
        $nomes = array_keys($porTurma);
        sort($nomes);

        $existentes = $this->carregarTurmasPorNomes($nomes);
        $criar = [];
        foreach ($nomes as $nome) {
            if (!isset($existentes[$nome])) {
                $criar[$nome] = $porTurma[$nome];
            }
        }

        if ($criar !== []) {
            $this->inserirTurmasNovas($idInterclasse, $idCategoria, $criar);
        }

        if ($nomes !== []) {
            $this->atualizarVinculoInterclasse($idInterclasse, $nomes);
        }

        return $this->carregarTurmasPorNomes($nomes);
    }

    /**
     * @param list<string> $nomes
     * @return array<string, int>
     */
    private function carregarTurmasPorNomes(array $nomes): array
    {
        if ($nomes === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($nomes), '?'));
        $types = str_repeat('s', count($nomes));
        $sql = "SELECT id_turma, nome_turma FROM turmas WHERE nome_turma IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Falha ao listar turmas: ' . $this->conn->error);
        }
        $stmt->bind_param($types, ...$nomes);
        $stmt->execute();
        $res = $stmt->get_result();
        $map = [];
        while ($row = $res->fetch_assoc()) {
            $map[(string) $row['nome_turma']] = (int) $row['id_turma'];
        }
        $stmt->close();
        return $map;
    }

    /**
     * @param array<string, string> $criar nome_turma => turno normalizado
     */
    private function inserirTurmasNovas(int $idInterclasse, int $idCategoria, array $criar): void
    {
        $cols = 'nome_turma, interclasses_id_interclasse, status_turma, categorias_id_categoria, turno_turma';
        $tpl = '(' . implode(',', array_fill(0, 5, '?')) . ')';
        $values = [];
        $params = [];
        $types = '';
        foreach ($criar as $nome => $turno) {
            $values[] = $tpl;
            $params[] = $nome;
            $params[] = $idInterclasse;
            $params[] = '1';
            $params[] = $idCategoria;
            $params[] = $turno;
            $types .= 'sisis';
        }
        $sql = 'INSERT INTO turmas (' . $cols . ') VALUES ' . implode(',', $values);
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Falha ao inserir turmas: ' . $this->conn->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err ?: 'Erro ao inserir turmas.');
        }
        $stmt->close();
    }

    /**
     * @param list<string> $nomes
     */
    private function atualizarVinculoInterclasse(int $idInterclasse, array $nomes): void
    {
        $placeholders = implode(',', array_fill(0, count($nomes), '?'));
        $types = 'i' . str_repeat('s', count($nomes));
        $sql = "UPDATE turmas SET interclasses_id_interclasse = ? WHERE nome_turma IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Falha ao atualizar vínculo das turmas: ' . $this->conn->error);
        }
        $params = array_merge([$idInterclasse], $nomes);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err ?: 'Erro ao atualizar turmas.');
        }
        $stmt->close();
    }
}

/**
 * Compatível com conversor_pdf.php e chamadas legadas.
 *
 * @param ?int $idInterclasse Opcional: edição alvo (sobrescreve "só ativo").
 * @param ?int $idCategoria Opcional: categoria para turmas novas do PDF.
 */
function importarCompetidores(mysqli $conn, ?int $idInterclasse = null, ?int $idCategoria = null): array
{
    $imp = new ImportadorCompetidores($conn);
    return $imp->importarDeArquivo(null, $idInterclasse, $idCategoria);
}
