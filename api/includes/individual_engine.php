<?php

declare(strict_types=1);

/**
 * Engine para modalidades individuais (corrida, natação, etc.)
 *
 * O admin seleciona participantes e registra 1º, 2º e 3º lugar.
 * Cada participante é um registro na tabela `partidas` com resultado_partida = posição (1, 2 ou 3).
 * Um "jogo" especial é criado com nome_jogo = "IND:{id_modalidade}" para agrupar o ranking.
 */

/** Tag para identificar jogos de modalidade individual */
function sgi_ind_tag(int $idModalidade): string
{
    return 'IND:' . $idModalidade;
}

/** Verifica se a tag é de uma modalidade individual */
function sgi_ind_parse(?string $nomeJogo): ?int
{
    if (!is_string($nomeJogo) || !preg_match('/^IND:(\d+)$/', $nomeJogo, $m)) {
        return null;
    }
    return (int) $m[1];
}

/**
 * Busca participantes disponíveis para uma modalidade individual.
 * Retorna alunos que são competidores e estão vinculados a equipes dessa modalidade.
 *
 * @return list<array{id_usuario:int, nome_usuario:string, nome_turma:string, nome_fantasia_turma:string, genero_usuario:string, id_equipe:int}>
 */
function sgi_ind_buscar_participantes(mysqli $conn, int $idModalidade): array
{
    $sql = 'SELECT DISTINCT u.id_usuario, u.nome_usuario, u.genero_usuario,
                   t.nome_turma, t.nome_fantasia_turma,
                   e.id_equipe
            FROM usuarios u
            INNER JOIN equipes_has_usuarios ehu ON ehu.usuarios_id_usuario = u.id_usuario
            INNER JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe
            INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
            WHERE e.modalidades_id_modalidade = ?
              AND e.status_equipe = \'1\'
              AND u.status_usuario = \'1\'
              AND u.competidor_usuario = \'1\'
            ORDER BY t.nome_turma, u.nome_usuario';

    $st = $conn->prepare($sql);
    if (!$st) {
        throw new RuntimeException($conn->error);
    }
    $st->bind_param('i', $idModalidade);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    return array_map(static fn(array $r): array => [
        'id_usuario' => (int) $r['id_usuario'],
        'nome_usuario' => $r['nome_usuario'],
        'genero_usuario' => $r['genero_usuario'],
        'nome_turma' => $r['nome_turma'],
        'nome_fantasia_turma' => $r['nome_fantasia_turma'],
        'id_equipe' => (int) $r['id_equipe'],
    ], $rows);
}

/**
 * Busca o jogo de ranking existente para uma modalidade individual.
 *
 * @return array{id_jogo:int, status_jogo:string}|null
 */
function sgi_ind_buscar_jogo_existente(mysqli $conn, int $idModalidade): ?array
{
    $tag = sgi_ind_tag($idModalidade);
    $st = $conn->prepare('SELECT id_jogo, status_jogo FROM jogos WHERE nome_jogo = ? LIMIT 1');
    $st->bind_param('s', $tag);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ? ['id_jogo' => (int) $row['id_jogo'], 'status_jogo' => $row['status_jogo']] : null;
}

/**
 * Cria o jogo de ranking para uma modalidade individual.
 */
function sgi_ind_criar_jogo(mysqli $conn, int $idModalidade): int
{
    $tag = sgi_ind_tag($idModalidade);
    $idLocal = sgi_mm_resolver_id_local($conn);

    $st = $conn->prepare(
        "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local)
         VALUES (?, CURDATE(), '08:00:00', 'Agendado', ?, ?)"
    );
    $st->bind_param('sii', $tag, $idModalidade, $idLocal);
    $st->execute();
    $idJogo = (int) $conn->insert_id;
    $st->close();

    return $idJogo;
}

/**
 * Salva o ranking (1º, 2º, 3º lugar) para uma modalidade individual.
 *
 * @param array{primeiro:int, segundo:int, terceiro:int} $ranking IDs dos usuários
 */
