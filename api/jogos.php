<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
header('Content-Type: application/json');

/**
 * Auxiliar para garantir formato HH:MM:SS
 */
function sgi_formatar_hora($hora)
{
    if (empty($hora) || $hora === '00:00' || $hora === '00:00:00') {
        return '00:00:00';
    }
    return strlen($hora) === 5 ? $hora . ':00' : $hora;
}

function sgi_validar_horario_turmas($conn, $id_jogo, $inicio, $termino)
{
    if ($inicio === '00:00:00' || $termino === '00:00:00') {
        return null;
    }

    $turnos = [
        'manha'    => ['07:00', '12:00'],
        'tarde'    => ['13:00', '18:00'],
        'noite'    => ['19:00', '22:30'],
        'integral' => ['07:00', '18:00'],
    ];

    $sql = "SELECT DISTINCT t.turno_turma
            FROM partidas p
            INNER JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
            INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
            WHERE p.jogos_id_jogo = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_jogo);
    $stmt->execute();
    $res = $stmt->get_result();
    $turnos_turmas = [];
    while ($row = $res->fetch_assoc()) {
        $turnos_turmas[] = $row['turno_turma'];
    }
    $stmt->close();

    if (empty($turnos_turmas)) {
        return null;
    }

    $inicio_ts = strtotime($inicio);
    $termino_ts = strtotime($termino);
    if ($inicio_ts === false || $termino_ts === false) {
        return "Horário inválido.";
    }

    foreach ($turnos_turmas as $turno) {
        if (!isset($turnos[$turno])) {
            continue;
        }
        list($limite_inicio, $limite_fim) = $turnos[$turno];
        $limite_inicio_ts = strtotime($limite_inicio);
        $limite_fim_ts = strtotime($limite_fim);

        if ($inicio_ts < $limite_inicio_ts || $termino_ts > $limite_fim_ts) {
            $mapa_nomes = [
                'manha' => 'Manhã (07:00-12:00)',
                'tarde' => 'Tarde (13:00-18:00)',
                'noite' => 'Noite (19:00-22:30)',
                'integral' => 'Integral (07:00-18:00)',
            ];
            $nome_turno = $mapa_nomes[$turno] ?? $turno;
            return "O horário do jogo excede o turno <b>{$nome_turno}</b> de uma ou mais turmas participantes. Ajuste o horário ou contate a coordenação.";
        }
    }
    return null;
}

