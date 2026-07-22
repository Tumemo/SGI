<?php
$tituloPagina = 'SGI - Arrecadação';
$titulo = 'Arrecadação';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
/* ═══ Arrecadação Modern ═══ */
.arc-page{padding-bottom:5rem}
.arc-container{width:100%;padding:0 2rem}
.arc-header{margin-bottom:2rem}
.arc-header__top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem}
.arc-header__title{font-size:1.75rem;font-weight:800;color:#111827;letter-spacing:-.03em;margin:0;line-height:1.2}
.arc-header__sub{font-size:.9rem;color:#6B7280;margin:.35rem 0 0;font-weight:400}
.arc-hist-btn{display:inline-flex;align-items:center;gap:.45rem;border:1.5px solid #E5E7EB;background:#fff;color:#374151;border-radius:10px;padding:.5rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s}
.arc-hist-btn:hover{border-color:#D1D5DB;background:#F9FAFB;transform:translateY(-1px);box-shadow:0 2px 8px rgba(0,0,0,.06)}
.arc-hist-btn i{font-size:.9rem;color:#9CA3AF}

/* Controls bar */
.arc-controls{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.03),0 4px 16px rgba(0,0,0,.02);margin-bottom:2rem;flex-wrap:wrap}
.arc-controls__field{flex:1;min-width:200px}
.arc-controls__field label{display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;margin-bottom:.4rem}
.arc-controls__field select{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:.85rem;font-weight:500;color:#374151;background:#fff;padding:.6rem .85rem;transition:border-color .15s,box-shadow .15s;cursor:pointer;appearance:auto}
.arc-controls__field select:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none}
.arc-controls__actions{display:flex;gap:.6rem;flex-shrink:0}
.arc-btn-cancel{border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;border-radius:10px;padding:.6rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .15s}
.arc-btn-cancel:hover{border-color:#D1D5DB;background:#F9FAFB;color:#374151}
.arc-btn-save{background:#E30613;border:none;color:#fff;border-radius:10px;padding:.6rem 1.5rem;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(227,6,19,.25)}
.arc-btn-save:hover{background:#C50510;transform:translateY(-1px);box-shadow:0 4px 14px rgba(227,6,19,.35)}
.arc-btn-save:active{transform:translateY(0)}

/* Cards grid */
.arc-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem}
@media(max-width:991.98px){.arc-grid{grid-template-columns:1fr}}
@media(max-width:575.98px){.arc-controls{flex-direction:column;align-items:stretch}.arc-controls__actions{justify-content:flex-end}.arc-container{padding:0 1rem}}

/* Turma card */
.arc-card{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;transition:transform .2s,box-shadow .2s;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden}
.arc-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.arc-card__icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF2F2,#FEE2E2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.arc-card__icon i{font-size:1.15rem;color:#E30613}
.arc-card__info{flex:1;min-width:0}
.arc-card__name{font-size:.95rem;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.arc-card__badge{display:inline-flex;align-items:center;font-size:.7rem;font-weight:600;background:#F3F4F6;color:#6B7280;border-radius:6px;padding:.15rem .5rem;margin-top:.25rem;border:1px solid #F0F0F0}
.arc-card__input-wrap{flex-shrink:0;position:relative;width:100px}
.arc-card__input{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:1rem;font-weight:700;color:#111827;text-align:center;padding:.55rem .5rem;padding-right:2.2rem;transition:all .15s;background:#FAFAFA}
.arc-card__input:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none;background:#fff}
.arc-card__input::placeholder{color:#D1D5DB;font-weight:400}
.arc-card__input-suffix{position:absolute;right:.6rem;top:50%;transform:translateY(-50%);font-size:.7rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.04em;pointer-events:none}

/* Status footer */
.arc-status{display:flex;justify-content:center;margin-bottom:1.5rem}
.arc-status__card{display:inline-flex;align-items:center;gap:.5rem;background:#F9FAFB;border:1px solid #F0F0F0;border-radius:12px;padding:.5rem 1.15rem;font-size:.8rem;color:#6B7280;font-weight:500}
.arc-status__card i{color:#10B981;font-size:.9rem}

/* Mobile layout */
.arc-mobile{padding-top:5.5rem;padding-bottom:6rem}
.arc-mobile .arc-card{padding:1rem}
.arc-mobile .arc-card__input-wrap{width:80px}
.arc-mobile .arc-card__input{font-size:.9rem;padding:.5rem .4rem;padding-right:2rem}
.arc-mobile .arc-btn-save{width:100%;padding:.7rem;font-size:.9rem}
.arc-mobile .arc-controls{margin:0 1rem 1.5rem}

/* Modal improvements */
.arc-modal .modal-content{border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.arc-modal .modal-header{border:none;padding:1.25rem 1.5rem .5rem}
.arc-modal .modal-title{font-size:1.05rem;font-weight:700}
.arc-modal .modal-body{padding:.5rem 1.5rem 1.25rem}
';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'arrecadacoes';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none arc-mobile">
    <div class="px-3 mt-3">
        <a href="./dashboard.php" id="btnVoltarArrecadacaoMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacaoMob">Interclasse</span>
        </a>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="arc-header__title">Lançamento de Arrecadações</h1>
                <p class="arc-header__sub">Registre as arrecadações das turmas por categoria.</p>
            </div>
            <?php if ($isAdmin): ?>
            <button type="button" class="arc-hist-btn" data-bs-toggle="modal" data-bs-target="#modalHistoricoArrecadacao" onclick="carregarHistorico()">
                <i class="bi bi-clock-history"></i> Histórico
            </button>
            <?php endif; ?>
        </div>

        <div class="arc-controls">
            <div class="arc-controls__field">
                <label>Categoria</label>
                <select id="filtroCategoriaMobile">
                    <option value="todos">Todas as Categorias</option>
                </select>
            </div>
        </div>
    </div>

    <div class="px-3">
        <div class="arc-grid" id="listaArrecadacaoMobile" style="grid-template-columns:1fr;">
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>

    <div id="barraContinuarArrecadacaoMobile" class="d-none px-3 mt-3">
        <button id="btnSalvarMobile" class="arc-btn-save">Salvar Alterações</button>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout arc-page">
    <div class="arc-container">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>
        </div>

        <div class="arc-header">
            <div class="arc-header__top">
                <div>
                    <h1 class="arc-header__title">Lançamento de Arrecadações</h1>
                    <p class="arc-header__sub">Registre as arrecadações das turmas por categoria.</p>
                </div>
                <?php if ($isAdmin): ?>
                <button type="button" class="arc-hist-btn" data-bs-toggle="modal" data-bs-target="#modalHistoricoArrecadacao" onclick="carregarHistorico()">
                    <i class="bi bi-clock-history"></i> Histórico
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="arc-controls">
            <div class="arc-controls__field">
                <label>Filtrar por Categoria</label>
                <select id="filtroCategoriaDesktop">
                    <option value="todos">Todas as Categorias</option>
                </select>
            </div>
            <div class="arc-controls__actions">
                <?php if ($isAdmin): ?>
                <button type="button" onclick="window.location.reload()" class="arc-btn-cancel">Cancelar</button>
                <?php endif; ?>
                <button type="button" id="btnSalvarDesktop" class="arc-btn-save">Salvar Dados</button>
            </div>
        </div>

        <div class="arc-grid" id="listaArrecadacaoDesktop">
            <div class="text-center text-muted py-5" style="grid-column:1/-1"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>

        <div id="barraContinuarArrecadacaoDesktop" class="d-none arc-status">
            <div class="arc-status__card">
                <i class="bi bi-shield-check"></i>
                <span id="statusSincronizacao">Dados guardados localmente</span>
            </div>
        </div>
    </div>
</main>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>

<?php if ($isAdmin): ?>
<div class="modal fade arc-modal" id="modalHistoricoArrecadacao" tabindex="-1" aria-labelledby="modalHistoricoArrecadacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalHistoricoArrecadacaoLabel">
                    <i class="bi bi-clock-history me-2"></i>Histórico de Arrecadações
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="btnFiltroAdicionados" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold active" style="background-color: var(--vermelho); color: white; border: 1px solid var(--vermelho); font-size: 0.8rem;" onclick="filtrarHistorico('adicionados')">
                        <i class="bi bi-plus-circle me-1"></i>Adicionados
                    </button>
                    <button type="button" id="btnFiltroExcluidos" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold" style="background-color: #f0f0f0; color: #555; border: 1px solid #e0e0e0; font-size: 0.8rem;" onclick="filtrarHistorico('excluidos')">
                        <i class="bi bi-trash me-1"></i>Excluídos
                    </button>
                </div>
                <div id="historicoConteudo" class="text-center text-muted py-4">
                    <div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    const storagePrefix = 'sgi_items_';
    const paramsArrecadacao = new URLSearchParams(window.location.search);
    const idInterclasseArrecadacao = paramsArrecadacao.get('id');
    const isAdminPage = <?= $isAdmin ? 'true' : 'false' ?>;

    let todasAsTurmas = [];
    let idInterclasseResolvida = null;

    function getQuantidadePendente(turma) {
        const local = localStorage.getItem(`${storagePrefix}${turma.id_turma}`);
        return local !== null ? Number(local) : 0;
    }

    function salvarLocal(idTurma, valor) {
        localStorage.setItem(`${storagePrefix}${idTurma}`, String(valor));
        const status = document.getElementById('statusSincronizacao');
        if (status) status.innerText = 'Itens por lançar (serão somados ao ranking ao guardar)...';
    }

    function renderizarTelas(categoriaFiltro = 'todos') {
        const listaMobile = document.getElementById('listaArrecadacaoMobile');
        const listaDesktop = document.getElementById('listaArrecadacaoDesktop');

        const turmasFiltradas = categoriaFiltro === 'todos'
            ? todasAsTurmas
            : todasAsTurmas.filter(t => t.nome_categoria === categoriaFiltro);

        if (turmasFiltradas.length === 0) {
            const msg = '<div class="text-center text-muted py-5" style="grid-column:1/-1"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#D1D5DB"></i>Nenhuma turma encontrada nesta categoria.</div>';
            listaMobile.innerHTML = msg;
            listaDesktop.innerHTML = msg;
            return;
        }

        listaMobile.innerHTML = turmasFiltradas.map(turma => `
            <div class="arc-card">
                <div class="arc-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="arc-card__info">
                    <p class="arc-card__name">${esc(turma.nome_turma)}</p>
                    <span class="arc-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                </div>
                <div class="arc-card__input-wrap">
                    <input type="number" step="0.1" min="0" class="arc-card__input arrec-input"
                        data-id-turma="${turma.id_turma}"
                        value="${getQuantidadePendente(turma)}" placeholder="0">
                    <span class="arc-card__input-suffix">Qtd</span>
                </div>
            </div>
        `).join('');

        listaDesktop.innerHTML = turmasFiltradas.map(turma => `
            <div class="arc-card">
                <div class="arc-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="arc-card__info">
                    <p class="arc-card__name">${esc(turma.nome_turma)}</p>
                    <span class="arc-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                </div>
                <div class="arc-card__input-wrap">
                    <input type="number" step="0.1" min="0" class="arc-card__input arrec-input"
                        data-id-turma="${turma.id_turma}"
                        value="${getQuantidadePendente(turma)}" placeholder="0">
                    <span class="arc-card__input-suffix">Qtd</span>
                </div>
            </div>
        `).join('');

        vincularEventosInputs();
    }

    let autoSaveTimer = null;

    function vincularEventosInputs() {
        document.querySelectorAll('.arrec-input').forEach(input => {
            input.addEventListener('input', (e) => {
                salvarLocal(e.target.dataset.idTurma, e.target.value);
                agendarAutoSave();
            });
        });
    }

    function agendarAutoSave() {
        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => salvarNoServidor(true), 3000);
    }

    window.addEventListener('beforeunload', () => {
        const pendentes = todasAsTurmas.some(t => getQuantidadePendente(t) > 0);
        if (pendentes) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../../api/arrecadacao.php', false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            const payload = {
                id_interclasse: idInterclasseArrecadacao,
                arrecadacoes: todasAsTurmas.map(t => ({
                    id_turma: t.id_turma,
                    quantidade: getQuantidadePendente(t)
                })).filter((item) => item.quantidade > 0)
            };
            xhr.send(JSON.stringify(payload));
        }
    });

    async function carregarDados() {
        try {
            const ativo = idInterclasseArrecadacao
                ? await window.SGIInterclasse.getInterclasseById(idInterclasseArrecadacao)
                : await window.SGIInterclasse.getActiveInterclasse();

            if (!ativo) return;

            idInterclasseResolvida = ativo.id_interclasse;

            document.getElementById('nomeInterclasseArrecadacao').innerText = ativo.nome_interclasse;
            const nomeMob = document.getElementById('nomeInterclasseArrecadacaoMob');
            if (nomeMob) nomeMob.innerText = ativo.nome_interclasse;
            const vDesk = document.getElementById('btnVoltarArrecadacao');
            if (vDesk) {
                vDesk.href = `./dashboard.php?id=${idInterclasseArrecadacao || ativo.id_interclasse}`;
            }
            const vMob = document.getElementById('btnVoltarArrecadacaoMob');
            if (vMob) {
                vMob.href = `./dashboard.php?id=${idInterclasseArrecadacao || ativo.id_interclasse}`;
            }
            document.getElementById('barraContinuarArrecadacaoMobile').classList.remove('d-none');
            document.getElementById('barraContinuarArrecadacaoDesktop').classList.remove('d-none');

            const res = await fetch(`../../../api/turmas.php?id_interclasse=${ativo.id_interclasse}`);
            todasAsTurmas = await res.json();

            const categorias = [...new Set(todasAsTurmas.map(t => t.nome_categoria))].filter(Boolean);
            const selects = [document.getElementById('filtroCategoriaMobile'), document.getElementById('filtroCategoriaDesktop')];

            categorias.forEach(cat => {
                selects.forEach(select => {
                    const opt = document.createElement('option');
                    opt.value = cat; opt.innerText = cat;
                    select.appendChild(opt);
                });
            });

            renderizarTelas();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
        }
    }

    async function salvarNoServidor(auto = false) {
        const btnDesk = document.getElementById('btnSalvarDesktop');
        const btnMob = document.getElementById('btnSalvarMobile');

        const payload = {
            id_interclasse: idInterclasseResolvida || idInterclasseArrecadacao,
            arrecadacoes: todasAsTurmas.map(t => ({
                id_turma: t.id_turma,
                quantidade: getQuantidadePendente(t)
            })).filter((item) => item.quantidade > 0)
        };

        if (!payload.arrecadacoes.length) {
            if (!auto) alert('Informe a quantidade de itens a adicionar em pelo menos uma turma.');
            return;
        }

        const textoOriginal = btnDesk.innerText;
        if (!auto) {
            btnDesk.disabled = btnMob.disabled = true;
            btnDesk.innerText = "A guardar...";
        }

        try {
            const response = await fetch('../../../api/arrecadacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Erro de rede: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                todasAsTurmas.forEach(t => {
                    localStorage.removeItem(`${storagePrefix}${t.id_turma}`);
                });
                const status = document.getElementById('statusSincronizacao');
                if (status) status.innerText = 'Salvo no servidor!';
                if (!auto) {
                    alert('Dados salvos com sucesso!');
                    window.location.reload();
                }
            } else {
                if (!auto) alert('Erro do servidor: ' + result.message);
            }
        } catch (error) {
            if (!auto) {
                alert('Erro de comunicação: Verifique se o ficheiro api/arrecadacao.php existe e se o banco de dados está online.');
            }
            console.error('Falha no salvamento:', error);
        } finally {
            if (!auto) {
                btnDesk.disabled = btnMob.disabled = false;
                btnDesk.innerText = textoOriginal;
            }
        }
    }

    document.getElementById('filtroCategoriaMobile').addEventListener('change', (e) => renderizarTelas(e.target.value));
    document.getElementById('filtroCategoriaDesktop').addEventListener('change', (e) => renderizarTelas(e.target.value));
    document.getElementById('btnSalvarDesktop').addEventListener('click', () => salvarNoServidor());
    document.getElementById('btnSalvarMobile').addEventListener('click', () => salvarNoServidor());

    <?php if ($isAdmin): ?>
    let historicoRegistros = [];
    let filtroHistoricoAtual = 'adicionados';

    async function carregarHistorico() {
        const conteudo = document.getElementById('historicoConteudo');
        conteudo.innerHTML = '<div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>';

        document.getElementById('btnFiltroAdicionados').style.backgroundColor = 'var(--vermelho)';
        document.getElementById('btnFiltroAdicionados').style.color = 'white';
        document.getElementById('btnFiltroAdicionados').style.borderColor = 'var(--vermelho)';
        document.getElementById('btnFiltroExcluidos').style.backgroundColor = '#f0f0f0';
        document.getElementById('btnFiltroExcluidos').style.color = '#555';
        document.getElementById('btnFiltroExcluidos').style.borderColor = '#e0e0e0';
        filtroHistoricoAtual = 'adicionados';

        const idInterclasse = idInterclasseResolvida || idInterclasseArrecadacao;
        if (!idInterclasse) {
            conteudo.innerHTML = '<p class="text-muted">Nenhuma interclasse selecionada.</p>';
            return;
        }

        try {
            const res = await fetch(`../../../api/arrecadacao.php?id_interclasse=${idInterclasse}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros) || historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhum registro de arrecadação encontrado.</p>';
                return;
            }

            renderizarHistoricoFiltrado();

        } catch (error) {
            console.error("Erro ao carregar histórico:", error);
            conteudo.innerHTML = '<p class="text-danger">Erro ao carregar histórico. Verifique a ligação ao banco de dados.</p>';
        }
    }

    function filtrarHistorico(filtro) {
        filtroHistoricoAtual = filtro;

        const btnAdic = document.getElementById('btnFiltroAdicionados');
        const btnExcl = document.getElementById('btnFiltroExcluidos');

        if (filtro === 'adicionados') {
            btnAdic.style.backgroundColor = 'var(--vermelho)';
            btnAdic.style.color = 'white';
            btnAdic.style.borderColor = 'var(--vermelho)';
            btnExcl.style.backgroundColor = '#f0f0f0';
            btnExcl.style.color = '#555';
            btnExcl.style.borderColor = '#e0e0e0';
        } else {
            btnExcl.style.backgroundColor = 'var(--vermelho)';
            btnExcl.style.color = 'white';
            btnExcl.style.borderColor = 'var(--vermelho)';
            btnAdic.style.backgroundColor = '#f0f0f0';
            btnAdic.style.color = '#555';
            btnAdic.style.borderColor = '#e0e0e0';
        }

        renderizarHistoricoFiltrado();
    }

    function renderizarHistoricoFiltrado() {
        const conteudo = document.getElementById('historicoConteudo');
        const lista = historicoRegistros.filter(r =>
            filtroHistoricoAtual === 'adicionados' ? r.status_historico === '1' : r.status_historico === '0'
        );

        if (lista.length === 0) {
            const msg = filtroHistoricoAtual === 'adicionados'
                ? '<p class="text-muted py-3">Nenhum registro adicionado encontrado.</p>'
                : '<p class="text-muted py-3">Nenhum registro excluído encontrado.</p>';
            conteudo.innerHTML = msg;
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
        html += '<thead><tr class="table-light"><th>Data</th><th>Turma</th><th>Cat.</th><th class="text-center">Qtd</th><th class="text-center">Pts</th>';

        if (filtroHistoricoAtual === 'adicionados') {
            html += '<th class="text-center">Ação</th>';
        } else {
            html += '<th class="text-center">Estado</th>';
        }

        html += '</tr></thead><tbody>';

        lista.forEach(r => {
            const data = new Date(r.data_registro);
            const dataFmt = data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

            html += '<tr>';

            if (filtroHistoricoAtual === 'excluidos') {
                html += '<td class="small text-muted">' + esc(dataFmt) + '</td>';
                html += '<td class="fw-semibold text-muted">' + esc(r.nome_turma) + '</td>';
                html += '<td><span class="badge bg-light text-muted border">' + esc(r.nome_categoria) + '</span></td>';
                html += '<td class="text-center text-muted">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-danger">-' + r.pontos_adicionados + '</td>';
                html += '<td class="text-center"><span class="badge bg-secondary">Removido</span></td>';
            } else {
                html += '<td class="small">' + esc(dataFmt) + '</td>';
                html += '<td class="fw-semibold">' + esc(r.nome_turma) + '</td>';
                html += '<td><span class="badge bg-light text-dark border">' + esc(r.nome_categoria) + '</span></td>';
                html += '<td class="text-center">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-success">+' + r.pontos_adicionados + '</td>';
                html += '<td class="text-center">';
                html += '<button class="btn btn-outline-danger btn-sm" title="Remover e reverter pontos" onclick="deletarHistorico(' + r.id_historico + ', \'' + esc(r.nome_turma).replace(/'/g, "\\'") + '\')">';
                html += '<i class="bi bi-trash"></i>';
                html += '</button>';
                html += '</td>';
            }

            html += '</tr>';
        });

        html += '</tbody></table></div>';
        conteudo.innerHTML = html;
    }

    async function deletarHistorico(idHistorico, nomeTurma) {
        if (!confirm(`Tem certeza que deseja remover o registro de "${nomeTurma}"?\nOs pontos serão subtraídos automaticamente do ranking.`)) {
            return;
        }

        const idInterclasse = idInterclasseResolvida || idInterclasseArrecadacao;

        try {
            const res = await fetch('../../../api/arrecadacao.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_historico: idHistorico, id_interclasse: idInterclasse })
            });

            const result = await res.json();

            if (result.success) {
                alert('Registro removido e pontos revertidos com sucesso!');
                carregarHistorico();
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error("Erro ao deletar:", error);
            alert('Erro de comunicação ao tentar remover o registro.');
        }
    }
    <?php endif; ?>

    window.addEventListener('load', carregarDados);
</script>
