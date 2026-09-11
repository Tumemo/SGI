window.SGIPage.mount("competicoes/modalidade-detalhes", function (pageConfig, pageScope) {

    const esc = (value) => window.SGIHtml
        ? window.SGIHtml.escape(value)
        : String(value == null ? '' : value).replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));

    let modalidadeAtual = null;
    let idInterclasseAtual = null;
    const PERMITE_EXCLUIR = pageConfig.value2;

    async function carregarDetalhesModalidade() {
        const params = new URLSearchParams(window.location.search);
        const idModalidade = params.get('id_modalidade') || params.get('id');
        if (!idModalidade) return;

        try {
            const [resModalidade, resEquipes] = await Promise.all([
                fetch(`/api/v1/modalidades?id_modalidade=${idModalidade}`),
                fetch(`/api/v1/equipes?id_modalidade=${idModalidade}`)
            ]);
            const modalidades = await resModalidade.json();
            const equipes = await resEquipes.json();
            const modalidade = (Array.isArray(modalidades) ? modalidades : [])[0];
            if (!modalidade) throw new Error('Modalidade não encontrada.');

            modalidadeAtual = modalidade;
            idInterclasseAtual = modalidade.interclasses_id_interclasse || params.get('id') || null;

            if (idInterclasseAtual) {
                document.getElementById('btnVoltarDashboardDesktop').href = `/painel?id=${idInterclasseAtual}`;
                const ic = await window.SGIInterclasse.getInterclasseById(idInterclasseAtual);
                if (ic?.nome_interclasse) {
                    const el = document.getElementById('nomeInterModalidadeDet');
                    if (el) el.textContent = ic.nome_interclasse;
                }
            }

            const turmasUnicas = [...new Set((equipes || []).map((item) => item.nome_turma).filter(Boolean))];
            const qtdEquipes = Array.isArray(equipes) ? equipes.length : 0;
            const nomeModalidade = esc(modalidade.nome_modalidade);

            ['nomeModalidadeHeadDesktop'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = nomeModalidade || 'Modalidade';
            });

            const btnExcluir = PERMITE_EXCLUIR
                ? '<button type="button" class="mdd-btn-delete btn btn-outline-danger d-inline-flex align-items-center gap-2" data-sgi-action="delete-modalidade"><i class="bi bi-trash3"></i> Excluir</button>'
                : '';

            const resumoHtml = `
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="h4 fw-bold text-body mb-0">${nomeModalidade}</h2>
                        ${modalidade.nome_categoria ? `<span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis"><i class="bi bi-tag me-1"></i> ${esc(modalidade.nome_categoria)}</span>` : ''}
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-sgi-action="edit-modalidade"><i class="bi bi-pencil-square"></i> Editar</button>
                        ${btnExcluir}
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
                    <div class="col"><div class="bg-body-tertiary border rounded-3 p-3 h-100">
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Categoria</div>
                        <div class="fs-4 fw-bold text-body">${esc(modalidade.nome_categoria || '-')}</div>
                    </div>
                    </div>
                    <div class="col"><div class="bg-body-tertiary border rounded-3 p-3 h-100">
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Tipo</div>
                        <div class="fs-4 fw-bold text-body">${esc(modalidade.nome_tipo_modalidade || '-')}</div>
                    </div>
                    </div>
                    <div class="col"><div class="bg-body-tertiary border rounded-3 p-3 h-100">
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Limite de inscritos</div>
                        <div class="fs-4 fw-bold text-body">${esc(modalidade.max_inscrito_modalidade || 'Ilimitado')}</div>
                    </div>
                    </div>
                    <div class="col"><div class="bg-body-tertiary border rounded-3 p-3 h-100">
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Máx. equipes por turma</div>
                        <div class="fs-4 fw-bold text-body">${esc(modalidade.max_equipes || 'Ilimitado')}</div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 g-3 pt-3 border-top">
                    <div class="col"><div class="d-flex align-items-center gap-3 border rounded-3 p-3 h-100">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex fs-5"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary lh-1 mb-1">${qtdEquipes}</div>
                            <div class="small text-body-secondary">Equipes cadastradas</div>
                        </div>
                    </div></div>
                    <div class="col"><div class="d-flex align-items-center gap-3 border rounded-3 p-3 h-100">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex fs-5"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <div class="fs-3 fw-bold text-primary lh-1 mb-1">${turmasUnicas.length}</div>
                            <div class="small text-body-secondary">Turmas vinculadas</div>
                        </div>
                    </div></div>
                </div>
            `;
            document.getElementById('resumoModalidadeDesktop').innerHTML = resumoHtml;

            const htmlTurmas = turmasUnicas.length
                ? turmasUnicas.map((nome) => `
                    <div class="col"><article class="card h-100 border shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex fs-5"><i class="bi bi-mortarboard-fill"></i></div>
                        <div class="sgi-u-min-width-0">
                            <div class="fw-bold text-body text-truncate">${esc(nome)}</div>
                            <div class="small text-body-secondary">Turma participante</div>
                        </div>
                    </article></div>`).join('')
                : `<div class="col-12"><div class="text-center py-4 border rounded-3 bg-body-tertiary">
                    <div class="fs-3 text-body-secondary mb-2"><i class="bi bi-inbox"></i></div>
                    <div class="fw-semibold text-body-secondary">Nenhuma turma vinculada</div>
                    <p class="small text-body-secondary mb-0">Esta modalidade ainda não possui turmas vinculadas.</p>
                   </div></div>`;
            const htmlEquipes = qtdEquipes
                ? equipes.map((item) => `
                    <div class="col"><article class="card h-100 border shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-inline-flex fs-5"><i class="bi bi-shield-fill"></i></div>
                        <div class="sgi-u-min-width-0">
                            <div class="fw-bold text-body text-truncate">${item.nome_equipe ? esc(item.nome_equipe) : `Equipe #${esc(item.id_equipe)}`}</div>
                            <div class="small text-body-secondary text-truncate">${esc(item.nome_turma || 'Turma não informada')}</div>
                        </div>
                    </article></div>`).join('')
                : `<div class="col-12"><div class="text-center py-4 border rounded-3 bg-body-tertiary">
                    <div class="fs-3 text-body-secondary mb-2"><i class="bi bi-inbox"></i></div>
                    <div class="fw-semibold text-body-secondary">Nenhuma equipe cadastrada</div>
                    <p class="small text-body-secondary mb-0">Esta modalidade ainda não possui equipes cadastradas.</p>
                   </div></div>`;

            document.getElementById('listaTurmasDesktop').innerHTML = htmlTurmas;
            document.getElementById('listaEquipesDesktop').innerHTML = htmlEquipes;

            const setCount = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            setCount('countTurmasDesktop', turmasUnicas.length);
            setCount('countEquipesDesktop', qtdEquipes);
        } catch (error) {
            document.getElementById('resumoModalidadeDesktop').innerHTML = `<p class="text-danger m-0">${esc(error.message)}</p>`;
            document.getElementById('listaTurmasDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaEquipesDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
        }
    }

    async function carregarTiposEdicao(selectedId) {
        const select = document.getElementById('editTipoModalidade');
        try {
            const resp = await fetch('/api/v1/tipos-modalidade');
            const tipos = await resp.json();
            const placeholder = new Option('Selecione...', '');
            placeholder.disabled = true;
            placeholder.selected = !selectedId;
            select.replaceChildren(placeholder);
            tipos.forEach(t => {
                const option = new Option(String(t.nome_tipo_modalidade || ''), String(t.id_tipo_modalidade));
                option.selected = String(t.id_tipo_modalidade) === String(selectedId);
                select.add(option);
            });
        } catch (e) {
            select.replaceChildren(new Option('Erro ao carregar', ''));
            select.options[0].disabled = true;
            select.options[0].selected = true;
        }
    }

    async function carregarCategoriasEdicao(selectedId) {
        const idInterclasse = idInterclasseAtual || new URLSearchParams(window.location.search).get('id');
        const select = document.getElementById('editCategoriaModalidade');
        try {
            const resp = await fetch(`/api/v1/categorias?id_interclasse=${idInterclasse}`);
            const cats = await resp.json();
            const placeholder = new Option('Selecione...', '');
            placeholder.disabled = true;
            placeholder.selected = !selectedId;
            select.replaceChildren(placeholder);
            cats.forEach(c => {
                const option = new Option(String(c.nome_categoria || ''), String(c.id_categoria));
                option.selected = String(c.id_categoria) === String(selectedId);
                select.add(option);
            });
        } catch (e) {
            select.replaceChildren(new Option('Erro ao carregar', ''));
            select.options[0].disabled = true;
            select.options[0].selected = true;
        }
    }

    async function abrirModalEdicao() {
        if (!modalidadeAtual) return;

        document.getElementById('editNomeModalidade').value = modalidadeAtual.nome_modalidade;
        document.getElementById('editGeneroModalidade').value = modalidadeAtual.genero_modalidade || '';
        document.getElementById('editMaxInscritos').value = modalidadeAtual.max_inscrito_modalidade || '';
        document.getElementById('editMaxEquipes').value = modalidadeAtual.max_equipes || '';
        document.getElementById('msgEditarModalidade').innerHTML = '';

        await Promise.all([
            carregarTiposEdicao(modalidadeAtual.id_tipo_modalidade),
            carregarCategoriasEdicao(modalidadeAtual.categorias_id_categoria)
        ]);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarModalidade'));
        modal.show();
    }

    async function excluirModalidade() {
        if (!modalidadeAtual) return;
        if (!await SGI.confirm({ titulo: 'Excluir modalidade?', mensagem: `A modalidade "${modalidadeAtual.nome_modalidade}" será excluída. Esta ação não pode ser desfeita.`, textoConfirmar: 'Excluir modalidade', destrutivo: true })) return;

        const btn = document.querySelector('.mdd-btn-delete');
        const originalText = btn?.innerHTML || '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-trash3"></i> Excluindo...'; }

        try {
            const resp = await fetch('/api/v1/modalidades', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: modalidadeAtual.id_modalidade })
            });
            const data = await resp.json();

            if (!resp.ok || data.success === false) {
                SGI.alert(data.message || 'Erro ao excluir.');
                return;
            }

            const idInterclasse = idInterclasseAtual;
            window.location.href = idInterclasse
                ? `/edicoes/modalidades?id=${idInterclasse}&modo=view`
                : '/edicoes/modalidades';
        } catch (e) {
            SGI.alert('Erro de conexão.');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        }
    }

    pageScope.listen(document.getElementById('formEditarModalidade'), 'submit', async (e) => {
        e.preventDefault();
        if (!modalidadeAtual) return;

        const btn = document.getElementById('btnSalvarEdicao');
        const msg = document.getElementById('msgEditarModalidade');

        const dados = {
            id_modalidade: modalidadeAtual.id_modalidade,
            nome_modalidade: document.getElementById('editNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('editGeneroModalidade').value,
            max_inscrito_modalidade: parseInt(document.getElementById('editMaxInscritos').value) || 0,
            max_equipes: (() => { const v = document.getElementById('editMaxEquipes').value; return v === '' ? null : parseInt(v); })(),
            tipos_modalidades_id_tipo_modalidade: parseInt(document.getElementById('editTipoModalidade').value),
            categorias_id_categoria: parseInt(document.getElementById('editCategoriaModalidade').value)
        };

        if (!dados.nome_modalidade) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">O nome não pode estar vazio.</p>';
            return;
        }
        if (!dados.genero_modalidade) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Selecione o gênero da modalidade.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const resp = await fetch('/api/v1/modalidades', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao atualizar.');

            msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Salvo com sucesso!</p>';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarModalidade')).hide();
                carregarDetalhesModalidade();
            }, 800);
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${esc(err.message)}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });

    window.SGIPage.ready(() => {
        const resumo = document.getElementById('resumoModalidadeDesktop');
        pageScope.listen(resumo, 'click', (event) => {
            const action = event.target.closest('[data-sgi-action]');
            if (!action) return;
            if (action.dataset.sgiAction === 'edit-modalidade') abrirModalEdicao();
            if (action.dataset.sgiAction === 'delete-modalidade') excluirModalidade();
        });
        carregarDetalhesModalidade();
    });

return {carregarDetalhesModalidade, carregarTiposEdicao, carregarCategoriasEdicao, abrirModalEdicao, excluirModalidade};
});
