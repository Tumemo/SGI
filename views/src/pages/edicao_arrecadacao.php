<?php
$tituloPagina = 'SGI - Arrecadação';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
.ocr-page{padding-bottom:5rem}
.ocr-container{width:100%;padding:0 2rem}
.ocr-header{margin-bottom:2rem}
.ocr-header__top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ocr-header__title{font-size:1.75rem;font-weight:800;color:#111827;letter-spacing:-.03em;margin:0;line-height:1.2}
.ocr-header__sub{font-size:.9rem;color:#6B7280;margin:.35rem 0 0;font-weight:400}

.ocr-card__hist{width:38px;height:38px;border-radius:50%;border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0;font-size:1rem}
.ocr-card__hist:hover{border-color:#D1D5DB;background:#F9FAFB;color:#374151;transform:scale(1.1)}

.ocr-card__save{width:38px;height:38px;border-radius:50%;border:2px dashed #D1D5DB;background:transparent;color:#9CA3AF;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;flex-shrink:0;font-size:1.1rem}
.ocr-card__save:hover{border-color:#E30613;color:#E30613;background:#FEF2F2;transform:scale(1.1)}
.ocr-card__save:disabled{opacity:.5;cursor:not-allowed;transform:none}

.ocr-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem}
@media(max-width:991.98px){.ocr-grid{grid-template-columns:1fr}}
@media(max-width:575.98px){.ocr-container{padding:0 1rem}}

.ocr-card{background:#fff;border:1px solid #F0F0F0;border-radius:16px;padding:1.25rem 1.5rem;transition:transform .2s,box-shadow .2s;display:flex;align-items:center;gap:1rem;overflow:hidden}
.ocr-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.ocr-card__icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF2F2,#FEE2E2);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ocr-card__icon i{font-size:1.15rem;color:#E30613}
.ocr-card__info{flex:1;min-width:0}
.ocr-card__name{font-size:.95rem;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ocr-card__badge{display:inline-flex;align-items:center;font-size:.7rem;font-weight:600;background:#F3F4F6;color:#6B7280;border-radius:6px;padding:.15rem .5rem;margin-top:.25rem;border:1px solid #F0F0F0}

.ocr-card__input-wrap{flex-shrink:0;position:relative;width:96px}
.ocr-card__input{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:.95rem;font-weight:700;color:#111827;text-align:center;padding:.5rem .4rem;padding-right:2.1rem;transition:all .15s;background:#FAFAFA}
.ocr-card__input:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none;background:#fff}
.ocr-card__input::placeholder{color:#D1D5DB;font-weight:400}
.ocr-card__input-suffix{position:absolute;right:.55rem;top:50%;transform:translateY(-50%);font-size:.65rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.04em;pointer-events:none}

.ocr-modal .modal-content{border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.ocr-modal .modal-header{border:none;padding:1.25rem 1.5rem .5rem}
.ocr-modal .modal-title{font-size:1.05rem;font-weight:700}
.ocr-modal .modal-body{padding:.5rem 1.5rem 1.25rem}

.ocr-mobile{padding-top:5.5rem;padding-bottom:6rem}
.ocr-mobile .ocr-card{padding:1rem}
.ocr-mobile .ocr-card__input-wrap{width:84px}
.ocr-mobile .ocr-card__input{font-size:.9rem;padding:.45rem .35rem;padding-right:1.9rem}
';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'arrecadacoes';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
?>

<main class="d-md-none ocr-mobile">
    <div class="px-3 mt-3">
        <a href="./dashboard.php" id="btnVoltarArrecadacaoMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacaoMob">Interclasse</span>
        </a>

        <div class="mb-4">
            <h1 class="ocr-header__title">Arrecadações</h1>
            <p class="ocr-header__sub">Registre as arrecadações das turmas por categoria.</p>
        </div>
    </div>

    <div class="px-3">
        <div class="ocr-grid" id="listaArrecadacaoMobile" style="grid-template-columns:1fr;">
            <div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout ocr-page">
    <div class="ocr-container">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>
        </div>

        <div class="ocr-header">
            <div class="ocr-header__top">
                <div>
                    <h1 class="ocr-header__title">Arrecadações</h1>
                    <p class="ocr-header__sub">Registre as arrecadações das turmas por categoria.</p>
                </div>
            </div>
        </div>

        <div class="ocr-grid" id="listaArrecadacaoDesktop">
            <div class="text-center text-muted py-5" style="grid-column:1/-1"><div class="spinner-border spinner-border-sm me-2"></div>Carregando...</div>
        </div>
    </div>
</main>

<?php include 'componentes/nav.php'; require_once '../componentes/footer.php'; ?>

<div class="modal fade ocr-modal" id="modalHistoricoArrecadacao" tabindex="-1" aria-labelledby="modalHistoricoArrecadacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalHistoricoArrecadacaoLabel">
                    <i class="bi bi-clock-history me-2"></i>Histórico de Arrecadações - <span id="modalHistoricoTurmaNome"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php if ($isAdmin): ?>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="btnFiltroAdicionados" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold active" style="background-color:var(--vermelho);color:white;border:1px solid var(--vermelho);font-size:0.8rem;" onclick="filtrarHistorico('adicionados')">
                        <i class="bi bi-plus-circle me-1"></i>Adicionados
                    </button>
                    <button type="button" id="btnFiltroExcluidos" class="btn btn-sm rounded-3 px-3 py-1 fw-semibold" style="background-color:#f0f0f0;color:#555;border:1px solid #e0e0e0;font-size:0.8rem;" onclick="filtrarHistorico('excluidos')">
                        <i class="bi bi-trash me-1"></i>Excluídos
                    </button>
                </div>
                <?php endif; ?>
                <div id="historicoConteudo" class="text-center text-muted py-4">
                    <div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    }

    function renderCard(turma) {
        const nome = esc(turma.nome_fantasia_turma || turma.nome_turma);
        const nomeJs = (turma.nome_fantasia_turma || turma.nome_turma || '').replace(/'/g, "\\'");
        return `
            <div class="ocr-card">
                <div class="ocr-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="ocr-card__info">
                    <p class="ocr-card__name">${nome}</p>
                    <span class="ocr-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                </div>
                <div class="ocr-card__input-wrap">
                    <input type="number" step="0.1" min="0" class="ocr-card__input arrec-input"
                        data-id-turma="${turma.id_turma}"
                        value="${getQuantidadePendente(turma)}" placeholder="0">
                    <span class="ocr-card__input-suffix">Kg</span>
                </div>
                <button type="button" class="ocr-card__hist" onclick="abrirHistoricoTurma(${turma.id_turma}, '${nomeJs}')" title="Ver histórico">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button type="button" class="ocr-card__save" data-id-turma="${turma.id_turma}" onclick="salvarTurma(${turma.id_turma})" title="Salvar">
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        `;
    }

    function renderizarTelas() {
        const listaMobile = document.getElementById('listaArrecadacaoMobile');
        const listaDesktop = document.getElementById('listaArrecadacaoDesktop');

        if (todasAsTurmas.length === 0) {
            const msg = '<div class="text-center text-muted py-5" style="grid-column:1/-1"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#D1D5DB"></i>Nenhuma turma encontrada.</div>';
            listaMobile.innerHTML = msg;
            listaDesktop.innerHTML = msg;
            return;
        }

        listaMobile.innerHTML = todasAsTurmas.map(renderCard).join('');
        listaDesktop.innerHTML = todasAsTurmas.map(renderCard).join('');

        vincularEventosInputs();
    }

    function getInputVisivel(idTurma) {
        const inputs = document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`);
        for (const input of inputs) {
            if (input.offsetParent !== null) return input;
        }
        return inputs[0] || null;
    }

    function getQuantidadeAtual(idTurma) {
        const input = getInputVisivel(idTurma);
        if (input && input.value !== '') {
            return Number(input.value);
        }
        return getQuantidadePendente({ id_turma: idTurma });
    }

    function vincularEventosInputs() {
        document.querySelectorAll('.arrec-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const idTurma = e.target.dataset.idTurma;
                const valor = e.target.value;
                salvarLocal(idTurma, valor);
                document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`).forEach(inp => {
                    if (inp !== e.target) inp.value = valor;
                });
            });
        });
    }

    window.addEventListener('beforeunload', () => {
        const pendentes = todasAsTurmas.some(t => getQuantidadePendente(t) > 0);
        if (pendentes) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../../api/arrecadacao.php', false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            const payload = {
                id_interclasse: idInterclasseResolvida || idInterclasseArrecadacao,
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

            const res = await fetch(`../../../api/turmas.php?id_interclasse=${ativo.id_interclasse}`);
            todasAsTurmas = await res.json();

            renderizarTelas();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
        }
    }

    async function salvarTurma(idTurma) {
        const input = getInputVisivel(idTurma);
        const quantidade = getQuantidadeAtual(idTurma);

        if (!quantidade || quantidade <= 0) {
            alert('Informe a quantidade em kg a adicionar.');
            return;
        }

        if (input) salvarLocal(idTurma, input.value);

        const botoes = document.querySelectorAll(`.ocr-card__save[data-id-turma="${idTurma}"]`);
        botoes.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        });

        const payload = {
            id_interclasse: idInterclasseResolvida || idInterclasseArrecadacao,
            arrecadacoes: [{ id_turma: Number(idTurma), quantidade }]
        };

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
                localStorage.removeItem(`${storagePrefix}${idTurma}`);
                document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`).forEach(inp => {
                    inp.value = '0';
                });
                alert('Dados salvos com sucesso!');
            } else {
                alert('Erro do servidor: ' + result.message);
            }
        } catch (error) {
            alert('Erro de comunicação: Verifique se o ficheiro api/arrecadacao.php existe e se o banco de dados está online.');
            console.error('Falha no salvamento:', error);
        } finally {
            botoes.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            });
        }
    }

    let historicoRegistros = [];
    let filtroHistoricoAtual = 'adicionados';
    let historicoTurmaId = null;

    function abrirHistoricoTurma(idTurma, nomeTurma) {
        historicoTurmaId = idTurma;
        document.getElementById('modalHistoricoTurmaNome').innerText = nomeTurma;
        const modal = new bootstrap.Modal(document.getElementById('modalHistoricoArrecadacao'));
        modal.show();
        carregarHistorico(idTurma);
    }

    async function carregarHistorico(idTurma) {
        const conteudo = document.getElementById('historicoConteudo');
        conteudo.innerHTML = '<div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>';

        <?php if ($isAdmin): ?>
        const btnAdic = document.getElementById('btnFiltroAdicionados');
        const btnExcl = document.getElementById('btnFiltroExcluidos');
        if (btnAdic && btnExcl) {
            btnAdic.style.backgroundColor = 'var(--vermelho)';
            btnAdic.style.color = 'white';
            btnAdic.style.borderColor = 'var(--vermelho)';
            btnExcl.style.backgroundColor = '#f0f0f0';
            btnExcl.style.color = '#555';
            btnExcl.style.borderColor = '#e0e0e0';
        }
        filtroHistoricoAtual = 'adicionados';
        <?php endif; ?>

        const idInterclasse = idInterclasseResolvida || idInterclasseArrecadacao;
        if (!idInterclasse) {
            conteudo.innerHTML = '<p class="text-muted">Nenhuma interclasse selecionada.</p>';
            return;
        }

        try {
            const res = await fetch(`../../../api/arrecadacao.php?id_interclasse=${idInterclasse}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros)) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhum registro de arrecadação encontrado.</p>';
                return;
            }

            historicoRegistros = historicoRegistros.filter(r => Number(r.id_turma) === Number(idTurma));

            if (historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhum registro de arrecadação encontrado para esta turma.</p>';
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

        <?php if ($isAdmin): ?>
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
        <?php endif; ?>

        renderizarHistoricoFiltrado();
    }

    function renderizarHistoricoFiltrado() {
        const conteudo = document.getElementById('historicoConteudo');
        const lista = historicoRegistros.filter(r => {
            if (isAdminPage) {
                return filtroHistoricoAtual === 'adicionados' ? r.status_historico === '1' : r.status_historico === '0';
            }
            return r.status_historico === '1';
        });

        if (lista.length === 0) {
            const msg = isAdminPage && filtroHistoricoAtual === 'excluidos'
                ? '<p class="text-muted py-3">Nenhum registro excluído encontrado.</p>'
                : '<p class="text-muted py-3">Nenhum registro adicionado encontrado.</p>';
            conteudo.innerHTML = msg;
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
        html += '<thead><tr class="table-light"><th>Data</th><th class="text-center">Kg</th><th class="text-center">Pts</th>';

        if (isAdminPage && filtroHistoricoAtual === 'adicionados') {
            html += '<th class="text-center">Ação</th>';
        } else if (isAdminPage && filtroHistoricoAtual === 'excluidos') {
            html += '<th class="text-center">Estado</th>';
        }

        html += '</tr></thead><tbody>';

        lista.forEach(r => {
            const data = new Date(r.data_registro);
            const dataFmt = data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

            html += '<tr>';

            if (isAdminPage && filtroHistoricoAtual === 'excluidos') {
                html += '<td class="small text-muted">' + esc(dataFmt) + '</td>';
                html += '<td class="text-center text-muted">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-danger">-' + r.pontos_adicionados + '</td>';
                html += '<td class="text-center"><span class="badge bg-secondary">Removido</span></td>';
            } else {
                html += '<td class="small">' + esc(dataFmt) + '</td>';
                html += '<td class="text-center">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-success">+' + r.pontos_adicionados + '</td>';
                if (isAdminPage) {
                    html += '<td class="text-center">';
                    html += '<button class="btn btn-outline-danger btn-sm" title="Remover e reverter pontos" onclick="deletarHistorico(' + r.id_historico + ')">';
                    html += '<i class="bi bi-trash"></i>';
                    html += '</button>';
                    html += '</td>';
                }
            }

            html += '</tr>';
        });

        html += '</tbody></table></div>';
        conteudo.innerHTML = html;
    }

    async function deletarHistorico(idHistorico) {
        if (!confirm('Tem certeza que deseja remover este registro?\nOs pontos serão subtraídos automaticamente do ranking.')) {
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
                carregarHistorico(historicoTurmaId);
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error("Erro ao deletar:", error);
            alert('Erro de comunicação ao tentar remover o registro.');
        }
    }

    window.addEventListener('load', carregarDados);
</script>
