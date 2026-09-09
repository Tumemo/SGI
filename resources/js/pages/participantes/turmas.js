window.SGIPage.mount("participantes/turmas", function (pageConfig, pageScope) {

    let turmasData = [];
    let editTurmaId = null;
    const NIVEL_USUARIO = pageConfig.value2;

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
                        <div class="turma-avatar sgi-inline-53f1afc1" >${avatarLetra}</div>
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
                        <a href="/turmas/alunos?id=${interclasse.id_interclasse}&id_turma=${turma.id_turma}&id_categoria=${turma.categorias_id_categoria}" class="turma-card-btn-detalhes ms-auto">
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
    window.SGIPage.ready( () => {
        const desk = document.getElementById('buscaTurmaDesk');
        const mob = document.getElementById('buscaTurmaMob');
        pageScope.listen(desk, 'input', () => { mob.value = desk.value; filtrarTurmas(); });
        pageScope.listen(mob, 'input', () => { desk.value = mob.value; filtrarTurmas(); });
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
                if (el) el.href = `/painel?id=${interclasse.id_interclasse}`;
            });
            window.SGIInterclasse.updatePageTitle(interclasse.nome_interclasse);

            const turmasRes = await fetch(`/api/v1/turmas?id_interclasse=${interclasse.id_interclasse}`);
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

            const res = await fetch(`/api/v1/categorias?id_interclasse=${interclasse.id_interclasse}`);
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

    pageScope.listen(document.getElementById('exampleModal'), 'show.bs.modal', () => {
        carregarCategoriasModal();
    });

    pageScope.listen(document.getElementById('formNovaTurma'), 'submit', async (e) => {
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

            const res = await fetch('/api/v1/turmas', {
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
                const up = await fetch('/api/v1/importacoes/turma-pdf', {
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
            const res = await fetch(`/api/v1/categorias?id_interclasse=${interclasse.id_interclasse}`);
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

    pageScope.listen(document.getElementById('formEditarTurma'), 'submit', async (e) => {
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

            const resp = await fetch('/api/v1/turmas', {
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

    pageScope.listen(document.getElementById('btnConfirmarExclusao'), 'click', async () => {
        if (!excluirIdPendente) return;

        const btn = document.getElementById('btnConfirmarExclusao');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Excluindo...';

        try {
            const res = await fetch(`/api/v1/turmas?id_turma=${excluirIdPendente}`, { method: 'DELETE' });
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

    pageScope.listen(document.getElementById('modalExcluirTurma'), 'hidden.bs.modal', () => {
        excluirIdPendente = null;
    });

    /* ── INIT ── */
    window.SGIPage.ready( carregarTurmasAtivas);

return {mostrarToast, resolverInterclasse, esc, mostrarNomeArquivo, renderizarSkeleton, renderizarEmptyState, renderizarCard, renderizarTurmas, filtrarTurmas, carregarTurmasAtivas, carregarCategoriasModal, carregarCategoriasEdicao};
});
