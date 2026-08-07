<?php

declare(strict_types=1);

/**
 * Helpers de regra de negócio para equipes (RF01/RF03/RF05).
 *
 * Convenção de nome de equipe: "{Nome base da modalidade} - {Número}".
 * A equipe com sufixo "- 1" é a Equipe Padrão da turma/modalidade.
 */

/**
 * Remove o sufixo de gênero do nome da modalidade para compor o nome da equipe.
 * Ex.: "Futsal - MA" => "Futsal"; "Corrida - FE" => "Corrida".
 */
function sgi_nome_base_modalidade(string $nome): string
{
    $base = trim($nome);
    $sufixos = ['MA', 'MI', 'FE', 'MASC', 'FEM', 'MISTO', 'MISTA'];
    $pattern = '/^(.*?)\s*-\s*(' . implode('|', $sufixos) . ')$/i';
    if (preg_match($pattern, $base, $m)) {
        return trim($m[1]);
    }
    return $base;
}

/**
 * Monta o nome padrão da equipe: "{Modalidade} - {Número}".
 */
function sgi_nome_equipe_padrao(string $nomeModalidade, int $numero): string
{
    return sgi_nome_base_modalidade($nomeModalidade) . ' - ' . $numero;
}

/**
 * Retorna o nome da turma ou null caso não exista.
 */
