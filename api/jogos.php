<?php
require_once '../config/db.php';
require_once 'filtros.php';
require_once 'auth.php';
header('Content-Type: application/json');

function sgi_validar_horario_turmas($conn, $id_jogo, $inicio, $termino) {
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
                    modalidades.tipos_modalidades_id_tipo_modalidade,
                    locais.nome_local,
                    categorias.nome_categoria,
                    GROUP_CONCAT(DISTINCT t.nome_turma ORDER BY p.id_partida SEPARATOR ' vs ') AS equipes_nomes
                FROM jogos 
                INNER JOIN modalidades ON modalidades.id_modalidade = jogos.modalidades_id_modalidade 
                INNER JOIN locais ON locais.id_local = jogos.locais_id_local
                INNER JOIN categorias ON categorias.id_categoria = modalidades.categorias_id_categoria
                LEFT JOIN partidas p ON p.jogos_id_jogo = jogos.id_jogo
                LEFT JOIN equipes e ON e.id_equipe = p.equipes_id_equipe
                LEFT JOIN turmas t ON t.id_turma = e.turmas_id_turma
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

        // Calcular tempo restante no servidor para jogos em andamento
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

        $inicio = $data->inicio_jogo ?? '00:00:00';
        $termino = $data->termino_jogo ?? $data->terminno_jogo ?? '00:00:00';
        $status = $data->status_jogo ?? 'Agendado';

        $sql = "INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, termino_jogo, modalidades_id_modalidade, locais_id_local, status_jogo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiis",
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
            http_response_code(400); // Bad Request
            echo json_encode([
                "success" => false,
                "message" => "Erro de integridade: Verifique se o ID da Modalidade ou do Local existem.",
                "detalhes" => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        requerEscrita();
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_jogo)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "O ID do jogo é obrigatório."]);
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
            $params[] = $data->inicio_jogo;
            $types .= "s";
        }
        if (isset($data->termino_jogo)) {
            $campos[] = "termino_jogo = ?";
            $params[] = $data->termino_jogo;
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

        // Gerenciamento automático de data_inicio_real baseado na transição de status
        $novoStatus = $data->status_jogo ?? null;
        $idJogoPut = (int) $data->id_jogo;

        if ($novoStatus === 'Iniciado' && !isset($data->data_inicio_real)) {
            // Iniciar ou retomar: registrar data_inicio_real = NOW()
            $campos[] = "data_inicio_real = NOW()";
        } elseif ($novoStatus === 'Pausado' || $novoStatus === 'Concluido') {
            // Pausar ou concluir: calcular e salvar tempo_restante, limpar data_inicio_real
            if (!isset($data->tempo_restante_jogo)) {
                // Buscar estado atual para calcular
                $ck = $conn->prepare("SELECT duracao_jogo, tempo_extra_jogo, data_inicio_real FROM jogos WHERE id_jogo = ?");
                $ck->bind_param('i', $idJogoPut);
                $ck->execute();
                $cur = $ck->get_result()->fetch_assoc();
                $ck->close();

                if ($cur && $cur['data_inicio_real'] && $cur['duracao_jogo']) {
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

        if (isset($data->inicio_jogo) || isset($data->termino_jogo)) {
            $id_jogo_val = (int)$data->id_jogo;
            $inicio_val = $data->inicio_jogo ?? '00:00:00';
            $termino_val = $data->termino_jogo ?? '00:00:00';

            $ck_sql = "SELECT inicio_jogo, termino_jogo FROM jogos WHERE id_jogo = ?";
            $ck_stmt = $conn->prepare($ck_sql);
            $ck_stmt->bind_param("i", $id_jogo_val);
            $ck_stmt->execute();
            $cur = $ck_stmt->get_result()->fetch_assoc();
            $ck_stmt->close();

            $time_changed = !$cur || $inicio_val !== $cur['inicio_jogo'] || $termino_val !== $cur['termino_jogo'];
            if ($time_changed) {
                $erro_turno = sgi_validar_horario_turmas($conn, $id_jogo_val, $inicio_val, $termino_val);
                if ($erro_turno) {
                    http_response_code(422);
                    echo json_encode(["success" => false, "message" => $erro_turno]);
                    break;
                }
            }
        }

        if (isset($data->data_jogo)) {
            $hoje = date('Y-m-d');
            if ($data->data_jogo < $hoje) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Não é permitido agendar um jogo para uma data passada."]);
                break;
            }
        }

        $sql = "UPDATE jogos SET " . implode(", ", $campos) . " WHERE id_jogo = ?";
        $params[] = $data->id_jogo;
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
        echo json_encode(["message" => "Metodo não permitido"]);
        break;
}
