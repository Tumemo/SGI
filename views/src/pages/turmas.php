<?php
$tituloPagina = 'SGI - Turmas';
$titulo = 'Turmas';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'categorias';
?>

<!-- Toast -->
<div class="toast-wrapper" id="toastWrapper"></div>

<!-- Mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <div class="p-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="./dashboard.php" id="btnVoltarCatMob" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCatMob">Interclasse</span>
            </a>
            <div class="turma-search-wrapper flex-grow-1">
                <i class="bi bi-search turma-search-icon"></i>
                <input type="text" id="buscaTurmaMob" placeholder="Buscar turma..." oninput="filtrarTurmas()">
            </div>
        </div>
        <div id="listaTurmasMobile"></div>
    </div>

    <?php if ($nivelUsuario === 0): ?>
    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed" style="height: 60px; width: 60px; bottom: 100px; right: 20px; z-index: 10; cursor: pointer; box-shadow: 0 4px 16px rgba(227,6,19,0.4);" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus-lg text-white"></i>
    </button>
    <?php endif; ?>
</main>

<!-- Desktop -->
<main class="d-none d-md-flex flex-column main-desktop-layout">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="./dashboard.php" id="btnVoltarCatDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-3 flex-shrink-0">
            <div class="turma-search-wrapper">
                <i class="bi bi-search turma-search-icon"></i>
                <input type="text" id="buscaTurmaDesk" placeholder="Buscar turma..." oninput="filtrarTurmas()">
            </div>
            <?php if ($nivelUsuario === 0): ?>
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-plus-lg"></i> Nova Turma
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="listaTurmasDesktop"></div>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurma">
                    <div class="mb-3">
                        <label for="nomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="nomeTurma" placeholder="Turma A" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomeFantasia" class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="nomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label for="turnoTurma" class="form-label">Turno:</label>
                        <select class="form-select" id="turnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="categoriaTurma" class="form-label">Categoria:</label>
                        <select class="form-select" id="categoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p style="font-size: 14px;">Adicione aqui o pdf dos alunos da turma criada</p>

                        <label for="arquivoUpload" class="">
                            <i class="bi bi-upload"></i>
                        </label>

                        <span id="nomeArquivo" class="text-muted"></span>
                    </div>
                    <div class=" d-flex justify-content-center gap-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Turma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarTurma">
                    <div class="mb-3">
                        <label class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="editNomeTurma" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="editNomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Turno:</label>
                        <select class="form-select" id="editTurnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria:</label>
                        <select class="form-select" id="editCategoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarTurma" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoTurma">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluirTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-body text-center py-4">
                <div class="modal-excluir-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-bold mb-1">Excluir Turma</h5>
                <p class="text-muted small mb-3">
                    Tem certeza que deseja excluir <strong class="modal-excluir-nome" id="excluirTurmaNome"></strong>?
                    <br>Esta ação não pode ser desfeita.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" id="btnConfirmarExclusao">Sim, excluir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let turmasData = [];
    let editTurmaId = null;
    const NIVEL_USUARIO = <?= $nivelUsuario ?>;

    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    /* ── TOAST ── */
    function mostrarToast(mensagem, tipo) {
        const wrapper = document.getElementById('toastWrapper');
        const icones = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
        const el = document.createElement('div');
        el.className = `toast-sgi toast-sgi--${tipo}`;
        el.innerHTML = `<i class="bi ${icones[tipo] || icones.info} toast-sgi-icon"></i> ${mensagem}`;
        wrapper.appendChild(el);
        setTimeout(() => {
            el.classList.add('toast-sgi--out');
            setTimeout(() => el.remove(), 300);
        }, 3500);
    }

    /* ── HELPERS ── */
    async function resolverInterclasse() {
        if (idInterclasse) {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
            if (dados) return dados;
        }
        return window.SGIInterclasse.getActiveInterclasse();
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function mostrarNomeArquivo() {
        const input = document.getElementById('arquivoUpload');
        const span = document.getElementById('nomeArquivo');
        span.textContent = input.files?.length ? input.files[0].name : 'Nenhum arquivo selecionado';
    }

    /* ── SKELETON ── */
    function renderizarSkeleton() {
        const html = Array.from({ length: 6 }, () => `
            <div class="col">
                <div class="skeleton-card skeleton-shimmer">
                    <div class="skeleton-card-top">
                        <div class="skeleton-avatar"></div>
                        <div class="skeleton-lines">
                            <div class="skeleton-line skeleton-line--sm"></div>
                            <div class="skeleton-line skeleton-line--xs"></div>
                        </div>
                    </div>
                    <div class="skeleton-meta">
                        <div class="skeleton-badge"></div>
                        <div class="skeleton-badge"></div>
                    </div>
                    <div class="skeleton-actions"></div>
                </div>
            </div>
        `).join('');
        document.getElementById('listaTurmasMobile').innerHTML = `<div class="row g-3">${html}</div>`;
        document.getElementById('listaTurmasDesktop').innerHTML = `<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">${html}</div>`;
    }

    /* ── EMPTY STATE ── */
    function renderizarEmptyState(mensagem, botao) {
        const html = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                <h3>${mensagem || 'Nenhuma turma encontrada'}</h3>
                <p>${botao || 'Nenhuma turma cadastrada neste interclasse ainda.'}</p>
                ${NIVEL_USUARIO === 0 ? '<button class="btn btn-danger px-4" data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="bi bi-plus-lg me-1"></i>Criar Turma</button>' : ''}
            </div>`;
        document.getElementById('listaTurmasMobile').innerHTML = html;
        document.getElementById('listaTurmasDesktop').innerHTML = html;
    }

    /* ── RENDER CARD ── */
    function renderizarCard(turma, interclasse) {
        const avatarLetra = turma.nome_turma.charAt(0).toUpperCase();
        const turno = turma.turno_turma || '';

        const adminBtns = NIVEL_USUARIO === 0 ? `
            <div class="turma-card-admin">
                <button class="btn-icon" title="Editar" onclick='editarTurma(${turma.id_turma})'>
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-icon btn-icon--delete" title="Excluir" onclick='abrirModalExcluir(${turma.id_turma}, "${esc(turma.nome_turma)}")'>
                    <i class="bi bi-trash"></i>
                </button>
            </div>` : '';

        return `
            <div class="col">
                <div class="turma-card">
                    <div class="turma-card-top">
                        <div class="turma-avatar" style="background:#e30613;">${avatarLetra}</div>
                        <div class="turma-card-info">
                            <div class="turma-card-name">${esc(turma.nome_turma)}</div>
                            ${turma.nome_fantasia_turma ? `<div class="turma-card-fantasy">${esc(turma.nome_fantasia_turma)}</div>` : ''}
                        </div>
                    </div>
                    <div class="turma-card-meta">
                        ${turno ? `<span class="turma-badge"><i class="bi bi-clock"></i> ${turno}</span>` : ''}
                        <span class="turma-badge"><i class="bi bi-bookmark"></i> ${esc(turma.nome_categoria || 'Categoria')}</span>
                        <span class="turma-badge"><i class="bi bi-people"></i> ${turma.qtd_alunos || 0}</span>
                    </div>
                    <div class="turma-card-actions">
                        ${adminBtns}
                        <a href="./turma_alunos.php?id=${interclasse.id_interclasse}&id_turma=${turma.id_turma}&id_categoria=${turma.categorias_id_categoria}" class="turma-card-btn-detalhes ms-auto">
                            Ver detalhes <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>`;
    }

    /* ── RENDER BY CATEGORY ── */
    function renderizarTurmas(turmas, interclasse) {
        if (!turmas.length) {
            renderizarEmptyState();
            return;
        }

        const grupos = {};
        turmas.forEach(t => {
            const chave = t.nome_categoria || 'Sem categoria';
            if (!grupos[chave]) grupos[chave] = [];
            grupos[chave].push(t);
        });

        let html = '';
        Object.entries(grupos).forEach(([catNome, lista]) => {
            const cardsHtml = lista.map(t => renderizarCard(t, interclasse)).join('');
            html += `
                <div class="turma-section mb-5">
                    <div class="turma-section-header">
                        <h2>${esc(catNome)}</h2>
                        <span class="turma-section-count">${lista.length} turma${lista.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
                        ${cardsHtml}
                    </div>
                </div>`;
        });

        document.getElementById('listaTurmasMobile').innerHTML = html;
        document.getElementById('listaTurmasDesktop').innerHTML = html;
    }

    /* ── FILTER ── */
    function filtrarTurmas() {
        const termo = (document.getElementById('buscaTurmaDesk').value || document.getElementById('buscaTurmaMob').value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const interclasse = window._interclasseCache;

        if (!termo) {
            renderizarTurmas(turmasData, interclasse);
            return;
        }

        const filtradas = turmasData.filter(t =>
            (t.nome_turma || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(termo) ||
            (t.nome_fantasia_turma || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(termo) ||
            (t.nome_categoria || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(termo)
        );

        if (!filtradas.length) {
            renderizarEmptyState(`Nenhum resultado para "${document.getElementById('buscaTurmaDesk').value || document.getElementById('buscaTurmaMob').value}"`, 'Tente buscar por nome, fantasia ou categoria.');
            return;
        }
        renderizarTurmas(filtradas, interclasse);
    }

    /* ── SYNC SEARCH ── */
    document.addEventListener('DOMContentLoaded', () => {
        const desk = document.getElementById('buscaTurmaDesk');
        const mob = document.getElementById('buscaTurmaMob');
        desk?.addEventListener('input', () => { mob.value = desk.value; filtrarTurmas(); });
        mob?.addEventListener('input', () => { desk.value = mob.value; filtrarTurmas(); });
    });

    /* ── MAIN LOAD ── */
    async function carregarTurmasAtivas() {
        renderizarSkeleton();

        try {
            const interclasse = await resolverInterclasse();
            if (!interclasse) {
                renderizarEmptyState('Nenhum interclasse ativo.', 'Selecione um interclasse para ver as turmas.');
                return;
            }

            window._interclasseCache = interclasse;

            ['nomeInterclasseCategoria', 'nomeInterclasseCatMob'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = interclasse.nome_interclasse;
            });
            ['btnVoltarCatDesk', 'btnVoltarCatMob'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.href = `./dashboard.php?id=${interclasse.id_interclasse}`;
            });
            window.SGIInterclasse.updatePageTitle(interclasse.nome_interclasse);

            const turmasRes = await fetch(`../../../api/turmas.php?id_interclasse=${interclasse.id_interclasse}`);
            const listaFinal = await turmasRes.json();

            turmasData = Array.isArray(listaFinal) ? listaFinal : [];

            if (!turmasData.length) {
                renderizarEmptyState();
                return;
            }

            renderizarTurmas(turmasData, interclasse);
        } catch (error) {
            console.error(error);
            renderizarEmptyState('Erro ao carregar turmas.', 'Não foi possível conectar ao servidor.');
        }
    }

    /* ── CATEGORIAS MODAL ── */
    async function carregarCategoriasModal() {
        try {
            const interclasse = await resolverInterclasse();
            if (!interclasse) return;

            const res = await fetch(`../../../api/categorias.php?id_interclasse=${interclasse.id_interclasse}`);
            const categorias = await res.json();
            const sel = document.getElementById('categoriaTurma');
            sel.innerHTML = '<option value="">Selecione...</option>';
            (categorias || []).forEach(cat => {
                sel.innerHTML += `<option value="${cat.id_categoria}">${cat.nome_categoria}</option>`;
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    document.getElementById('exampleModal').addEventListener('show.bs.modal', () => {
        carregarCategoriasModal();
    });

    document.getElementById('formNovaTurma').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const interclasse = await resolverInterclasse();
            if (!interclasse) {
                mostrarToast('Nenhum interclasse disponível.', 'error');
                return;
            }

            const body = {
                interclasses_id_interclasse: interclasse.id_interclasse,
                categorias_id_categoria: document.getElementById('categoriaTurma').value,
                nome_turma: document.getElementById('nomeTurma').value.trim(),
                nome_fantasia_turma: document.getElementById('nomeFantasia').value.trim() || null,
                turno_turma: document.getElementById('turnoTurma').value || null,
                status_turma: '1'
            };

            if (!body.categorias_id_categoria) {
                mostrarToast('Selecione uma categoria.', 'error');
                return;
            }

            const res = await fetch('../../../api/turmas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Erro ao criar turma.');

            const pdf = document.getElementById('arquivoUpload').files?.[0];
            if (pdf) {
                const formData = new FormData();
                formData.append('pdf_arquivo', pdf);
                formData.append('nome_turma', body.nome_turma);
                formData.append('id_interclasse', String(interclasse.id_interclasse));
                formData.append('id_categoria', String(body.categorias_id_categoria));
                formData.append('id_turma', String(data.id_turma));
                const up = await fetch('../../../api/upload_turma_pdf.php', {
                    method: 'POST',
                    body: formData
                });
                const upJson = await up.json().catch(() => ({}));
                if (!up.ok || upJson.success === false) {
                    mostrarToast('Turma criada, mas falha ao processar PDF.', 'info');
                } else {
                    mostrarToast('Turma criada e PDF processado com sucesso!', 'success');
                }
            } else {
                mostrarToast('Turma criada com sucesso!', 'success');
            }

            bootstrap.Modal.getInstance(document.getElementById('exampleModal')).hide();
            document.getElementById('formNovaTurma').reset();
            document.getElementById('nomeArquivo').textContent = '';
            carregarTurmasAtivas();
        } catch (error) {
            mostrarToast(error.message, 'error');
        }
    });

    /* ── CATEGORIAS EDIÇÃO ── */
    async function carregarCategoriasEdicao(selectedId) {
        try {
            const interclasse = await resolverInterclasse();
            if (!interclasse) return;
            const res = await fetch(`../../../api/categorias.php?id_interclasse=${interclasse.id_interclasse}`);
            const cats = await res.json();
            const sel = document.getElementById('editCategoriaTurma');
            sel.innerHTML = '<option value="">Selecione...</option>';
            (cats || []).forEach(cat => {
                sel.innerHTML += `<option value="${cat.id_categoria}" ${cat.id_categoria == selectedId ? 'selected' : ''}>${cat.nome_categoria}</option>`;
            });
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    }

    window.editarTurma = async function(idTurma) {
        const turma = turmasData.find(t => t.id_turma == idTurma);
        if (!turma) return;

        editTurmaId = turma.id_turma;
        document.getElementById('editNomeTurma').value = turma.nome_turma || '';
        document.getElementById('editNomeFantasia').value = turma.nome_fantasia_turma || '';
        document.getElementById('editTurnoTurma').value = turma.turno_turma || '';
        document.getElementById('msgEditarTurma').innerHTML = '';

        await carregarCategoriasEdicao(turma.categorias_id_categoria);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarTurma'));
        modal.show();
    };

    document.getElementById('formEditarTurma').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarEdicaoTurma');
        const msg = document.getElementById('msgEditarTurma');

        const nome = document.getElementById('editNomeTurma').value.trim();
        if (!nome) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">O nome não pode estar vazio.</p>';
            return;
        }

        const interclasse = await resolverInterclasse();
        if (!interclasse) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Nenhum interclasse disponível.</p>';
            return;
        }

        const body = {
            id_turma: editTurmaId,
            nome_turma: nome,
            nome_fantasia_turma: document.getElementById('editNomeFantasia').value.trim() || null,
            turno_turma: document.getElementById('editTurnoTurma').value || null,
            categorias_id_categoria: parseInt(document.getElementById('editCategoriaTurma').value)
        };

        if (!body.categorias_id_categoria) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Selecione uma categoria.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const resp = await fetch('../../../api/turmas.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao atualizar.');

            msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Salvo com sucesso!</p>';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarTurma')).hide();
                carregarTurmasAtivas();
            }, 800);
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });

    /* ── EXCLUSÃO COM MODAL ── */
    let excluirIdPendente = null;

    window.abrirModalExcluir = function(idTurma, nomeTurma) {
        excluirIdPendente = idTurma;
        document.getElementById('excluirTurmaNome').textContent = nomeTurma;
        const modal = new bootstrap.Modal(document.getElementById('modalExcluirTurma'));
        modal.show();
    };

    document.getElementById('btnConfirmarExclusao').addEventListener('click', async () => {
        if (!excluirIdPendente) return;

        const btn = document.getElementById('btnConfirmarExclusao');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Excluindo...';

        try {
            const res = await fetch(`../../../api/turmas.php?id_turma=${excluirIdPendente}`, { method: 'DELETE' });
            const texto = await res.text();
            let data = null;
            try { data = JSON.parse(texto); } catch (_) {}

            if (!res.ok || !data || data.success === false) {
                throw new Error(data?.message || 'Não é possível excluir esta turma pois existem registros vinculados a ela.');
            }

            bootstrap.Modal.getInstance(document.getElementById('modalExcluirTurma')).hide();
            mostrarToast('Turma excluída com sucesso!', 'success');
            excluirIdPendente = null;
            await carregarTurmasAtivas();
        } catch (error) {
            mostrarToast(error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Sim, excluir';
        }
    });

    document.getElementById('modalExcluirTurma').addEventListener('hidden.bs.modal', () => {
        excluirIdPendente = null;
    });

    /* ── INIT ── */
    window.addEventListener('load', carregarTurmasAtivas);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>