<?php
$titulo = "Chaveamentos";
$textTop = "Chaveamentos";
$btnVoltar = true;

if (isset($_GET['ajax'])) {
    (session_status() === PHP_SESSION_NONE) && session_start();
    if (!isset($_SESSION['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
        exit;
    }
    require_once dirname(__DIR__, 3) . '/config/db.php';
    header('Content-Type: application/json; charset=utf-8');

    // GET: listar alunos de uma modalidade individual + turma
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['ajax'] === 'alunos_individual') {
        $idMod = (int) ($_GET['id_modalidade'] ?? 0);
        $idTurma = (int) ($_GET['id_turma'] ?? 0);
        if (!$idMod || !$idTurma) {
            echo json_encode([]);
            exit;
        }
        // primeiro tenta buscar alunos vinculados à equipe desta modalidade+turma
        $st = $conn->prepare(
            'SELECT u.id_usuario, u.nome_usuario, u.matricula_usuario
             FROM equipes e
             INNER JOIN equipes_has_usuarios ehu ON e.id_equipe = ehu.equipes_id_equipe
             INNER JOIN usuarios u ON ehu.usuarios_id_usuario = u.id_usuario
             WHERE e.modalidades_id_modalidade = ? AND e.turmas_id_turma = ? AND e.status_equipe = \'1\' AND u.status_usuario = \'1\'
             ORDER BY u.nome_usuario ASC'
        );
        $st->bind_param('ii', $idMod, $idTurma);
        $st->execute();
        $res = $st->get_result();
        $alunos = $res->fetch_all(MYSQLI_ASSOC);
        $st->close();

        // fallback: todos os alunos da turma
        if (empty($alunos)) {
            $st2 = $conn->prepare(
                'SELECT id_usuario, nome_usuario, matricula_usuario
                 FROM usuarios
                 WHERE turmas_id_turma = ? AND status_usuario = \'1\' AND nivel_usuario = \'3\'
                 ORDER BY nome_usuario ASC'
            );
            if ($st2) {
                $st2->bind_param('i', $idTurma);
                $st2->execute();
                $res2 = $st2->get_result();
                $alunos = $res2->fetch_all(MYSQLI_ASSOC);
                $st2->close();
            }
        }

        echo json_encode($alunos);
        exit;
    }

    // POST: salvar pontuação individual
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['ajax'] === 'pontos_individual') {
        $idModalidade = (int) ($_POST['id_modalidade'] ?? 0);
        $idTurma1 = (int) ($_POST['id_turma_1'] ?? 0);
        $idTurma2 = (int) ($_POST['id_turma_2'] ?? 0);
        $idTurma3 = (int) ($_POST['id_turma_3'] ?? 0);
        $idAluno1 = (int) ($_POST['id_aluno_1'] ?? 0);
        $idAluno2 = (int) ($_POST['id_aluno_2'] ?? 0);
        $idAluno3 = (int) ($_POST['id_aluno_3'] ?? 0);
        if (!$idModalidade || (!$idTurma1 && !$idTurma2 && !$idTurma3)) {
            echo json_encode(['success' => false, 'message' => 'Selecione ao menos o 1º lugar.']);
            exit;
        }

        $st = $conn->prepare('SELECT interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $st->bind_param('i', $idModalidade);
        $st->execute();
        $modRow = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$modRow) {
            echo json_encode(['success' => false, 'message' => 'Modalidade não encontrada.']);
            exit;
        }
        $idInter = (int) $modRow['interclasses_id_interclasse'];

        $st2 = $conn->prepare('SELECT ponto_1_lugar, ponto_2_lugar, ponto_3_lugar FROM interclasses WHERE id_interclasse = ? LIMIT 1');
        $st2->bind_param('i', $idInter);
        $st2->execute();
        $ptRow = $st2->get_result()->fetch_assoc();
        $st2->close();
        if (!$ptRow) {
            echo json_encode(['success' => false, 'message' => 'Interclasse não configurado.']);
            exit;
        }

        $p1 = (int) ($ptRow['ponto_1_lugar'] ?? 0);
        $p2 = (int) ($ptRow['ponto_2_lugar'] ?? 0);
        $p3 = (int) ($ptRow['ponto_3_lugar'] ?? 0);

        // Buscar nome da modalidade para registrar na tabela pontuacoes
        $stM = $conn->prepare('SELECT nome_modalidade FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $stM->bind_param('i', $idModalidade);
        $stM->execute();
        $modNome = $stM->get_result()->fetch_assoc()['nome_modalidade'] ?? 'Individual';
        $stM->close();

        $conn->begin_transaction();
        try {
            $stP = $conn->prepare(
                'UPDATE turmas SET pontuacao_turma = pontuacao_turma + ? WHERE id_turma = ? LIMIT 1'
            );
            $stIns = $conn->prepare(
                'INSERT INTO pontuacoes (nome_pontuacao, valor_pontuacao, jogos_id_jogo, usuarios_id_usuario) VALUES (?, ?, NULL, ?)'
            );

            if ($idTurma1 && $p1) {
                $stP->bind_param('ii', $p1, $idTurma1);
                $stP->execute();
                if ($idAluno1) {
                    $nomePonto = "1º Lugar - $modNome";
                    $stIns->bind_param('sii', $nomePonto, $p1, $idAluno1);
                    $stIns->execute();
                }
            }
            if ($idTurma2 && $p2) {
                $stP->bind_param('ii', $p2, $idTurma2);
                $stP->execute();
                if ($idAluno2) {
                    $nomePonto = "2º Lugar - $modNome";
                    $stIns->bind_param('sii', $nomePonto, $p2, $idAluno2);
                    $stIns->execute();
                }
            }
            if ($idTurma3 && $p3) {
                $stP->bind_param('ii', $p3, $idTurma3);
                $stP->execute();
                if ($idAluno3) {
                    $nomePonto = "3º Lugar - $modNome";
                    $stIns->bind_param('sii', $nomePonto, $p3, $idAluno3);
                    $stIns->execute();
                }
            }
            $stP->close();
            $stIns->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Pontuação individual salva! ($p1 / $p2 / $p3 pts)"]);
        } catch (Throwable $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    body {
        background: #f4f6f9;
    }

    .section-title {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1f2937;
    }

    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }

    .pontuacao-card {
        transition: .2s ease;
        min-height: 180px;
    }

    .pontuacao-card:hover {
        transform: translateY(-4px);
    }

    .pontuacao-numero {
        font-size: 3rem;
        font-weight: 700;
        color: #111827;
    }

    .btn-pontos {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #6b7280;
    }

    .btn-pontos:hover {
        color: #dc2626;
    }

    .filtro-box {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }

    .table-custom thead {
        background: #f8fafc;
    }

    .table-custom th {
        color: #6b7280;
        font-size: .9rem;
        font-weight: 600;
    }

    .status {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
    }

    .status-success {
        background: #dcfce7;
        color: #166534;
    }

    .btn-gerar {
        background: #E30613;
        border: none;
        padding: 12px 24px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
    }

    .btn-gerar:hover {
        background: #bb0812;
    }

    .search-input {
        border-radius: 12px;
        padding: 10px 14px;
    }
</style>

<main class="main-desktop-layout py-4">

    <div class="container-fluid" style="max-width: 92%;">

        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarPontuacao"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

            <h2 class="section-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-diagram-3"></i>
                Gerenciamento de Chaveamentos
            </h2>

            <p class="text-muted mb-0">
                Configure pontuações, filtre modalidades e acompanhe os jogos.
            </p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Pontuações</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-danger fw-bold px-4" id="btnSalvarPontuacao" onclick="salvarPontuacao()">
                    <i class="bi bi-check-lg me-1"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn btn-dark fw-bold px-4 d-none">
                    Continuar <i class="bi bi-arrow-right-circle ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #e2b714;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-trophy fs-4" style="color:#e2b714;"></i>

                        
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-1">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #9ca3af;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award fs-4" style="color:#9ca3af;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-2">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #b87333;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award-fill fs-4" style="color:#b87333;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-3">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #dc2626;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-heart-fill fs-4 text-danger"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            MULTIPLICADOR
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-arr">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div class="filtro-box mb-4">

            <div class="row g-3 align-items-end">

                <div class="col-lg-3">
                    <label class="form-label fw-semibold">
                        Modalidade
                    </label>

                    <select class="form-select" id="selectModalidade">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>

                <div class="col-lg-3 d-grid">
                    <button class="btn-gerar" id="btnGerarChaveamento" onclick="gerarChaveamento()">
                        <i class="bi bi-diagram-3-fill me-2"></i>
                        Gerar Chaveamento
                    </button>
                </div>
                <div class="col-lg-6">
                    <div id="msgChaveamento" class="mt-2"></div>
                    <div id="linkVerArvore" class="mt-2 d-none">
                        <a href="#" id="btnVerArvore" class="btn btn-outline-danger btn-sm fw-bold">
                            <i class="bi bi-diagram-3-fill me-1"></i> Ver árvore do chaveamento
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <div id="secaoIndividual" class="card card-custom mb-4 d-none">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-person-rolodex me-2"></i>
                    Pontuação Individual
                </h5>
                <p class="text-muted small mb-3">Selecione a turma e o aluno para 1º, 2º e 3º lugar. Os pontos da turma serão adicionados automaticamente conforme a configuração do interclasse.</p>

                <div class="row g-4">
                    <div class="col-md-4 border-end">
                        <div class="p-2">
                            <label class="form-label fw-bold text-warning"><i class="bi bi-trophy-fill"></i> 1º Lugar</label>
                            <label class="form-label small text-muted mt-1">Turma</label>
                            <select class="form-select form-select-sm mb-2" id="selIndivT1" onchange="carregarAlunosIndiv(1)"><option value="">Selecione a turma</option></select>
                            <label class="form-label small text-muted">Aluno</label>
                            <select class="form-select form-select-sm" id="selIndivA1"><option value="">Selecione o aluno</option></select>
                        </div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="p-2">
                            <label class="form-label fw-bold text-secondary"><i class="bi bi-award-fill"></i> 2º Lugar</label>
                            <label class="form-label small text-muted mt-1">Turma</label>
                            <select class="form-select form-select-sm mb-2" id="selIndivT2" onchange="carregarAlunosIndiv(2)"><option value="">Selecione a turma</option></select>
                            <label class="form-label small text-muted">Aluno</label>
                            <select class="form-select form-select-sm" id="selIndivA2"><option value="">Selecione o aluno</option></select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2">
                            <label class="form-label fw-bold" style="color:#b87333;"><i class="bi bi-award"></i> 3º Lugar</label>
                            <label class="form-label small text-muted mt-1">Turma</label>
                            <select class="form-select form-select-sm mb-2" id="selIndivT3" onchange="carregarAlunosIndiv(3)"><option value="">Selecione a turma</option></select>
                            <label class="form-label small text-muted">Aluno</label>
                            <select class="form-select form-select-sm" id="selIndivA3"><option value="">Selecione o aluno</option></select>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex align-items-center gap-3">
                    <button class="btn btn-danger fw-bold px-4" onclick="salvarPontosIndividual()">
                        <i class="bi bi-check-lg me-1"></i> Salvar Pontuação Individual
                    </button>
                    <div id="msgIndividual" class="small"></div>
                </div>
            </div>
        </div>

        <div id="secaoJogos">
            <div class="card card-custom">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold">Jogos Realizados</h4>
                            <span class="text-muted">Histórico de partidas concluídas.</span>
                        </div>
                        <input type="text" class="form-control search-input" placeholder="Buscar partida..." style="width:300px" id="inputBuscaJogo">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Partida</th>
                                    <th>Modalidade</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyJogos">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Carregando jogos...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal Editar Jogo -->
<div class="modal fade" id="modalEditarJogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Editar Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarJogo">
                    <input type="hidden" id="editIdJogo">
                    <label for="editDataJogo" class="form-label">Data</label>
                    <input id="editDataJogo" type="date" class="form-control mb-3" required>
                    <label for="editInicioJogo" class="form-label">Horário de início</label>
                    <input id="editInicioJogo" type="time" class="form-control mb-3" required>
                    <label for="editTerminoJogo" class="form-label">Horário de término</label>
                    <input id="editTerminoJogo" type="time" class="form-control mb-3">
                    <label for="editLocalJogo" class="form-label">Local</label>
                    <select id="editLocalJogo" class="form-select mb-3"></select>
                    <div id="msgEditarJogo" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoJogo">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';
    let modalidadesCache = [];

    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let atual = parseInt(elemento.innerText);
        if (isNaN(atual)) atual = 0;
        if (atual + valor >= 0) {
            elemento.innerText = atual + valor;
        }
    }

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclassePontuacao').innerText = dados?.nome_interclasse || 'Interclasse';
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);

        const btnBack = document.getElementById('btnVoltarPontuacao');
        if (btnBack) {
            btnBack.href = `./dashboard.php?id=${idInterclasse}`;
        }

        if (dados) {
            document.getElementById('pontos-1').innerText = dados.ponto_1_lugar || 0;
            document.getElementById('pontos-2').innerText = dados.ponto_2_lugar || 0;
            document.getElementById('pontos-3').innerText = dados.ponto_3_lugar || 0;
            document.getElementById('pontos-arr').innerText = dados.valor_item_arrecadacao || 0;
        }
        return idInterclasse;
    }

    async function carregarModalidades() {
        try {
            const resp = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            modalidadesCache = Array.isArray(data) ? data : [];
            const select = document.getElementById('selectModalidade');
            select.innerHTML = '<option value="">Todas as modalidades</option>';
            modalidadesCache.forEach(mod => {
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                select.innerHTML += `<option value="${mod.id_modalidade}">${mod.nome_modalidade}${genero}${categoria}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar modalidades:", e);
        }
    }

    function formatarNomePartida(jogo) {
        const tag = jogo.nome_jogo || '';
        const mm = tag.match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 8: 'Oitavas de final', 4: 'Quartas de final', 2: 'Semifinal', 1: 'Final' };
            const fase = fases[largura] || 'Fase ' + largura;
            const confronto = slot + 1;
            const equipes = (jogo.equipes_nomes || '').trim();
            const sufixo = kind === 'B' ? ' (bye)' : '';
            if (equipes) {
                return `${fase} — confronto ${confronto}: ${equipes}${sufixo}`;
            }
            return `${fase} — confronto ${confronto}${sufixo}`;
        }
        return tag || '---';
    }

    function atualizarSecaoIndividual() {
        const idMod = document.getElementById('selectModalidade').value;
        const secao = document.getElementById('secaoIndividual');
        const jogos = document.getElementById('secaoJogos');
        if (!idMod) {
            secao.classList.add('d-none');
            if (jogos) jogos.style.display = '';
            return;
        }
        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idMod);
        const isIndividual = mod && Number(mod.id_tipo_modalidade) === 2;
        if (isIndividual) {
            secao.classList.remove('d-none');
            if (jogos) jogos.style.display = 'none';
            carregarTurmasIndividuais(mod.categorias_id_categoria);
        } else {
            secao.classList.add('d-none');
            if (jogos) jogos.style.display = '';
        }
    }

    async function carregarTurmasIndividuais(idCategoria) {
        const turmaIds = ['selIndivT1', 'selIndivT2', 'selIndivT3'];
        const alunoIds = ['selIndivA1', 'selIndivA2', 'selIndivA3'];
        [...turmaIds, ...alunoIds].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<option value="">Carregando...</option>';
        });
        try {
            const resp = await fetch(`../../../api/turmas.php?id_categoria=${idCategoria}&id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            const turmas = Array.isArray(data) ? data : [];
            turmaIds.forEach(id => {
                const sel = document.getElementById(id);
                if (!sel) return;
                sel.innerHTML = '<option value="">Selecione a turma</option>';
                turmas.forEach(t => {
                    sel.innerHTML += `<option value="${t.id_turma}">${t.nome_turma}</option>`;
                });
            });
            alunoIds.forEach(id => {
                const sel = document.getElementById(id);
                if (sel) sel.innerHTML = '<option value="">Selecione a turma primeiro</option>';
            });
        } catch (e) {
            turmaIds.forEach(id => {
                const sel = document.getElementById(id);
                if (sel) sel.innerHTML = '<option value="">Erro ao carregar</option>';
            });
        }
    }

    window.carregarAlunosIndiv = async function(pos) {
        const idMod = document.getElementById('selectModalidade').value;
        const idTurma = document.getElementById('selIndivT' + pos).value;
        const selAluno = document.getElementById('selIndivA' + pos);
        if (!selAluno) return;
        if (!idMod || !idTurma) {
            selAluno.innerHTML = '<option value="">Selecione a turma primeiro</option>';
            return;
        }
        selAluno.innerHTML = '<option value="">Carregando...</option>';
        try {
            const url = `${window.location.pathname}?ajax=alunos_individual&id_modalidade=${idMod}&id_turma=${idTurma}&_t=${Date.now()}`;
            const resp = await fetch(url);
            const alunos = await resp.json();
            if (!Array.isArray(alunos) || alunos.length === 0) {
                selAluno.innerHTML = '<option value="">Nenhum aluno encontrado para esta turma</option>';
                return;
            }
            selAluno.innerHTML = '<option value="">Selecione o aluno</option>';
            alunos.forEach(a => {
                selAluno.innerHTML += `<option value="${a.id_usuario}">${a.nome_usuario} (${a.matricula_usuario})</option>`;
            });
        } catch (e) {
            selAluno.innerHTML = '<option value="">Erro ao carregar alunos</option>';
        }
    };

    window.salvarPontosIndividual = async function() {
        const idMod = document.getElementById('selectModalidade').value;
        const t1 = document.getElementById('selIndivT1').value;
        const t2 = document.getElementById('selIndivT2').value;
        const t3 = document.getElementById('selIndivT3').value;
        const a1 = document.getElementById('selIndivA1').value;
        const a2 = document.getElementById('selIndivA2').value;
        const a3 = document.getElementById('selIndivA3').value;
        const msg = document.getElementById('msgIndividual');

        if (!t1) { msg.innerHTML = '<span class="text-danger">Selecione ao menos o 1º lugar.</span>'; return; }

        const fd = new FormData();
        fd.append('id_modalidade', idMod);
        fd.append('id_turma_1', t1);
        fd.append('id_turma_2', t2 || '0');
        fd.append('id_turma_3', t3 || '0');
        fd.append('id_aluno_1', a1 || '0');
        fd.append('id_aluno_2', a2 || '0');
        fd.append('id_aluno_3', a3 || '0');

        msg.innerHTML = '<span class="text-muted">Salvando...</span>';
        try {
            const resp = await fetch(window.location.pathname + '?ajax=pontos_individual', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                msg.innerHTML = `<span class="text-success fw-bold">${data.message}</span>`;
                ['selIndivT1', 'selIndivT2', 'selIndivT3'].forEach(id => {
                    document.getElementById(id).value = '';
                });
                ['selIndivA1', 'selIndivA2', 'selIndivA3'].forEach(id => {
                    document.getElementById(id).innerHTML = '<option value="">Selecione a turma primeiro</option>';
                });
            } else {
                msg.innerHTML = `<span class="text-danger">${data.message}</span>`;
            }
        } catch (e) {
            msg.innerHTML = `<span class="text-danger">Erro de conexão: ${e.message}</span>`;
        }
    }

    async function carregarJogos() {
        const tbody = document.getElementById('tbodyJogos');
        try {
            const idModalidade = document.getElementById('selectModalidade').value;
            let url = `../../../api/jogos.php?id_interclasse=${idInterclasse}`;
            if (idModalidade) url += `&id_modalidade=${idModalidade}`;

            const resp = await fetch(url);
            const data = await resp.json();
            const jogos = Array.isArray(data) ? data : [];

            if (!jogos.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Nenhum jogo encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = jogos.map(j => {
                let dataJogo = '---';
                if (j.data_jogo) {
                    try {
                        dataJogo = new Date(j.data_jogo + (j.inicio_jogo ? 'T' + j.inicio_jogo : '')).toLocaleString('pt-BR');
                    } catch (_) { dataJogo = j.data_jogo; }
                }
                const statusClass = j.status_jogo === 'Concluido' ? 'status-success' : '';
                return `<tr>
                    <td>${formatarNomePartida(j)}</td>
                    <td>${j.nome_modalidade || '---'}</td>
                    <td>${dataJogo}</td>
                    <td><span class="status ${statusClass}">${j.status_jogo || '---'}</span></td>
                    <td class="text-end">
                        <a href="./jogos.php?id_jogo=${j.id_jogo}" class="btn btn-link text-success p-0 me-2" title="Acessar Jogo">
                            <i class="bi bi-play-circle"></i>
                        </a>
                        <button class="btn btn-link btn-edit p-0 ms-2" title="Editar Jogo"
                            onclick="editarJogo(${j.id_jogo},'${j.data_jogo || ''}','${j.inicio_jogo || ''}','${j.termino_jogo || ''}',${j.locais_id_local || 'null'})">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            console.error("Erro ao carregar jogos:", e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Erro ao carregar jogos.</td></tr>';
        }
    }

    window.salvarPontuacao = async function() {
        const btn = document.getElementById('btnSalvarPontuacao');
        const pontos1 = parseInt(document.getElementById('pontos-1').innerText);
        const pontos2 = parseInt(document.getElementById('pontos-2').innerText);
        const pontos3 = parseInt(document.getElementById('pontos-3').innerText);
        const pontosArr = parseInt(document.getElementById('pontos-arr').innerText);

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const formData = new FormData();
            formData.append('ponto_1_lugar', pontos1);
            formData.append('ponto_2_lugar', pontos2);
            formData.append('ponto_3_lugar', pontos3);
            formData.append('valor_item_arrecadacao', pontosArr);

            const resp = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao salvar.');

            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvo!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
            }, 2000);
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
        }
    };

    window.gerarChaveamento = async function() {
        const idModalidade = document.getElementById('selectModalidade').value;
        const msgEl = document.getElementById('msgChaveamento');
        const btn = document.getElementById('btnGerarChaveamento');

        if (!idModalidade) {
            msgEl.innerHTML = '<p class="text-danger fw-bold mb-0">Selecione uma modalidade primeiro.</p>';
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const tipoId = mod ? Number(mod.id_tipo_modalidade) : 0;

        if (tipoId === 2) {
            msgEl.innerHTML = '<p class="text-info fw-bold mb-0">Use a seção "Pontuação Individual" abaixo para registrar 1º, 2º e 3º lugar.</p>';
            return;
        }

        msgEl.innerHTML = '<p class="text-muted fw-bold mb-0">Gerando chaveamento...</p>';

        try {
            btn.disabled = true;
            const resp = await fetch('../../../api/chaveamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idModalidade), tipo_modalidade: 'mata_mata' })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao gerar chaveamento.');

            msgEl.innerHTML = `<p class="text-success fw-bold mb-0">${data.message} (${data.jogos_criados} jogos criados).</p>`;
            const linkArvore = document.getElementById('linkVerArvore');
            linkArvore.classList.remove('d-none');
            document.getElementById('btnVerArvore').href = `./chaveamento_arvore.php?id=${idInterclasse}`;
            carregarJogos();
        } catch (err) {
            msgEl.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
        }
    };

    window.editarJogo = async function(id, data, inicio, termino, idLocal) {
        document.getElementById('editIdJogo').value = id;
        document.getElementById('editDataJogo').value = data;
        document.getElementById('editInicioJogo').value = inicio ? inicio.slice(0, 5) : '';
        document.getElementById('editTerminoJogo').value = termino ? termino.slice(0, 5) : '';
        document.getElementById('msgEditarJogo').innerHTML = '';

        const selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';
        try {
            const resp = await fetch('../../../api/locais.php');
            const result = await resp.json();
            const locais = (result && result.data) || [];
            selectLocal.innerHTML = '<option value="">Selecione um local</option>';
            locais.forEach(l => {
                const sel = Number(l.id_local) === Number(idLocal) ? ' selected' : '';
                selectLocal.innerHTML += `<option value="${l.id_local}"${sel}>${l.nome_local}</option>`;
            });
        } catch (e) {
            selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarJogo')).show();
    }

    document.getElementById('formEditarJogo').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editIdJogo').value;
        const data = document.getElementById('editDataJogo').value;
        const inicio = document.getElementById('editInicioJogo').value;
        const termino = document.getElementById('editTerminoJogo').value;
        const btn = document.getElementById('btnSalvarEdicaoJogo');
        const msg = document.getElementById('msgEditarJogo');

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            const idLocal = document.getElementById('editLocalJogo').value;
            const resp = await fetch('../../../api/jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_jogo: Number(id),
                    data_jogo: data,
                    inicio_jogo: inicio + ':00',
                    termino_jogo: termino ? termino + ':00' : null,
                    locais_id_local: idLocal ? Number(idLocal) : null
                })
            });
            const result = await resp.json();
            if (!result.success) throw new Error(result.message || 'Erro ao salvar.');
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Jogo atualizado com sucesso.</p>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarJogo')).hide();
            carregarJogos();
        } catch (err) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Salvar';
        }
    });

    document.getElementById('selectModalidade').addEventListener('change', () => {
        document.getElementById('msgChaveamento').innerHTML = '';
        carregarJogos();
        atualizarSecaoIndividual();
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        const btnContinuar = document.getElementById('btnContinuarPontuacao');
        if (btnContinuar) {
            btnContinuar.href = `./edicao_resumo.php?id=${idInterclasse}&modo=create`;
            if (modo === 'create') btnContinuar.classList.remove('d-none');
        }

        await Promise.all([
            carregarModalidades(),
            carregarJogos()
        ]);
        atualizarSecaoIndividual();
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
