<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Infrastructure;

use App\Modules\Competicoes\Domain\EquipeCapacityRules;
use App\Shared\Database\Transaction;

final class MysqliEquipePadraoRepository
{
    /**
     * Retorna o nome da turma ou null caso não exista.
     */
    public static function nomeTurma(\mysqli $conn, int $idTurma): ?string
    {
        $stmt = $conn->prepare('SELECT nome_turma FROM turmas WHERE id_turma = ? LIMIT 1');
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('i', $idTurma);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? \trim((string) $row['nome_turma']) : \null;
    }

    /** @return array{max_equipes:?int}|null */
    private static function lockScope(\mysqli $conn, int $idModalidade, int $idTurma): ?array
    {
        $modality = $conn->prepare(
            'SELECT categorias_id_categoria, interclasses_id_interclasse, max_equipes, status_modalidade
             FROM modalidades WHERE id_modalidade = ? LIMIT 1 FOR UPDATE',
        );
        if (!$modality) {
            return \null;
        }
        $modality->bind_param('i', $idModalidade);
        $modality->execute();
        $modalityRow = $modality->get_result()->fetch_assoc() ?: null;
        $modality->close();
        if ($modalityRow === null || (string) $modalityRow['status_modalidade'] !== '1') {
            return \null;
        }

        $class = $conn->prepare(
            'SELECT categorias_id_categoria, interclasses_id_interclasse, status_turma
             FROM turmas WHERE id_turma = ? LIMIT 1 FOR UPDATE',
        );
        if (!$class) {
            return \null;
        }
        $class->bind_param('i', $idTurma);
        $class->execute();
        $classRow = $class->get_result()->fetch_assoc() ?: null;
        $class->close();
        if ($classRow === null || (string) $classRow['status_turma'] !== '1') {
            return \null;
        }
        if ((int) $modalityRow['interclasses_id_interclasse'] !== (int) $classRow['interclasses_id_interclasse']
            || (int) $modalityRow['categorias_id_categoria'] !== (int) $classRow['categorias_id_categoria']) {
            return \null;
        }
        return ['max_equipes' => $modalityRow['max_equipes'] === null ? null : (int) $modalityRow['max_equipes']];
    }

    private static function activeCount(\mysqli $conn, int $idModalidade, int $idTurma): int
    {
        $statement = $conn->prepare(
            "SELECT COUNT(*) FROM equipes
             WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'",
        );
        if (!$statement) {
            return 0;
        }
        $statement->bind_param('ii', $idModalidade, $idTurma);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        return $count;
    }