function sgi_validar_conflito_local_horario($conn, $data, $local_id, $inicio, $termino, $id_jogo_atual = null)
{
    // Ignora checagem se o horário não foi preenchido corretamente
    if ($inicio === '00:00:00' || $termino === '00:00:00') {
        return null;
    }

    // Interseção correta: $inicio <= termino_jogo AND $termino >= inicio_jogo
    $sql = "SELECT id_jogo, nome_jogo FROM jogos 
            WHERE data_jogo = ? 
              AND locais_id_local = ? 
              AND status_jogo != 'Cancelado'
              AND ? < termino_jogo 
              AND ? > inicio_jogo";

    if ($id_jogo_atual) {
        $sql .= " AND id_jogo != ?";
    }

    $stmt = $conn->prepare($sql);
  
    // Ordem exata dos parâmetros: data, local_id, inicio, termino
    if ($id_jogo_atual) {
        $stmt->bind_param("sissi", $data, $local_id, $inicio, $termino, $id_jogo_atual);
    } else {
        $stmt->bind_param("siss", $data, $local_id, $inicio, $termino);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $conflito = $res->fetch_assoc();
    $stmt->close();

    if ($conflito) {
        return "Já existe um jogo agendado neste mesmo local com conflito de horário ({$conflito['nome_jogo']}).";
    }

    return null;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $filtro = aplicarFiltrosJogos();

        $sql = "SELECT 
                    jogos.id_jogo, 
                    jogos.nome_jogo, 
                    jogos.data_jogo, 
                    jogos.inicio_jogo, 
                    jogos.termino_jogo, 
                    jogos.status_jogo,
                    jogos.tempo_restante_jogo,
                    jogos.duracao_jogo,
                    jogos.tempo_extra_jogo,
                    jogos.data_inicio_real,
                    jogos.modalidades_id_modalidade,
                    jogos.locais_id_local,
                    modalidades.nome_modalidade,
                    modalidades.interclasses_id_interclasse AS id_interclasse,
                    modalidades.tipos_modalidades_id_tipo_modalidade,
                    locais.nome_local,
                    categorias.nome_categoria,
                    GROUP_CONCAT(DISTINCT COALESCE(e.nome_equipe, t.nome_turma) ORDER BY p.id_partida SEPARATOR ' vs ') AS equipes_nomes,
                    art_top.nome_usuario AS artilheiro_nome
                FROM jogos 
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
                LEFT JOIN partidas p ON p.jogos_id_jogo = jogos.id_jogo
                LEFT JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
                LEFT JOIN turmas t ON t.id_turma = e.turmas_id_turma
                LEFT JOIN (
                    SELECT a.jogos_id_jogo, u.nome_usuario,
                           ROW_NUMBER() OVER (PARTITION BY a.jogos_id_jogo ORDER BY COUNT(*) DESC, u.nome_usuario ASC) AS rn
                    FROM artilheiros a
                    INNER JOIN usuarios u ON u.id_usuario = a.usuarios_id_usuario
                    GROUP BY a.jogos_id_jogo, a.usuarios_id_usuario, u.nome_usuario
                ) art_top ON art_top.jogos_id_jogo = jogos.id_jogo AND art_top.rn = 1
                WHERE 1=1" . $filtro['sql'];

        $sql .= " GROUP BY jogos.id_jogo";
        $sql .= " ORDER BY jogos.data_jogo ASC, jogos.inicio_jogo ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            break;
        }
        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            break;
        }
        $res = $stmt->get_result();
        if (!$res) {
            echo json_encode(["success" => false, "message" => "Erro ao obter resultados."]);
            break;
        }

        $jogos = $res->fetch_all(MYSQLI_ASSOC);

        foreach ($jogos as &$jogo) {
            if ($jogo['status_jogo'] === 'Iniciado' && $jogo['data_inicio_real'] && $jogo['duracao_jogo']) {
                $inicioTs = strtotime($jogo['data_inicio_real']);
                $agoraTs = time();
                $decorrido = $agoraTs - $inicioTs;
                $totalProgramado = (int) $jogo['duracao_jogo'] + (int) ($jogo['tempo_extra_jogo'] ?? 0);
                $jogo['tempo_restante_calculado'] = max(0, $totalProgramado - $decorrido);
            } else {
                $jogo['tempo_restante_calculado'] = $jogo['tempo_restante_jogo'];
            }
        }
        unset($jogo);

        echo json_encode($jogos, JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        requerEscrita();
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->nome_jogo, $data->data_jogo, $data->modalidades_id_modalidade, $data->locais_id_local)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            break;
        }

        $inicio  = sgi_formatar_hora($data->inicio_jogo ?? '00:00:00');
        $termino = sgi_formatar_hora($data->termino_jogo ?? $data->terminno_jogo ?? '00:00:00');
        $status  = $data->status_jogo ?? 'Agendado';

        $erro_conflito = sgi_validar_conflito_local_horario(
            $conn,
            $data->data_jogo,
            $data->locais_id_local,
            $inicio,
            $termino
        );

        if ($erro_conflito) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => $erro_conflito]);
            break;
        }

        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, modalidades_id_modalidade, locais_id_local, status_jogo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssiis",
            $data->nome_jogo,
            $data->data_jogo,
            $inicio,
            $termino,
            $data->modalidades_id_modalidade,
            $data->locais_id_local,
            $status
        );

        try {
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Jogo cadastrado com sucesso!",
                    "id" => $conn->insert_id
                ]);
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Erro de integridade: Verifique se o ID da Modalidade ou do Local existem.",
                "detalhes" => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        requerOperacaoJogo();
        garantirInterclasseAtivo($conn);

        $nivel = (int)$_SESSION['nivel'];
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_jogo)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID do jogo é obrigatório."]);
            break;
        }

        if ($nivel === 2 && (isset($data->data_jogo) || isset($data->locais_id_local) || isset($data->modalidades_id_modalidade))) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Mesários só podem alterar o status ou placar do jogo."]);
            break;
        }

        // Buscar estado atual do jogo
        $id_jogo_val = (int)$data->id_jogo;
        $ck = $conn->prepare("SELECT data_jogo, inicio_jogo, termino_jogo, locais_id_local, duracao_jogo, tempo_extra_jogo, data_inicio_real FROM jogos WHERE id_jogo = ?");
        $ck->bind_param("i", $id_jogo_val);
        $ck->execute();
        $cur = $ck->get_result()->fetch_assoc();
        $ck->close();

        if (!$cur) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Jogo não encontrado."]);
            break;
        }

        // Normalização dos valores enviados ou fallback para o valor do banco
        $data_val    = $data->data_jogo ?? $cur['data_jogo'];
        $inicio_raw  = $data->inicio_jogo ?? $cur['inicio_jogo'];
        $termino_raw = $data->termino_jogo ?? $data->terminno_jogo ?? $cur['termino_jogo'];
        
        $inicio_val  = sgi_formatar_hora($inicio_raw);
        $termino_val = sgi_formatar_hora($termino_raw);
        $local_val   = $data->locais_id_local ?? $cur['locais_id_local'];

        // 1. Validação de conflito de Local e Horário
        if (isset($data->data_jogo) || isset($data->inicio_jogo) || isset($data->termino_jogo) || isset($data->terminno_jogo) || isset($data->locais_id_local)) {
            $erro_conflito = sgi_validar_conflito_local_horario($conn, $data_val, $local_val, $inicio_val, $termino_val, $id_jogo_val);
            if ($erro_conflito) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => $erro_conflito]);
                break;
            }
        }

        // 2. Validação do horário do turno das turmas
        $time_changed = $inicio_val !== $cur['inicio_jogo'] || $termino_val !== $cur['termino_jogo'];
        if ($time_changed) {
            $erro_turno = sgi_validar_horario_turmas($conn, $id_jogo_val, $inicio_val, $termino_val);
            if ($erro_turno) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => $erro_turno]);
                break;
            }
        }

        // 3. Validação de data passada
        if (isset($data->data_jogo) && $data->data_jogo < date('Y-m-d')) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => "Não é permitido agendar um jogo para uma data passada."]);
            break;
        }

        $campos = [];
        $params = [];
        $types = "";

        if (isset($data->nome_jogo)) {
            $campos[] = "nome_jogo = ?";
            $params[] = $data->nome_jogo;
            $types .= "s";
        }
        if (isset($data->data_jogo)) {
            $campos[] = "data_jogo = ?";
            $params[] = $data->data_jogo;
            $types .= "s";
        }
        if (isset($data->inicio_jogo)) {
            $campos[] = "inicio_jogo = ?";
            $params[] = $inicio_val;
            $types .= "s";
        }
        if (isset($data->termino_jogo) || isset($data->terminno_jogo)) {
            $campos[] = "termino_jogo = ?";
            $params[] = $termino_val;
            $types .= "s";
        }
        if (isset($data->tempo_restante_jogo)) {
            $campos[] = "tempo_restante_jogo = ?";
            $params[] = (int) $data->tempo_restante_jogo;
            $types .= "i";
        }
        if (isset($data->duracao_jogo)) {
            $campos[] = "duracao_jogo = ?";
            $params[] = (int) $data->duracao_jogo;
            $types .= "i";
        }
        if (isset($data->tempo_extra_jogo)) {
            $campos[] = "tempo_extra_jogo = ?";
            $params[] = (int) $data->tempo_extra_jogo;
            $types .= "i";
        }
        if (isset($data->status_jogo)) {
            $campos[] = "status_jogo = ?";
            $params[] = $data->status_jogo;
            $types .= "s";
        }
        if (isset($data->modalidades_id_modalidade)) {
            $campos[] = "modalidades_id_modalidade = ?";
            $params[] = $data->modalidades_id_modalidade;
            $types .= "i";
        }
        if (isset($data->locais_id_local)) {
            $campos[] = "locais_id_local = ?";
            $params[] = $data->locais_id_local;
            $types .= "i";
        }

        $novoStatus = $data->status_jogo ?? null;

        if ($novoStatus === 'Iniciado' && !isset($data->data_inicio_real)) {
            $campos[] = "data_inicio_real = NOW()";
        } elseif ($novoStatus === 'Pausado' || $novoStatus === 'Concluido') {
            if (!isset($data->tempo_restante_jogo)) {
                if ($cur['data_inicio_real'] && $cur['duracao_jogo']) {
                    $inicioTs = strtotime($cur['data_inicio_real']);
                    $agoraTs = time();
                    $decorrido = $agoraTs - $inicioTs;
                    $total = (int) $cur['duracao_jogo'] + (int) ($cur['tempo_extra_jogo'] ?? 0);
                    $restante = max(0, $total - $decorrido);
                    $campos[] = "tempo_restante_jogo = ?";
                    $params[] = $restante;
                    $types .= "i";
                }
            }
            $campos[] = "data_inicio_real = NULL";
        }

        if (empty($campos)) {
            echo json_encode(["success" => false, "message" => "Nenhum dado enviado para atualização."]);
            break;
        }

        $sql = "UPDATE jogos SET " . implode(", ", $campos) . " WHERE id_jogo = ?";
        $params[] = $id_jogo_val;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Jogo atualizado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao atualizar: " . $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
        break;
}