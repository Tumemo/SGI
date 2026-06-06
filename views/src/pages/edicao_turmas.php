<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>
<main class="d-md-none">
    <a href="./dashboard.php" id="btnVoltarTurmasMobile" class="btn btn-danger btn-sm mt-3 ms-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <p class="text-secondary text-center my-3">Editar detalhes turmas</p>

    <input type="text" id="inputBuscaTurmaMobile" class="form-control w-75 mx-auto mb-3 form-control-sm rounded-pill" placeholder="Buscar turma">

    <div id="listaTurmasMobile" class="px-3">
        <p class="text-muted text-center">Carregando...</p>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout" id="viewTurmasGestaoDesk">
    <div class="container-fluid px-0">
        <a href="./dashboard.php" id="btnVoltarTurmasDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <div class="row g-4 mx-0">
            <div class="col-md-4 px-0 px-md-2">
                <div class="bg-white rounded-4 shadow-sm overflow-hidden border-0">
                    <div class="p-3 d-flex align-items-center gap-2" style="background-color: #ed1c24; color: white;">
                        <i class="bi bi-plus-circle fs-5" style="cursor: pointer;" title="Adicionar Categoria"></i>
                        <h6 class="mb-0 fw-bold fs-5">Categorias</h6>
                    </div>

                    <div id="listaCategorias" class="list-group list-group-flush" style="max-height: 60vh; overflow-y: auto;">
                        <p class="text-muted p-3 mb-0 text-center">Carregando categorias...</p>
                    </div>
                </div>
            </div>

            <div class="col-md-8 px-0 px-md-2 d-flex flex-column gap-3">

                <div class="bg-white rounded-3 shadow-sm p-2 d-flex align-items-center">
                    <i class="bi bi-search text-muted ms-3"></i>
                    <input type="text" id="inputBuscaTurma" class="form-control border-0 shadow-none bg-transparent" placeholder="Buscar turma">
                    <button class="btn fw-bold px-4 text-nowrap" style="color: #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalCriarTurma">
                        + Adicionar
                    </button>
                </div>

                <div id="listaTurmas" class="d-flex flex-column gap-3 pe-2" style="max-height: 60vh; overflow-y: auto;">
                    <div class="text-center mt-5">
                        <p class="text-muted fs-5">Selecione uma categoria ao lado para ver as turmas.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCriarTurma" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 p-2">
                <div class="modal-header border-0 pb-0 justify-content-center">
                    <h5 class="modal-title fw-bold text-center w-100" style="color: #ed1c24;">
                        ADICIONAR TURMA
                    </h5>
                </div>
                <form id="formTurma">
                    <div class="modal-body pt-3 pb-3">
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Nome da turma:</label>
                            <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary" placeholder="Ex: 9º Ano A" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputNomeTurma" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Nome fantasia:</label>
                            <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary" placeholder="Ex: Turma dos Campeões" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputNomeFantasiaTurma">
                        </div>
                        <div class="mb-3">
                            <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Turno:</label>
                            <select class="form-select form-select-lg shadow-sm rounded-3 text-secondary" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputTurnoTurma">
                                <option value="">Selecione o turno</option>
                                <option value="Manhã">Manhã</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noite">Noite</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2 flex-wrap">
                        <div id="msgTurma" class="w-100 text-center small mb-2"></div>
                        <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2" data-bs-dismiss="modal" style="color: #ed1c24; border: 1px solid #ed1c24;">
                            Cancelar
                        </button>
                        <button type="submit" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;" id="btnSalvarTurma">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    const API = '../../../api/';
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const idCategoriaUrl = urlParams.get('id_categoria');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    if (!idInterclasse) {
        alert("Erro: Nenhum interclasse selecionado! Você será redirecionado.");
        window.location.href = "home.php";
    }

    function getEl(id) {
        return document.getElementById(id);
    }

    // ─── BACK BUTTONS ────────────────────────────────────────────────
    if (idInterclasse) {
        const backs = ['btnVoltarTurmasMobile', 'btnVoltarTurmasDesk'];
        backs.forEach(id => {
            const el = getEl(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // GESTÃO DE TURMAS
    // ═══════════════════════════════════════════════════════════════════
    let categoriaSelecionadaId = null;
    let todasTurmasAtuais = [];

    async function carregarCategorias() {
        try {
            const response = await fetch(`${API}categorias.php?id_interclasse=${idInterclasse}`);
            const categorias = await response.json();
            const container = getEl('listaCategorias');
            container.innerHTML = '';

            if (categorias && categorias.length > 0) {
                categorias.forEach(cat => {
                    const btn = document.createElement('button');
                    btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-4 border-bottom border-0 fs-6 fw-medium text-secondary';
                    btn.style.cursor = 'pointer';
                    btn.innerHTML = `${cat.nome_categoria} <i class="bi bi-chevron-right text-muted"></i>`;

                    btn.onclick = () => {
                        document.querySelectorAll('#listaCategorias button').forEach(b => {
                            b.classList.remove('bg-light', 'text-dark', 'fw-bold');
                            b.classList.add('text-secondary');
                        });
                        btn.classList.add('bg-light', 'text-dark', 'fw-bold');
                        btn.classList.remove('text-secondary');
                        categoriaSelecionadaId = cat.id_categoria;
                        carregarTurmas(categoriaSelecionadaId);
                    };

                    container.appendChild(btn);
                    if (idCategoriaUrl && String(idCategoriaUrl) === String(cat.id_categoria)) {
                        btn.click();
                    }
                });
            } else {
                container.innerHTML = '<p class="text-muted p-3 text-center mb-0">Nenhuma categoria encontrada.</p>';
            }
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
        }
    }

    async function carregarTurmas(idCategoria) {
        try {
            const response = await fetch(`${API}turmas.php?id_categoria=${idCategoria}&id_interclasse=${idInterclasse}`);
            const turmas = await response.json();
            todasTurmasAtuais = turmas;
            renderizarTurmas(turmas);
        } catch (error) {
            console.error("Erro ao carregar turmas:", error);
        }
    }

    function renderizarTurmas(turmas) {
        const container = getEl('listaTurmas');
        const containerMob = getEl('listaTurmasMobile');
        container.innerHTML = '';
        if (containerMob) containerMob.innerHTML = '';

        if (turmas && turmas.length > 0) {
            const html = turmas.map(turma => `
                <a href="./turma_alunos.php?id=${idInterclasse}&id_turma=${turma.id_turma}" class="text-decoration-none">
                    <div class="bg-white rounded-3 shadow-sm p-4 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark fs-5">${esc(turma.nome_turma)}</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            `).join('');
            container.innerHTML = html;
            if (containerMob) containerMob.innerHTML = html;
        } else {
            const msg = '<div class="text-center mt-5"><p class="text-muted fs-5">Nenhuma turma adicionada nesta categoria.</p></div>';
            container.innerHTML = msg;
            if (containerMob) containerMob.innerHTML = msg;
        }
    }

    // Search sync between mobile and desktop
    const inputBuscaTurma = getEl('inputBuscaTurma');
    const inputBuscaTurmaMob = getEl('inputBuscaTurmaMobile');

    function filtrarTurmasGestao(termo) {
        const t = (termo || '').toLowerCase();
        const filtradas = todasTurmasAtuais.filter(tur => tur.nome_turma.toLowerCase().includes(t));
        renderizarTurmas(filtradas);
    }
    if (inputBuscaTurma) {
        inputBuscaTurma.addEventListener('input', (e) => {
            if (inputBuscaTurmaMob) inputBuscaTurmaMob.value = e.target.value;
            filtrarTurmasGestao(e.target.value);
        });
    }
    if (inputBuscaTurmaMob) {
        inputBuscaTurmaMob.addEventListener('input', (e) => {
            if (inputBuscaTurma) inputBuscaTurma.value = e.target.value;
            filtrarTurmasGestao(e.target.value);
        });
    }

    // Create turma form
    const formTurma = getEl('formTurma');
    if (formTurma) {
        formTurma.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!categoriaSelecionadaId) {
                alert("Por favor, selecione uma categoria na lista ao lado primeiro!");
                const modalEl = getEl('modalCriarTurma');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                return;
            }

            const btnSalvar = getEl('btnSalvarTurma');
            const inputNome = getEl('inputNomeTurma');
            const inputNomeFantasia = getEl('inputNomeFantasiaTurma');
            const inputTurno = getEl('inputTurnoTurma');
            const msg = getEl('msgTurma');

            const mapaTurno = {
                'manhã': 'manha',
                'manha': 'manha',
                'tarde': 'tarde',
                'noite': 'noite',
                'integral': 'integral'
            };
            const turnoRaw = (inputTurno.value || '').trim().toLowerCase();
            const turnoNormalizado = mapaTurno[turnoRaw] || (turnoRaw || null);

            const dadosTurma = {
                interclasses_id_interclasse: parseInt(idInterclasse, 10),
                categorias_id_categoria: parseInt(String(categoriaSelecionadaId), 10),
                nome_turma: inputNome.value.trim(),
                nome_fantasia_turma: inputNomeFantasia.value.trim(),
                turno_turma: turnoNormalizado,
                status_turma: "1"
            };

            try {
                btnSalvar.disabled = true;
                msg.innerHTML = "Salvando turma...";
                const response = await fetch(`${API}turmas.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dadosTurma)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    todasTurmasAtuais = [...todasTurmasAtuais, {
                        id_turma: result.id_turma,
                        nome_turma: inputNome.value.trim(),
                        nome_fantasia_turma: inputNomeFantasia.value.trim(),
                        turno_turma: turnoNormalizado
                    }];
                    renderizarTurmas(todasTurmasAtuais);
                    msg.innerHTML = `<p class="text-success fw-bold mt-2 mb-0">Turma Adicionada!</p>`;
                    inputNome.value = '';
                    getEl('inputNomeFantasiaTurma').value = '';
                    getEl('inputTurnoTurma').value = '';
                    await carregarTurmas(categoriaSelecionadaId);
                    setTimeout(() => {
                        const modalEl = getEl('modalCriarTurma');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        msg.innerHTML = '';
                    }, 1500);
                } else {
                    alert("Erro ao criar turma: " + (result.message || "Erro desconhecido."));
                    msg.innerHTML = "";
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                alert("Erro de conexão.");
                msg.innerHTML = "";
            } finally {
                btnSalvar.disabled = false;
            }
        });
    }

    // ─── INIT ────────────────────────────────────────────────────────
    if (idInterclasse) {
        window.addEventListener('load', carregarCategorias);
    }
</script>

<?php
require_once '../componentes/footer.php';
?>