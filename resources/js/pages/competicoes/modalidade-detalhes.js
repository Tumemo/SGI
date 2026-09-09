window.SGIPage.mount("competicoes/modalidade-detalhes", function (pageConfig, pageScope) {

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
                ? '<button class="mdd-btn-delete" onclick="excluirModalidade()"><i class="bi bi-trash3"></i> Excluir</button>'
                : '';

            const resumoHtml = `
                <div class="mdd-hero__top">
                    <div class="mdd-hero__name">
                        <h2 class="mdd-hero__name-text">${nomeModalidade}</h2>
                        ${modalidade.nome_categoria ? `<span class="mdd-badge"><i class="bi bi-tag"></i> ${esc(modalidade.nome_categoria)}</span>` : ''}
                    </div>
                    <div class="mdd-hero__actions">
                        <button class="mdd-btn-edit" onclick="abrirModalEdicao()"><i class="bi bi-pencil-square"></i> Editar</button>
                        ${btnExcluir}
                    </div>
                </div>
                <div class="mdd-info">
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Categoria</div>
                        <div class="mdd-info__value">${esc(modalidade.nome_categoria || '-')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Tipo</div>
                        <div class="mdd-info__value">${esc(modalidade.nome_tipo_modalidade || '-')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Limite de inscritos</div>
                        <div class="mdd-info__value">${esc(modalidade.max_inscrito_modalidade || 'Ilimitado')}</div>
                    </div>
                    <div class="mdd-info__item">
                        <div class="mdd-info__label">Máx. equipes por turma</div>
                        <div class="mdd-info__value">${esc(modalidade.max_equipes || 'Ilimitado')}</div>
                    </div>
                </div>
                <div class="mdd-hero__footer">
                    <div class="mdd-stat">
                        <div class="mdd-stat__icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="mdd-stat__num">${qtdEquipes}</div>
                            <div class="mdd-stat__label">Equipes cadastradas</div>
                        </div>
                    </div>
                    <div class="mdd-stat">
                        <div class="mdd-stat__icon"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <div class="mdd-stat__num">${turmasUnicas.length}</div>
                            <div class="mdd-stat__label">Turmas vinculadas</div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('resumoModalidadeDesktop').innerHTML = resumoHtml;

            const htmlTurmas = turmasUnicas.length
                ? turmasUnicas.map((nome) => `
                    <div class="mdd-turma">
                        <div class="mdd-turma__icon"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div class="mdd-turma__name">${esc(nome)}</div>
                            <div class="mdd-turma__meta">Turma participante</div>
                        </div>
                    </div>`).join('')
                : `<div class="mdd-empty">
                    <div class="mdd-empty__icon"><i class="bi bi-inbox"></i></div>
                    <div class="mdd-empty__title">Nenhuma turma vinculada</div>
                    <p class="mdd-empty__desc">Esta modalidade ainda não possui turmas vinculadas.</p>
                   </div>`;
            const htmlEquipes = qtdEquipes
                ? equipes.map((item) => `
                    <div class="mdd-equipe">
                        <div class="mdd-equipe__icon"><i class="bi bi-shield-fill"></i></div>
                        <div>
                            <div class="mdd-equipe__name">${item.nome_equipe ? esc(item.nome_equipe) : `Equipe #${esc(item.id_equipe)}`}</div>
                            <div class="mdd-equipe__meta">${esc(item.nome_turma || 'Turma não informada')}</div>
                        </div>
                    </div>`).join('')
                : `<div class="mdd-empty">
                    <div class="mdd-empty__icon"><i class="bi bi-inbox"></i></div>
                    <div class="mdd-empty__title">Nenhuma equipe cadastrada</div>
                    <p class="mdd-empty__desc">Esta modalidade ainda não possui equipes cadastradas.</p>
                   </div>`;

            document.getElementById('listaTurmasDesktop').innerHTML = htmlTurmas;
            document.getElementById('listaEquipesDesktop').innerHTML = htmlEquipes;

            const setCount = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            setCount('countTurmasDesktop', turmasUnicas.length);
            setCount('countEquipesDesktop', qtdEquipes);
        } catch (error) {
            document.getElementById('resumoModalidadeDesktop').innerHTML = `<p class="text-danger m-0">${error.message}</p>`;
            document.getElementById('listaTurmasDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('listaEquipesDesktop').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
        }
    }

    async function carregarTiposEdicao(selectedId) {
        const select = document.getElementById('editTipoModalidade');
        try {
            const resp = await fetch('/api/v1/tipos-modalidade');
            const tipos = await resp.json();
            select.innerHTML = '<option value="" disabled>Selecione...</option>';
            tipos.forEach(t => {
                const sel = t.id_tipo_modalidade == selectedId ? 'selected' : '';
                select.innerHTML += `<option value="${t.id_tipo_modalidade}" ${sel}>${t.nome_tipo_modalidade}</option>`;
            });
        } catch (e) {
            select.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function carregarCategoriasEdicao(selectedId) {
        const idInterclasse = idInterclasseAtual || new URLSearchParams(window.location.search).get('id');
        const select = document.getElementById('editCategoriaModalidade');
        try {
            const resp = await fetch(`/api/v1/categorias?id_interclasse=${idInterclasse}`);
            const cats = await resp.json();
            select.innerHTML = '<option value="" disabled>Selecione...</option>';
            cats.forEach(c => {
                const sel = c.id_categoria == selectedId ? 'selected' : '';
                select.innerHTML += `<option value="${c.id_categoria}" ${sel}>${c.nome_categoria}</option>`;
            });
        } catch (e) {
            select.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
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
        if (!confirm(`Tem certeza que deseja excluir a modalidade "${modalidadeAtual.nome_modalidade}"?`)) return;

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
                alert(data.message || 'Erro ao excluir.');
                return;
            }

            const idInterclasse = idInterclasseAtual;
            window.location.href = idInterclasse
                ? `/edicoes/modalidades?id=${idInterclasse}&modo=view`
                : '/edicoes/modalidades';
        } catch (e) {
            alert('Erro de conexão.');
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
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });

    window.SGIPage.ready( carregarDetalhesModalidade);

return {carregarDetalhesModalidade, carregarTiposEdicao, carregarCategoriasEdicao, abrirModalEdicao, excluirModalidade};
});
