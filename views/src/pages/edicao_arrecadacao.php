<?php
$tituloPagina = 'SGI - Arrecadação';
$titulo = 'Arrecadação';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'arrecadacoes';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <?php if ($isAdmin): ?>
    <div class="px-3 mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalHistoricoArrecadacao" onclick="carregarHistorico()">
            <i class="bi bi-gear"></i> Histórico
        </button>
    </div>
    <?php endif; ?>
    <div class="px-3 mt-4">
        <label class="form-label small fw-bold text-muted">Filtrar Categoria:</label>
        <select id="filtroCategoriaMobile" class="form-select shadow-sm mb-3">
            <option value="todos">Todas as Categorias</option>
        </select>
    </div>

    <div class="card shadow m-auto" style="width: 22rem;">
        <div class="card-header fw-bold text-center bg-white">
            Itens a adicionar (somam ao ranking)
        </div>
        <ul class="list-group list-group-flush" id="listaArrecadacaoMobile">
            <li class="list-group-item text-center text-muted">(Carregando...)</li>
        </ul>
    </div>

    <div class="container mt-3 mb-5 pb-4">
        <div id="barraContinuarArrecadacaoMobile" class="d-none">
            <button id="btnSalvarMobile" class="btn btn-danger w-100 fw-semibold rounded-3 py-2 shadow-sm">Salvar Alterações</button>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 1000px;">
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-start">
                <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
                </a>
                <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalHistoricoArrecadacao" onclick="carregarHistorico()" style="border-radius: 6px;">
                    <i class="bi bi-gear fs-6"></i> Histórico
                </button>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Lançamento de Arrecadações</h4>
                    <div class="mt-3" style="min-width: 250px;">
                        <label class="small fw-bold text-muted">Filtrar por Categoria:</label>
                        <select id="filtroCategoriaDesktop" class="form-select border-0 shadow-sm mt-1">
                            <option value="todos">Todas as Categorias</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($isAdmin): ?>
                    <button type="button" onclick="window.location.reload()" class="btn bg-white fw-semibold rounded-3 px-4 py-2" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <?php endif; ?>
                    <button type="button" id="btnSalvarDesktop" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                        Salvar Dados
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5" id="listaArrecadacaoDesktop"></div>
    </div>
</main>

<div id="barraContinuarArrecadacaoDesktop" class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, #f8f9fa 70%, rgba(248, 249, 250, 0) 100%); padding: 24px 0; z-index: 1020;">
    <div class="container-fluid d-flex justify-content-end align-items-center" style="max-width: 1000px; margin-left: auto; margin-right: auto;">
        <span class="text-muted me-3 small" id="statusSincronizacao">Dados guardados localmente</span>
    </div>
</div>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>

<?php if ($isAdmin): ?>
<div class="modal fade" id="modalHistoricoArrecadacao" tabindex="-1" aria-labelledby="modalHistoricoArrecadacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
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
            const msg = '<p class="p-4 text-center text-muted">Nenhuma turma encontrada nesta categoria.</p>';
            listaMobile.innerHTML = msg;
            listaDesktop.innerHTML = msg;
            return;
        }

        listaMobile.innerHTML = turmasFiltradas.map(turma => `
            <li class="list-group-item justify-content-between d-flex align-items-center px-3">
                <div>
                    <span class="fw-bold d-block">${esc(turma.nome_turma)}</span>
                    <small class="text-muted">${esc(turma.nome_categoria || 'Geral')}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" step="0.1" min="0" class="form-control form-control-sm arrec-input text-center"
                        data-id-turma="${turma.id_turma}"
                        value="${getQuantidadePendente(turma)}" style="width: 80px;" placeholder="0">
                </div>
            </li>
        `).join('');

        listaDesktop.innerHTML = turmasFiltradas.map(turma => `
            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold text-dark fs-6 d-block">${esc(turma.nome_turma)}</span>
                        <span class="badge bg-light text-dark fw-normal border">${esc(turma.nome_categoria || 'Geral')}</span>
                    </div>
                    <div class="input-group" style="max-width: 150px;">
                        <input type="number" step="0.1" min="0" class="form-control text-center arrec-input"
                            data-id-turma="${turma.id_turma}"
                            value="${getQuantidadePendente(turma)}" placeholder="0">
                        <span class="input-group-text bg-light border-start-0 small">Qtd</span>
                    </div>
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
            const vDesk = document.getElementById('btnVoltarArrecadacao');
            if (vDesk) {
                vDesk.href = `./dashboard.php?id=${idInterclasseArrecadacao || ativo.id_interclasse}`;
            }
            document.getElementById('barraContinuarArrecadacaoMobile').classList.remove('d-none');

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