function sgi_nome_turma(mysqli $conn, int $idTurma): ?string
{
    $stmt = $conn->prepare('SELECT nome_turma FROM turmas WHERE id_turma = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $idTurma);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? trim((string) $row['nome_turma']) : null;
}

/**
 * Monta o nome da equipe com o nome da turma na frente:
 * "{Nome da turma} {Modalidade} - {Número}". Ex.: "6EF Futsal - 2".
 */
function sgi_nome_equipe_turma(?string $nomeTurma, string $nomeModalidade, int $numero): string
{
    $nome = sgi_nome_equipe_padrao($nomeModalidade, $numero);
    if ($nomeTurma !== null && $nomeTurma !== '') {
        return $nomeTurma . ' ' . $nome;
    }
    return $nome;
}

/**
 * Extrai o número do sufixo de uma equipe no padrão "{...} - {N}". Retorna null se não houver.
 */
function sgi_numero_equipe(?string $nomeEquipe): ?int
{
    if ($nomeEquipe === null || $nomeEquipe === '') {
        return null;
    }
    if (preg_match('/-\s*(\d+)\s*$/', $nomeEquipe, $m)) {
        return (int) $m[1];
    }
    return null;
}

/**
 * Retorna dados básicos da modalidade: id, nome, max_inscrito_modalidade e max_equipes.
 */
function sgi_dados_modalidade(mysqli $conn, int $idModalidade): ?array
{
    $stmt = $conn->prepare(
        'SELECT id_modalidade, nome_modalidade, max_inscrito_modalidade, max_equipes
         FROM modalidades WHERE id_modalidade = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $idModalidade);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Busca a Equipe Padrão ("- 1") de uma turma/modalidade, criando-a quando não existir.
 * Retorna o id_equipe ou null em caso de falha.
 */
function sgi_buscar_ou_criar_equipe_padrao(mysqli $conn, int $idModalidade, int $idTurma): ?int
{
    if ($idModalidade <= 0 || $idTurma <= 0) {
        return null;
    }

    $mod = sgi_dados_modalidade($conn, $idModalidade);
    $nomeTurma = sgi_nome_turma($conn, $idTurma);
    $nomePadrao = $mod ? sgi_nome_equipe_turma($nomeTurma, (string) $mod['nome_modalidade'], 1) : null;

    // 1. Tenta pela convenção de nome ("{Modalidade} - 1").
    if ($nomePadrao !== null) {
        $stmt = $conn->prepare(
            "SELECT id_equipe FROM equipes
             WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ?
               AND nome_equipe = ? AND status_equipe = '1'
             ORDER BY id_equipe ASC LIMIT 1"
        );
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
    $stmt = $conn->prepare(
        "SELECT id_equipe FROM equipes
         WHERE modalidades_id_modalidade = ? AND turmas_id_turma = ? AND status_equipe = '1'
         ORDER BY id_equipe ASC LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $idModalidade, $idTurma);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int) $row['id_equipe'];
    }

    // 3. Cria a Equipe Padrão.
    $stmt = $conn->prepare(
        "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
         VALUES ('1', ?, ?, ?)"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('iis', $idModalidade, $idTurma, $nomePadrao);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
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
function sgi_gerar_equipes_padrao_interclasse(mysqli $conn, int $idInterclasse): array
{
    $criadas = 0;
    $erros = [];

    if ($idInterclasse <= 0) {
        return ['criadas' => 0, 'erros' => ['ID do interclasse inválido.']];
    }

    $stmtTurmas = $conn->prepare(
        'SELECT id_turma, categorias_id_categoria FROM turmas
         WHERE status_turma = \'1\' AND interclasses_id_interclasse = ?'
    );
    $stmtModalidades = $conn->prepare(
        'SELECT id_modalidade, nome_modalidade, categorias_id_categoria FROM modalidades
         WHERE status_modalidade = \'1\' AND interclasses_id_interclasse = ?'
    );
    if (!$stmtTurmas || !$stmtModalidades) {
        return ['criadas' => 0, 'erros' => ['Falha ao preparar consulta de turmas/modalidades.']];
    }

    $stmtTurmas->bind_param('i', $idInterclasse);
    $stmtTurmas->execute();
    $turmas = $stmtTurmas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtTurmas->close();

    $stmtModalidades->bind_param('i', $idInterclasse);
    $stmtModalidades->execute();
    $modalidades = $stmtModalidades->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtModalidades->close();

    foreach ($turmas as $turma) {
        foreach ($modalidades as $modalidade) {
            if ((int) $turma['categorias_id_categoria'] !== (int) $modalidade['categorias_id_categoria']) {
                continue;
            }

            $idEquipe = sgi_buscar_ou_criar_equipe_padrao(
                $conn,
                (int) $modalidade['id_modalidade'],
                (int) $turma['id_turma']
            );
            if ($idEquipe === null) {
                $erros[] = sprintf(
                    'Falha ao garantir equipe padrão para turma %d / modalidade %s.',
                    (int) $turma['id_turma'],
                    (string) $modalidade['nome_modalidade']
                );
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
function sgi_carregar_equipes_secundarias(
    mysqli $conn,
    int $idModalidade,
    int $idTurma,
    int $idEquipePadrao
): array {
    $secundarias = [];

    $sql = "SELECT e.id_equipe, e.nome_equipe, COUNT(u.id_usuario) AS total
            FROM equipes e
            LEFT JOIN equipes_has_usuarios eu ON eu.equipes_id_equipe = e.id_equipe
            LEFT JOIN usuarios u ON u.id_usuario = eu.usuarios_id_usuario AND u.status_usuario = '1'
            WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ?
              AND e.id_equipe != ? AND e.status_equipe = '1'
            GROUP BY e.id_equipe
            ORDER BY e.id_equipe ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $secundarias;
    }
    $stmt->bind_param('iii', $idModalidade, $idTurma, $idEquipePadrao);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $secundarias[(int) $row['id_equipe']] = [
            'numero' => sgi_numero_equipe($row['nome_equipe']),
            'ocupados' => (int) $row['total'],
        ];
    }
    $stmt->close();

    return $secundarias;
}

/**
 * Retorna a próxima equipe secundária com vaga disponível, criando uma nova
 * equipe ("- 2", "- 3", ...) quando necessário. Atualiza $secundarias por referência.
 */
function sgi_proxima_equipe_com_vaga(
    mysqli $conn,
    int $idModalidade,
    int $idTurma,
    int $idEquipePadrao,
    array &$secundarias,
    int $limite
): ?int {
    foreach ($secundarias as $id => $dados) {
        if ($dados['ocupados'] < $limite) {
            $secundarias[$id]['ocupados']++;
            return (int) $id;
        }
    }

    // Nenhuma vaga: tenta criar uma nova equipe secundária, respeitando o
    // limite máximo de equipes da turma/modalidade (max_equipes), se definido.
    $mod = sgi_dados_modalidade($conn, $idModalidade);
    $maxEquipes = isset($mod['max_equipes']) && $mod['max_equipes'] !== null
        ? (int) $mod['max_equipes']
        : null;
    if ($maxEquipes !== null && (count($secundarias) + 1) >= $maxEquipes) {
        return null;
    }

    $numero = 2;
    foreach ($secundarias as $dados) {
        $n = $dados['numero'];
        if ($n !== null && $n >= $numero) {
            $numero = $n + 1;
        }
    }

    $nomeTurma = sgi_nome_turma($conn, $idTurma);
    $nome = $mod ? sgi_nome_equipe_turma($nomeTurma, (string) $mod['nome_modalidade'], $numero) : null;

    $stmt = $conn->prepare(
        "INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
         VALUES ('1', ?, ?, ?)"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('iis', $idModalidade, $idTurma, $nome);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
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
function sgi_redistribuir_equipe(mysqli $conn, int $idModalidade, int $idTurma): array
{
    $mod = sgi_dados_modalidade($conn, $idModalidade);
    if ($mod === null) {
        return ['success' => false, 'message' => 'Modalidade não encontrada.'];
    }
    $limite = (int) $mod['max_inscrito_modalidade'];
    if ($limite <= 0) {
        return ['success' => false, 'message' => 'Modalidade sem limite máximo de inscritos definido.'];
    }

    $idEquipePadrao = sgi_buscar_ou_criar_equipe_padrao($conn, $idModalidade, $idTurma);
    if ($idEquipePadrao === null) {
        return ['success' => false, 'message' => 'Não foi possível localizar/criar a equipe padrão da turma.'];
    }

    $stmt = $conn->prepare(
        'SELECT eu.usuarios_id_usuario
         FROM equipes_has_usuarios eu
         INNER JOIN usuarios u ON u.id_usuario = eu.usuarios_id_usuario
         WHERE eu.equipes_id_equipe = ? AND u.status_usuario = \'1\'
         ORDER BY eu.usuarios_id_usuario ASC'
    );
    if (!$stmt) {
        return ['success' => false, 'message' => 'Falha ao consultar alunos da equipe padrão.'];
    }
    $stmt->bind_param('i', $idEquipePadrao);
    $stmt->execute();
    $res = $stmt->get_result();
    $alunos = [];
    while ($row = $res->fetch_assoc()) {
        $alunos[] = (int) $row['usuarios_id_usuario'];
    }
    $stmt->close();

    $total = count($alunos);
    if ($total <= $limite) {
        return [
            'success' => true,
            'message' => 'Nenhum aluno excedente para redistribuir.',
            'modalidades_id_modalidade' => $idModalidade,
            'turmas_id_turma' => $idTurma,
            'total_alunos' => $total,
            'limite_maximo' => $limite,
            'redistribuidos' => 0,
            'nao_redistribuidos' => 0,
            'excedeu_limite' => false,
        ];
    }

    $excedentes = array_slice($alunos, $limite);
    shuffle($excedentes);

    $conn->begin_transaction();
    try {
        $secundarias = sgi_carregar_equipes_secundarias($conn, $idModalidade, $idTurma, $idEquipePadrao);

        $sqlRemover = $conn->prepare(
            'DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?'
        );
        $sqlInserir = $conn->prepare(
            'INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)'
        );
        if (!$sqlRemover || !$sqlInserir) {
            throw new RuntimeException('Falha ao preparar comandos de movimentação de alunos.');
        }

        $redistribuidos = 0;
        $naoRedistribuidos = 0;

        foreach ($excedentes as $idUsuario) {
            $alvo = sgi_proxima_equipe_com_vaga($conn, $idModalidade, $idTurma, $idEquipePadrao, $secundarias, $limite);
            if ($alvo === null) {
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

        return [
            'success' => true,
            'message' => $redistribuidos > 0
                ? $redistribuidos . ' aluno(s) redistribuído(s) para equipe(s) secundária(s).'
                : 'Nenhum aluno pôde ser redistribuído.',
            'modalidades_id_modalidade' => $idModalidade,
            'turmas_id_turma' => $idTurma,
            'total_alunos' => $totalFinal,
            'limite_maximo' => $limite,
            'redistribuidos' => $redistribuidos,
            'nao_redistribuidos' => $naoRedistribuidos,
            'excedeu_limite' => $totalFinal > $limite,
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Falha na redistribuição: ' . $e->getMessage()];
    }
}