function sgi_ind_salvar_ranking(mysqli $conn, int $idModalidade, array $ranking): array
{
    // Validações
    if (empty($ranking['primeiro']) || empty($ranking['segundo']) || empty($ranking['terceiro'])) {
        throw new RuntimeException('É necessário informar o 1º, 2º e 3º lugar.');
    }

    // Verifica se os participantes são válidos
    $participantes = sgi_ind_buscar_participantes($conn, $idModalidade);
    $idsValidos = array_column($participantes, 'id_usuario');

    foreach (['primeiro', 'segundo', 'terceiro'] as $posicao) {
        if (!in_array($ranking[$posicao], $idsValidos, true)) {
            throw new RuntimeException("O participante do {$posicao} lugar não é válido para esta modalidade.");
        }
    }

    // Verifica duplicados
    $idsUnicos = array_unique([$ranking['primeiro'], $ranking['segundo'], $ranking['terceiro']]);
    if (count($idsUnicos) !== 3) {
        throw new RuntimeException('Os participantes do 1º, 2º e 3º lugar devem ser diferentes.');
    }

    // Busca ou cria o jogo
    $jogo = sgi_ind_buscar_jogo_existente($conn, $idModalidade);
    if ($jogo === null) {
        $idJogo = sgi_ind_criar_jogo($conn, $idModalidade);
    } else {
        $idJogo = $jogo['id_jogo'];
        // Limpa partidas existentes
        $stDel = $conn->prepare('DELETE FROM partidas WHERE jogos_id_jogo = ?');
        $stDel->bind_param('i', $idJogo);
        $stDel->execute();
        $stDel->close();
    }

    // Insere as 3 posições
    $posicoes = [
        1 => $ranking['primeiro'],
        2 => $ranking['segundo'],
        3 => $ranking['terceiro'],
    ];

    $stIns = $conn->prepare(
        "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_pardida)
         VALUES (?, ?, ?, '1')"
    );

    foreach ($posicoes as $posicao => $idUsuario) {
        // Busca a equipe do usuário para esta modalidade
        $equipe = sgi_ind_buscar_equipe_usuario($conn, $idUsuario, $idModalidade);
        if ($equipe === null) {
            throw new RuntimeException("Usuário {$idUsuario} não possui equipe nesta modalidade.");
        }

        $stIns->bind_param('iii', $idJogo, $equipe, $posicao);
        $stIns->execute();
    }
    $stIns->close();

    // Atualiza status do jogo para Concluído
    $stUpd = $conn->prepare("UPDATE jogos SET status_jogo = 'Concluido' WHERE id_jogo = ?");
    $stUpd->bind_param('i', $idJogo);
    $stUpd->execute();
    $stUpd->close();

    return [
        'success' => true,
        'message' => 'Ranking registrado com sucesso.',
        'id_jogo' => $idJogo,
    ];
}

/**
 * Busca a equipe de um usuário para uma modalidade específica.
 */
function sgi_ind_buscar_equipe_usuario(mysqli $conn, int $idUsuario, int $idModalidade): ?int
{
    $st = $conn->prepare(
        'SELECT e.id_equipe
         FROM equipes e
         INNER JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = e.id_equipe
         WHERE ehu.usuarios_id_usuario = ?
           AND e.modalidades_id_modalidade = ?
           AND e.status_equipe = \'1\'
         LIMIT 1'
    );
    $st->bind_param('ii', $idUsuario, $idModalidade);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return $row ? (int) $row['id_equipe'] : null;
}

/**
 * Monta JSON com o ranking de uma modalidade individual para o frontend.
 *
 * @return array{success:bool, ranking:list<array{posicao:int, id_usuario:int, nome_usuario:string, nome_turma:string, nome_fantasia_turma:string}>, jogo:array|null}
 */
function sgi_ind_montar_json_ranking(mysqli $conn, int $idModalidade): array
{
    $jogo = sgi_ind_buscar_jogo_existente($conn, $idModalidade);
    if ($jogo === null) {
        return ['success' => true, 'ranking' => [], 'jogo' => null];
    }

    $sql = 'SELECT p.resultado_partida, p.equipes_id_equipe,
                   u.id_usuario, u.nome_usuario,
                   t.nome_turma, t.nome_fantasia_turma
            FROM partidas p
            INNER JOIN equipes_has_usuarios ehu ON ehu.equipes_id_equipe = p.equipes_id_equipe
            INNER JOIN usuarios u ON u.id_usuario = ehu.usuarios_id_usuario
            INNER JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
            INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
            WHERE p.jogos_id_jogo = ?
              AND u.competidor_usuario = \'1\'
            ORDER BY p.resultado_partida ASC';

    $st = $conn->prepare($sql);
    $st->bind_param('i', $jogo['id_jogo']);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $ranking = [];
    foreach ($rows as $row) {
        $ranking[] = [
            'posicao' => (int) $row['resultado_partida'],
            'id_usuario' => (int) $row['id_usuario'],
            'nome_usuario' => $row['nome_usuario'],
            'nome_turma' => $row['nome_turma'],
            'nome_fantasia_turma' => $row['nome_fantasia_turma'],
        ];
    }

    return [
        'success' => true,
        'ranking' => $ranking,
        'jogo' => [
            'id_jogo' => $jogo['id_jogo'],
            'status_jogo' => $jogo['status_jogo'],
        ],
    ];
}
