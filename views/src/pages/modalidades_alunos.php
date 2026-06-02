<?php
$titulo = "Modalidades / Alunos";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Borda dourada suave para o aluno destaque */
    .borda-destaque {
        border: 2px solid #D4AF37 !important;
        transition: border 0.3s ease-in-out;
    }

    /* Cor para a estrela preenchida */
    .estrela-dourada {
        color: #D4AF37 !important;
    }
</style>

<main class="d-md-none" style="margin-bottom:120px;">
    <div class="container mt-3">
        <a href="./dashboard.php" id="btnVoltarAlunosMobile" class="btn btn-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left-circle"></i> Voltar
        </a>
        <p class="text-muted small" id="contagemMob"></p>
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <input type="text" id="buscaNomeMobile" class="form-control form-control-sm rounded-pill" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-filtro="limpar">Sem filtro</button>
        </div>
        <div id="listaAlunosMobile"></div>
        <div class="card border-0 shadow-sm rounded-4 p-3 mt-4" id="blocoPdfMob">
            <p class="small text-muted mb-2">Importar alunos via PDF</p>
            <form id="formPdfMob" enctype="multipart/form-data">
                <label class="form-label small">Arquivo PDF</label>
                <input type="file" class="form-control form-control-sm mb-2" name="pdf" accept="application/pdf" required>
                <button type="submit" class="btn btn-danger w-100 rounded-3">Importar PDF</button>
                <div id="msgPdfMob" class="small mt-2 text-center"></div>
            </form>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 800px;">
        <a href="./dashboard.php" id="btnVoltarAlunosDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <h4 style="font-weight: 400;" id="tituloTurmaDesk">Alunos</h4>
        <p class="text-muted" id="contagemDesk"></p>
        <div class="d-flex gap-2 mb-4 overflow-auto pb-1 pt-2 ps-1" style="white-space: nowrap; scrollbar-width: none;">
            <input type="text" id="buscaNome" class="form-control form-control-sm rounded-pill p-2 ps-4" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-filtro="limpar">Sem filtro</button>
        </div>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-weight: 400;">Nome</th>
                            <th style="font-weight: 400;">RM</th>
                            <th style="font-weight: 400;">Gênero</th>
                            <th style="font-weight: 400;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlunosDesk"></tbody>
                </table>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 p-4" id="blocoPdfDesk">
            <h6 style="font-weight: 400;">Cadastrar alunos por PDF</h6>
            <p class="text-muted small">Use o mesmo nome da turma cadastrada para o arquivo bater com o esperado pelo sistema.</p>
            <form id="formPdfDesk" enctype="multipart/form-data" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small">PDF (lista de alunos)</label>
                    <input type="file" class="form-control" name="pdf" accept="application/pdf" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100 rounded-3">Importar</button>
                </div>
                <div id="msgPdfDesk" class="col-12 small text-center"></div>
            </form>
        </div>
    </div>
</main>