    /**
     * Retorna dados básicos da modalidade: id, nome, max_inscrito_modalidade e max_equipes.
     */
    public static function dadosModalidade(\mysqli $conn, int $idModalidade): ?array
    {
        $stmt = $conn->prepare('SELECT id_modalidade, nome_modalidade, max_inscrito_modalidade, max_equipes
         FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('i', $idModalidade);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: \null;
    }
    /**
     * Busca a Equipe Padrão ("- 1") de uma turma/modalidade, criando-a quando não existir.
     * Retorna o id_equipe ou null em caso de falha.
     */
    public static function buscarOuCriarEquipePadrao(\mysqli $conn, int $idModalidade, int $idTurma): ?int
    {
        Transaction::begin($conn);
        try {
            $id = self::buscarOuCriarEquipePadraoSemTransacao($conn, $idModalidade, $idTurma);
            Transaction::commit($conn);
            return $id;
        } catch (\Throwable) {
            Transaction::rollback($conn);
            return null;
        }
    }

    private static function buscarOuCriarEquipePadraoSemTransacao(\mysqli $conn, int $idModalidade, int $idTurma): ?int
    {
        if ($idModalidade <= 0 || $idTurma <= 0) {
            return \null;
        }
        $scope = self::lockScope($conn, $idModalidade, $idTurma);
        if ($scope === null) {
            return \null;
        }
        $mod = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::dadosModalidade($conn, $idModalidade);
        $nomeTurma = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::nomeTurma($conn, $idTurma);
        $nomePadrao = $mod ? \App\Modules\Competicoes\Domain\EquipeRules::nomeEquipeTurma($nomeTurma, (string) $mod['nome_modalidade'], 1) : \null;
        // 1. Tenta pela convenção de nome ("{Modalidade} - 1").
        if ($nomePadrao !== \null) {
            $stmt = $conn->prepare("SELECT id_equipe FROM equipes\r\n             WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ?\r\n               AND nome_equipe = ? AND status_equipe = '1'\r\n             ORDER BY id_equipe ASC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('iis', $idModalidade, $idTurma, $nomePadrao);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    return (int) $row['id_equipe'];
                }
            }
        }
        // 2. Fallback: primeira equipe ativa da combinação (equipes criadas antes da nova convenção).
        $stmt = $conn->prepare("SELECT id_equipe FROM equipes\r\n         WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'\r\n         ORDER BY id_equipe ASC LIMIT 1");
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('ii', $idModalidade, $idTurma);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int) $row['id_equipe'];
        }
        if (!EquipeCapacityRules::podeAtivar($scope['max_equipes'], self::activeCount($conn, $idModalidade, $idTurma))) {
            return \null;
        }
        // 3. Cria a Equipe Padrão.
        $stmt = $conn->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)\r\n         VALUES ('1', ?, ?, ?)");
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('iis', $idModalidade, $idTurma, $nomePadrao);
        if (!$stmt->execute()) {
            $stmt->close();
            return \null;
        }
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }
    /**
     * Garante a existência da Equipe Padrão ("- 1") para cada turma × modalidade da edição.
     * Respeita o vínculo de categoria (turma e modalidade na mesma categoria).
     *
     * @return array{criadas:int, erros:list<string>}
     */
    public static function gerarEquipesPadraoInterclasse(\mysqli $conn, int $idInterclasse): array
    {
        $criadas = 0;
        $erros = [];
        if ($idInterclasse <= 0) {
            return ['criadas' => 0, 'erros' => ['ID do interclasse inválido.']];
        }
        $stmtTurmas = $conn->prepare('SELECT id_turma, categorias_id_categoria FROM turmas
         WHERE status_turma = \'1\' AND interclasses_id_interclasse = ?');
        $stmtModalidades = $conn->prepare('SELECT id_modalidade, nome_modalidade, categorias_id_categoria FROM modalidades
         WHERE status_modalidade = \'1\' AND interclasses_id_interclasse = ?');
        if (!$stmtTurmas || !$stmtModalidades) {
            return ['criadas' => 0, 'erros' => ['Falha ao preparar consulta de turmas/modalidades.']];
        }
        $stmtTurmas->bind_param('i', $idInterclasse);
        $stmtTurmas->execute();
        $turmas = $stmtTurmas->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmtTurmas->close();
        $stmtModalidades->bind_param('i', $idInterclasse);
        $stmtModalidades->execute();
        $modalidades = $stmtModalidades->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmtModalidades->close();
        foreach ($turmas as $turma) {
            foreach ($modalidades as $modalidade) {
                if ((int) $turma['categorias_id_categoria'] !== (int) $modalidade['categorias_id_categoria']) {
                    continue;
                }
                $idEquipe = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($conn, (int) $modalidade['id_modalidade'], (int) $turma['id_turma']);
                if ($idEquipe === \null) {
                    $erros[] = \sprintf('Falha ao garantir equipe padrão para turma %d / modalidade %s.', (int) $turma['id_turma'], (string) $modalidade['nome_modalidade']);
                } else {
                    $criadas++;
                }
            }
        }
        return ['criadas' => $criadas, 'erros' => $erros];
    }
    /**
     * Carrega as equipes secundárias (todas exceto a padrão) da turma/modalidade
     * com a quantidade de alunos ocupada.
     *
     * @return array<int, array{numero:?int, ocupados:int}>
     */
    public static function carregarEquipesSecundarias(\mysqli $conn, int $idModalidade, int $idTurma, int $idEquipePadrao): array
    {
        $secundarias = [];
        $sql = "SELECT e.id_equipe, e.nome_equipe, COUNT(u.id_usuario) AS total\r\n            FROM equipes e\r\n            LEFT JOIN equipes_has_usuarios eu ON eu.equipes_id_equipe = e.id_equipe\r\n            LEFT JOIN usuarios u ON u.id_usuario = eu.usuarios_id_usuario AND u.status_usuario = '1'\r\n            WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ?\r\n              AND e.id_equipe != ? AND e.status_equipe = '1'\r\n            GROUP BY e.id_equipe\r\n            ORDER BY e.id_equipe ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $secundarias;
        }
        $stmt->bind_param('iii', $idModalidade, $idTurma, $idEquipePadrao);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $secundarias[(int) $row['id_equipe']] = ['numero' => \App\Modules\Competicoes\Domain\EquipeRules::numeroEquipe($row['nome_equipe']), 'ocupados' => (int) $row['total']];
        }
        $stmt->close();
        return $secundarias;
    }
    /**
     * Retorna a próxima equipe secundária com vaga disponível, criando uma nova
     * equipe ("- 2", "- 3", ...) quando necessário. Atualiza $secundarias por referência.
     */
    public static function proximaEquipeComVaga(\mysqli $conn, int $idModalidade, int $idTurma, int $idEquipePadrao, array &$secundarias, int $limite): ?int
    {
        $scope = self::lockScope($conn, $idModalidade, $idTurma);
        if ($scope === null) {
            return \null;
        }
        foreach ($secundarias as $id => $dados) {
            if ($dados['ocupados'] < $limite) {
                $secundarias[$id]['ocupados']++;
                return (int) $id;
            }
        }
        // Nenhuma vaga: tenta criar uma nova equipe secundária, respeitando o
        // limite máximo de equipes da turma/modalidade (max_equipes), se definido.
        $mod = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::dadosModalidade($conn, $idModalidade);
        $maxEquipes = $scope['max_equipes'];
        if (!EquipeCapacityRules::podeAtivar($maxEquipes, self::activeCount($conn, $idModalidade, $idTurma))) {
            return \null;
        }
        $numero = 2;
        foreach ($secundarias as $dados) {
            $n = $dados['numero'];
            if ($n !== \null && $n >= $numero) {
                $numero = $n + 1;
            }
        }
        $nomeTurma = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::nomeTurma($conn, $idTurma);
        $nome = $mod ? \App\Modules\Competicoes\Domain\EquipeRules::nomeEquipeTurma($nomeTurma, (string) $mod['nome_modalidade'], $numero) : \null;
        $stmt = $conn->prepare("INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)\r\n         VALUES ('1', ?, ?, ?)");
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('iis', $idModalidade, $idTurma, $nome);
        if (!$stmt->execute()) {
            $stmt->close();
            return \null;
        }
        $novoId = (int) $stmt->insert_id;
        $stmt->close();
        $secundarias[$novoId] = ['numero' => $numero, 'ocupados' => 1];
        return $novoId;
    }
    /**
     * Redistribui os alunos excedentes da Equipe Padrão para equipes secundárias.
     *
     * 1. Mantém na Equipe Padrão a quantidade exata até o limite.
     * 2. Embaralha os excedentes e preenche as equipes secundárias ("- 2", "- 3", ...)
     *    até o limite de cada uma, criando novas equipes sob demanda.
     * 3. Se não for possível alocar um aluno (falha na criação/inserção), ele permanece
     *    na Equipe Padrão, que continuará com excedeu_limite = true.
     */
    public static function redistribuirEquipe(\mysqli $conn, int $idModalidade, int $idTurma): array
    {
        $mod = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::dadosModalidade($conn, $idModalidade);
        if ($mod === \null) {
            return ['success' => \false, 'message' => 'Modalidade não encontrada.'];
        }
        $limite = (int) $mod['max_inscrito_modalidade'];
        if ($limite <= 0) {
            return ['success' => \false, 'message' => 'Modalidade sem limite máximo de inscritos definido.'];
        }
        $idEquipePadrao = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::buscarOuCriarEquipePadrao($conn, $idModalidade, $idTurma);
        if ($idEquipePadrao === \null) {
            return ['success' => \false, 'message' => 'Não foi possível localizar/criar a equipe padrão da turma.'];
        }
        $stmt = $conn->prepare('SELECT eu.usuarios_id_usuario
         FROM equipes_has_usuarios eu
         INNER JOIN usuarios u ON u.id_usuario = eu.usuarios_id_usuario
         WHERE eu.equipes_id_equipe = ? AND u.status_usuario = \'1\'
         ORDER BY eu.usuarios_id_usuario ASC');
        if (!$stmt) {
            return ['success' => \false, 'message' => 'Falha ao consultar alunos da equipe padrão.'];
        }
        $stmt->bind_param('i', $idEquipePadrao);
        $stmt->execute();
        $res = $stmt->get_result();
        $alunos = [];
        while ($row = $res->fetch_assoc()) {
            $alunos[] = (int) $row['usuarios_id_usuario'];
        }
        $stmt->close();
        $total = \count($alunos);
        if ($total <= $limite) {
            return ['success' => \true, 'message' => 'Nenhum aluno excedente para redistribuir.', 'modalidades_id_modalidade' => $idModalidade, 'turmas_id_turma' => $idTurma, 'total_alunos' => $total, 'limite_maximo' => $limite, 'redistribuidos' => 0, 'nao_redistribuidos' => 0, 'excedeu_limite' => \false];
        }
        $excedentes = \array_slice($alunos, $limite);
        \shuffle($excedentes);
        $conn->begin_transaction();
        try {
            $secundarias = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::carregarEquipesSecundarias($conn, $idModalidade, $idTurma, $idEquipePadrao);
            $sqlRemover = $conn->prepare('DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?');
            $sqlInserir = $conn->prepare('INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
            if (!$sqlRemover || !$sqlInserir) {
                throw new \RuntimeException('Falha ao preparar comandos de movimentação de alunos.');
            }
            $redistribuidos = 0;
            $naoRedistribuidos = 0;
            foreach ($excedentes as $idUsuario) {
                $alvo = \App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepository::proximaEquipeComVaga($conn, $idModalidade, $idTurma, $idEquipePadrao, $secundarias, $limite);
                if ($alvo === \null) {
                    $naoRedistribuidos++;
                    continue;
                }
                $sqlInserir->bind_param('ii', $alvo, $idUsuario);
                $sqlInserir->execute();
                if ($sqlInserir->affected_rows === 1) {
                    $sqlRemover->bind_param('ii', $idEquipePadrao, $idUsuario);
                    $sqlRemover->execute();
                    $redistribuidos++;
                } else {
                    $naoRedistribuidos++;
                }
            }
            $sqlRemover->close();
            $sqlInserir->close();
            $conn->commit();
            $totalFinal = $total - $redistribuidos;
            return ['success' => \true, 'message' => $redistribuidos > 0 ? $redistribuidos . ' aluno(s) redistribuído(s) para equipe(s) secundária(s).' : 'Nenhum aluno pôde ser redistribuído.', 'modalidades_id_modalidade' => $idModalidade, 'turmas_id_turma' => $idTurma, 'total_alunos' => $totalFinal, 'limite_maximo' => $limite, 'redistribuidos' => $redistribuidos, 'nao_redistribuidos' => $naoRedistribuidos, 'excedeu_limite' => $totalFinal > $limite];
        } catch (\Throwable $e) {
            $conn->rollback();
            return ['success' => \false, 'message' => 'Falha na redistribuição: ' . $e->getMessage()];
        }
    }
}