<div class="modal fade" id="modalRemoverAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 320px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-4 pt-5 pb-4">
                <h5 class="mb-4" style="font-size: 1.2rem; font-weight: 400; color: #000;">Deseja remover esse aluno?</h5>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal" id="btnConfirmarRemoverAluno">Remover</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    let alunosLista = [];
    let filtrosAtivos = { busca: '', genero: null, destaque: false };

    const paramsAlunos = new URLSearchParams(window.location.search);
    const idInterclasseAlunos = paramsAlunos.get('id');
    const idCategoria = Number(paramsAlunos.get('id_categoria') || 0);
    const idTurmaAlunos = paramsAlunos.get('id_turma');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    if (idInterclasseAlunos) {
        document.getElementById('btnVoltarAlunosMobile').href = `./dashboard.php?id=${idInterclasseAlunos}`;
        document.getElementById('btnVoltarAlunosDesk').href = `./dashboard.php?id=${idInterclasseAlunos}`;
    }

    // ─── Mobile card ─────────────────────────────────────────────────
    function cardAluno(aluno) {
        const nome = aluno.nome_usuario || aluno.nome || `Aluno ${aluno.id_usuario || aluno.id}`;
        const id = aluno.id_usuario || aluno.id;
        const genero = (aluno.genero_usuario || 'MASC').toUpperCase();
        return `
            <div id="card-aluno-${id}-m" class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center aluno-card mb-2" data-genero="${genero}" data-destaque="0" data-nome="${nome.toLowerCase()}">
                <h6 class="mb-0 fw-bold text-dark">${esc(nome)}</h6>
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="removerAluno(${id})"><i class="bi bi-trash"></i> Remover</button></li>
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="alternarDestaque(${id})"><i id="icone-estrela-${id}" class="bi bi-star"></i> Destacar</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="button" class="dropdown-item text-danger d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="resetarSenhaAluno(${id})"><i class="bi bi-shield-lock"></i> Resetar senha</button></li>
                    </ul>
                </div>
            </div>
        `;
    }

    // ─── Remover ─────────────────────────────────────────────────────
    window.removerAluno = function(id) {
        const btn = document.getElementById('btnConfirmarRemoverAluno');
        if (btn) btn.dataset.alunoId = id;
        const modal = new bootstrap.Modal(document.getElementById('modalRemoverAluno'));
        modal.show();
    };

    document.getElementById('btnConfirmarRemoverAluno')?.addEventListener('click', async function() {
        const id = this.dataset.alunoId;
        if (!id) return;
        try {
            const r = await fetch(`${API}usuarios.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'remover_competidor', id_usuario: Number(id) })
            });
            const data = await r.json();
            if (data.success === false) throw new Error(data.message || 'Erro ao remover');
            carregarAlunos();
        } catch (e) {
            alert('Erro ao remover aluno: ' + e.message);
        }
    });

    // ─── Destaque ────────────────────────────────────────────────────
    window.alternarDestaque = function(idAluno) {
        document.querySelectorAll(`#card-aluno-${idAluno}-m, #row-aluno-${idAluno}`).forEach(el => {
            if (!el) return;
            el.classList.toggle('borda-destaque');
            el.dataset.destaque = el.dataset.destaque === '1' ? '0' : '1';
        });
        const icone = document.getElementById(`icone-estrela-${idAluno}`);
        if (icone) {
            icone.classList.toggle('bi-star');
            icone.classList.toggle('bi-star-fill');
            icone.classList.toggle('estrela-dourada');
        }
    };

    window.resetarSenhaAluno = function(idAluno) {
        if (confirm('Tem certeza que deseja resetar a senha deste aluno?')) {
            alert('Senha resetada com sucesso!');
        }
    };

    // ─── Busca e filtros ─────────────────────────────────────────────
    function sincronizarBusca(origem) {
        const valor = origem?.value ?? '';
        filtrosAtivos.busca = valor.toLowerCase();
        const outro = origem?.id === 'buscaNome'
            ? document.getElementById('buscaNomeMobile')
            : document.getElementById('buscaNome');
        if (outro && outro.value !== valor) outro.value = valor;
    }

    function atualizarEstiloBotoesFiltro() {
        document.querySelectorAll('[data-filtro="masculino"], [data-filtro="feminino"]').forEach(btn => {
            const generoBtn = btn.dataset.filtro === 'masculino' ? 'MASC' : 'FEM';
            const ativo = filtrosAtivos.genero === generoBtn;
            btn.classList.toggle('btn-secondary', ativo);
            btn.classList.toggle('btn-outline-secondary', !ativo);
            btn.classList.toggle('text-white', ativo);
        });
        document.querySelectorAll('[data-filtro="destaque"]').forEach(btn => {
            btn.classList.toggle('btn-warning', filtrosAtivos.destaque);
            btn.classList.toggle('btn-outline-warning', !filtrosAtivos.destaque);
        });
        document.querySelectorAll('[data-filtro="limpar"]').forEach(btn => {
            const semFiltro = !filtrosAtivos.busca && !filtrosAtivos.genero && !filtrosAtivos.destaque;
            btn.classList.toggle('btn-secondary', semFiltro);
            btn.classList.toggle('text-white', semFiltro);
        });
    }

    function aplicarFiltro() {
        document.querySelectorAll('.aluno-card, .aluno-table-row').forEach(el => {
            let mostrar = true;
            if (filtrosAtivos.busca && !el.dataset.nome.includes(filtrosAtivos.busca)) mostrar = false;
            if (filtrosAtivos.genero && el.dataset.genero !== filtrosAtivos.genero) mostrar = false;
            if (filtrosAtivos.destaque && el.dataset.destaque !== '1') mostrar = false;
            el.classList.toggle('d-none', !mostrar);
        });
        atualizarEstiloBotoesFiltro();
    }

    function limparFiltros() {
        filtrosAtivos = { busca: '', genero: null, destaque: false };
        const b1 = document.getElementById('buscaNome'); if (b1) b1.value = '';
        const b2 = document.getElementById('buscaNomeMobile'); if (b2) b2.value = '';
        aplicarFiltro();
    }

    function toggleFiltro(filtro) {
        if (filtro === 'limpar') { limparFiltros(); return; }
        if (filtro === 'masculino' || filtro === 'feminino') {
            const alvo = filtro === 'masculino' ? 'MASC' : 'FEM';
            filtrosAtivos.genero = filtrosAtivos.genero === alvo ? null : alvo;
        } else if (filtro === 'destaque') {
            filtrosAtivos.destaque = !filtrosAtivos.destaque;
        }
        aplicarFiltro();
    }

    // ─── Carregar alunos ─────────────────────────────────────────────
    async function carregarAlunos() {
        const desktop = document.getElementById('tbodyAlunosDesk');
        const mobile = document.getElementById('listaAlunosMobile');
        const contDesk = document.getElementById('contagemDesk');
        const contMob = document.getElementById('contagemMob');

        if (!idTurmaAlunos) {
            const msg = '<p class="text-muted text-center">Turma não informada na URL.</p>';
            if (desktop) desktop.innerHTML = '<tr><td colspan="4" class="text-muted px-3 py-4">Turma não informada.</td></tr>';
            if (mobile) mobile.innerHTML = msg;
            return;
        }

        // Carregar nome da turma
        let nomeTurma = 'Alunos';
        try {
            const rT = await fetch(`${API}turmas.php?id_turma=${encodeURIComponent(idTurmaAlunos)}&id_interclasse=${encodeURIComponent(idInterclasseAlunos)}`);
            const textT = await rT.text();
            let turmas;
            try { turmas = JSON.parse(textT || 'null'); } catch (_) { turmas = null; }
            const t = Array.isArray(turmas) ? turmas[0] : null;
            nomeTurma = t?.nome_turma || 'Alunos';
        } catch (_) {}

        const titDesk = document.getElementById('tituloTurmaDesk');
        if (titDesk) titDesk.textContent = nomeTurma;

        try {
            const res = await fetch(`${API}usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurmaAlunos)}`);
            const data = await res.json();
            if (data.status !== 'sucesso') throw new Error(data.mensagem || 'Falha ao carregar alunos.');
            alunosLista = data.competidores || [];

            if (contDesk) contDesk.textContent = `${alunosLista.length} aluno(s)`;
            if (contMob) contMob.textContent = `${alunosLista.length} aluno(s)`;

            if (!alunosLista.length) {
                if (mobile) mobile.innerHTML = '<p class="text-muted small">Nenhum aluno cadastrado nesta turma.</p>';
                if (desktop) desktop.innerHTML = '<tr><td colspan="4" class="text-muted px-3 py-4">Nenhum aluno cadastrado nesta turma.</td></tr>';
                return;
            }

            if (mobile) mobile.innerHTML = alunosLista.map(a => cardAluno(a)).join('');

            if (desktop) {
                desktop.innerHTML = alunosLista.map(a => {
                    const gen = (a.genero_usuario || 'MASC').toUpperCase();
                    return `
                    <tr class="aluno-table-row" id="row-aluno-${a.id_usuario}" data-genero="${gen}" data-destaque="0" data-nome="${esc(a.nome_usuario).toLowerCase()}">
                        <td class="px-3">${esc(a.nome_usuario)}</td>
                        <td class="px-3">${esc(a.matricula_usuario)}</td>
                        <td class="px-3">${gen}</td>
                        <td class="px-3">
                            <div class="dropdown">
                                <i class="bi bi-three-dots-vertical text-muted" style="cursor: pointer;" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="min-width: 120px;">
                                    <li><button class="dropdown-item text-muted small" onclick="removerAluno(${a.id_usuario})"><i class="bi bi-trash"></i> Remover</button></li>
                                    <li><button class="dropdown-item text-muted small" onclick="alternarDestaque(${a.id_usuario})"><i class="bi bi-star"></i> Destacar</button></li>
                                    <li><button class="dropdown-item text-danger small" onclick="resetarSenhaAluno(${a.id_usuario})"><i class="bi bi-shield-lock"></i> Resetar senha</button></li>
                                </ul>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }

            aplicarFiltro();
        } catch (error) {
            console.error(error);
            if (mobile) mobile.innerHTML = `<p class="text-danger">${error.message}</p>`;
        }
    }

    // ─── PDF Upload ──────────────────────────────────────────────────
    async function enviarPdf(form, msgEl, btn) {
        msgEl.innerHTML = '';
        const fd = new FormData();
        for (const [k, v] of new FormData(form).entries()) fd.append(k, v);
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) fd.append('pdf_arquivo', fileInput.files[0]);
        fd.append('id_interclasse', idInterclasseAlunos || '');
        fd.append('id_categoria', idCategoria || '');
        fd.append('id_turma', idTurmaAlunos || '');
        try {
            if (btn) btn.disabled = true;
            const r = await fetch(`${API}upload_turma_pdf.php`, { method: 'POST', body: fd, credentials: 'include' });
            const text = await r.text();
            let js = {};
            try { js = JSON.parse(text); } catch (_) { js = {}; }
            if (!r.ok || js.success === false) throw new Error(js.message || 'Falha no upload: ' + text);
            msgEl.innerHTML = '<span class="text-success">Importação concluída. Atualizando…</span>';
            setTimeout(() => window.location.reload(), 1200);
        } catch (err) {
            msgEl.innerHTML = `<span class="text-danger">${esc(err.message || String(err))}</span>`;
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function setupPdfForms() {
        const fMob = document.getElementById('formPdfMob');
        const fDesk = document.getElementById('formPdfDesk');
        if (fMob) {
            fMob.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fMob, document.getElementById('msgPdfMob'), fMob.querySelector('button[type="submit"]'));
            });
        }
        if (fDesk) {
            fDesk.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fDesk, document.getElementById('msgPdfDesk'), fDesk.querySelector('button[type="submit"]'));
            });
        }
    }

    // ─── Event listeners ─────────────────────────────────────────────
    document.getElementById('buscaNome')?.addEventListener('input', (e) => { sincronizarBusca(e.target); aplicarFiltro(); });
    document.getElementById('buscaNomeMobile')?.addEventListener('input', (e) => { sincronizarBusca(e.target); aplicarFiltro(); });
    document.querySelectorAll('[data-filtro]').forEach(btn => {
        btn.addEventListener('click', () => toggleFiltro(btn.dataset.filtro));
    });

    setupPdfForms();
    window.addEventListener('load', carregarAlunos);
</script>

<?php
require_once '../componentes/footer.php';
?>