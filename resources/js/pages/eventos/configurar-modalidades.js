window.SGIPage.mount("eventos/configurar-modalidades", function (pageConfig, pageScope) {

    const APP_BASE = window.SGI_BASE_PATH || '';

    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const idCategoria = urlParams.get('id_categoria');
    const modo = urlParams.get('modo') || 'view';
    let modalidadeSelecionada = null;

    /* ── HELPERS ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // TRAVA DE SEGURANÇA e Configuração dos Botões "Voltar"
    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            const msg = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('listaModalidadesDesktop').innerHTML = msg;
            window.location.href = "/edicoes";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const nome = dados?.nome_interclasse || 'Interclasse';
        ['nomeInterclasseModalidades'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = nome;
        });
        window.SGIInterclasse.updatePageTitle(nome);
        atualizarBotaoContinuar();
        return idInterclasse;
    }

    function atualizarBotaoContinuar() {
        const botaoDesktop = document.getElementById('btnContinuarDesktop');

        if (botaoDesktop) {
            botaoDesktop.href = `/edicoes/pontuacao?id=${idInterclasse}&modo=create${modalidadeSelecionada ? `&id_modalidade=${modalidadeSelecionada}` : ''}`;
            const disabled = !modalidadeSelecionada;
            botaoDesktop.classList.toggle('disabled', disabled);
            botaoDesktop.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            botaoDesktop.classList.toggle('d-none', modo === 'view');
        }

        const destinoVoltar = modo === 'view'
            ? `/painel?id=${idInterclasse}`
            : `/edicoes/categorias?id=${idInterclasse}&modo=create`;
        const btnVoltar = document.getElementById('btnVoltarModalidades');
        if (btnVoltar) btnVoltar.href = destinoVoltar;
    }

    /* ── RENDER CARD ── */
    function renderizarCard(modalidade) {
        const destino = `/modalidades/detalhes?id=${modalidade.id_modalidade}`;
        const genero = modalidade.genero_modalidade || '';
        const generoLabel = genero === 'MASC' ? 'Masculino' : genero === 'FEM' ? 'Feminino' : genero === 'MISTO' ? 'Misto' : genero;
        const qtdEquipes = Number(modalidade.qtd_equipes) || 0;
        const tipo = modalidade.nome_tipo_modalidade || '';

        return `
            <div class="col">
                <a href="${destino}" class="card h-100 border shadow-sm text-body text-decoration-none p-3 d-flex flex-column modalidade-card-simples" data-id="${modalidade.id_modalidade}" aria-label="Ver detalhes de ${esc(modalidade.nome_modalidade)}">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 fs-4 d-inline-flex flex-shrink-0"><i class="bi bi-trophy"></i></div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h5 class="fw-bold text-body mb-0 text-truncate">${esc(modalidade.nome_modalidade)}</h5>
                            <small class="text-body-secondary text-truncate d-block mt-1">${esc(tipo)}${tipo && genero ? ' · ' : ''}${esc(generoLabel)}</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle"><i class="bi bi-people"></i> ${qtdEquipes} equipe${qtdEquipes !== 1 ? 's' : ''}</span>
                        ${modalidade.max_inscrito_modalidade ? `<span class="badge rounded-pill text-bg-light border text-body-secondary"><i class="bi bi-person-lines-fill"></i> Máx. ${esc(modalidade.max_inscrito_modalidade)}</span>` : ''}
                    </div>
                    <span class="btn btn-sm btn-outline-primary align-self-end mt-auto d-inline-flex align-items-center gap-2">
                        Ver detalhes <i class="bi bi-arrow-right"></i>
                    </span>
                </a>
            </div>`;
    }

    /* ── RENDER POR CATEGORIA ── */
    function renderizarModalidades(modalidades) {
        const divDesktop = document.getElementById('listaModalidadesDesktop');
        if (!divDesktop) return;

        if (!modalidades.length) {
            divDesktop.innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
            return;
        }

        const grupos = {};
        modalidades.forEach(m => {
            const chave = m.nome_categoria || 'Sem categoria';
            if (!grupos[chave]) grupos[chave] = [];
            grupos[chave].push(m);
        });

        let html = '';
        Object.entries(grupos).forEach(([catNome, lista]) => {
            html += `
                <section class="mb-5">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-primary-subtle">
                        <h2 class="h5 fw-bold text-body mb-0">${esc(catNome)}</h2>
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis" title="Total de modalidades">${lista.length}</span>
                    </div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
                        ${lista.map(renderizarCard).join('')}
                    </div>
                </section>`;
        });

        divDesktop.innerHTML = html;
        ligarEventosCards();
    }

    /* ── EVENTOS DOS CARDS ── */
    function ligarEventosCards() {
        document.querySelectorAll('.modalidade-card-simples').forEach((card) => {
            pageScope.listen(card, 'click', (e) => {
                if (modo === 'create') {
                    e.preventDefault();
                    selecionarModalidade(Number(card.dataset.id));
                }
            });
        });
    }

    function selecionarModalidade(id) {
        modalidadeSelecionada = Number(id);
        document.querySelectorAll('.modalidade-card-simples').forEach((card) => {
            card.classList.toggle('is-selected', Number(card.dataset.id) === modalidadeSelecionada);
            const selecionado = Number(card.dataset.id) === modalidadeSelecionada;
            card.classList.toggle('border-primary', selecionado);
            card.classList.toggle('border-3', selecionado);
            card.classList.toggle('bg-primary-subtle', selecionado);
        });
        atualizarBotaoContinuar();
    }

    // 1. FUNÇÃO: Listar as modalidades já existentes (Cards)
    async function carregarModalidades() {
        try {
            const filtroCategoria = idCategoria ? `&id_categoria=${idCategoria}` : '';
            const response = await axios.get(`/api/v1/modalidades?x=1${filtroCategoria}`);
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidades = modalidades.filter((item) => String(item.interclasses_id_interclasse) === String(idInterclasse));
            renderizarModalidades(modalidades);
        } catch (error) {
            console.error("Erro ao carregar lista:", error);
            const msgErro = '<p class="text-muted mt-4 text-center w-100">Erro ao carregar modalidades.</p>';
            document.getElementById('listaModalidadesDesktop').innerHTML = msgErro;
        }
    }

    // 2. FUNÇÃO: Preencher o Select de TIPOS (Vem da api/v1/tipos-modalidade)
    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('/api/v1/tipos-modalidade');
            const tipos = response.data;
            const placeholder = new Option('Selecione um tipo...', '');
            placeholder.disabled = true;
            placeholder.selected = true;
            selectTipo.replaceChildren(placeholder);
            tipos.forEach(tipo => {
                selectTipo.add(new Option(String(tipo.nome_tipo_modalidade || ''), String(tipo.id_tipo_modalidade)));
            });
        } catch (error) {
            console.error("Erro ao carregar tipos:", error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 3. FUNÇÃO: Preencher o Select de CATEGORIAS (Vem da api/v1/categorias)
    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get(`/api/v1/categorias?id_interclasse=${idInterclasse}`);
            const categorias = response.data;
            const placeholder = new Option('Selecione uma categoria...', '');
            placeholder.disabled = true;
            placeholder.selected = !idCategoria;
            selectCat.replaceChildren(placeholder);
            categorias.forEach((cat) => {
                const option = new Option(String(cat.nome_categoria || ''), String(cat.id_categoria));
                option.selected = Boolean(idCategoria && String(idCategoria) === String(cat.id_categoria));
                selectCat.add(option);
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 4. EVENTO: Enviar Formulário de Criação
    pageScope.listen(document.getElementById('formNovaModalidade'), 'submit', async (e) => {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');

        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_modalidade: document.getElementById('inputNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('inputGeneroModalidade').value,
            max_inscrito_modalidade: parseInt(document.getElementById('inputMaxInscritos').value) || 0,
            max_equipes: (() => { const v = document.getElementById('inputMaxEquipes').value; return v === '' ? null : parseInt(v); })(),
            tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
            categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = "Salvando...";
            const res = await axios.post('/api/v1/modalidades', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center fw-bold">Criada com sucesso!</p>`;
                document.getElementById('formNovaModalidade').reset();
                modalidadeSelecionada = Number(res.data.id);
                carregarModalidades();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('exampleModal')).hide();
                    caixaMensagem.innerHTML = "";
                }, 1000);
            }
        } catch (error) {
            caixaMensagem.innerHTML = `<p class="text-danger text-center fw-bold">Erro ao salvar.</p>`;
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    /* ── ALUNOS DESTAQUES ── */
    function montarItemDestaque(a) {
        const temFoto = a.foto_usuario && !/^default\.(jpg|jpeg|png|gif|webp)$/i.test(a.foto_usuario);
        const fotoHtml = temFoto
            ? `<img class="rounded-circle object-fit-cover flex-shrink-0" width="48" height="48" src="${APP_BASE}/uploads/fotosUsuarios/${encodeURIComponent(a.foto_usuario)}" alt="${esc(a.nome_usuario)}" onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');">`
            : '';
        const iconeHtml = `<span class="${temFoto ? 'd-none' : 'rounded-circle bg-warning-subtle text-warning-emphasis p-2 fs-4 d-inline-flex align-items-center justify-content-center'}"><i class="bi bi-star-fill"></i></span>`;
        const turma = a.nome_fantasia_turma || a.nome_turma || 'Sem turma';
        const gols = Number(a.total_gols) || 0;

        return `
            <div class="card border-0 bg-body-tertiary mb-2">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    ${fotoHtml}${iconeHtml}
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold text-body text-truncate">${esc(a.nome_usuario)} <i class="bi bi-star-fill text-warning"></i></div>
                        <div class="small text-body-secondary text-truncate">${esc(a.nome_categoria || 'Sem categoria')} · ${esc(turma)}</div>
                    </div>
                    <div class="text-end fw-bold text-primary fs-5 flex-shrink-0">${gols}<small class="d-block text-body-secondary text-uppercase fs-6 fw-semibold">gol${gols !== 1 ? 's' : ''}</small></div>
                </div>
            </div>`;
    }

    async function carregarDestaques() {
        const corpo = document.getElementById('corpoDestaques');
        corpo.innerHTML = `<div class="text-center py-5">
            <div class="spinner-border text-danger" role="status"></div>
            <p class="text-muted mt-3 mb-0">Carregando destaques...</p>
        </div>`;

        try {
            const res = await axios.get(`/api/v1/artilheiros?acao=destaques_modalidades&id_interclasse=${idInterclasse}`);
            const raw = res.data && res.data.data !== undefined ? res.data.data : res.data;
            const lista = Array.isArray(raw) ? raw : [];

            if (!lista.length) {
                corpo.innerHTML = '<p class="text-muted text-center py-4">Nenhum aluno destaque registrado para este interclasse ainda.</p>';
                return;
            }

            const grupos = {};
            lista.forEach(d => {
                const chave = d.nome_modalidade || 'Sem modalidade';
                if (!grupos[chave]) grupos[chave] = [];
                grupos[chave].push(d);
            });

            let html = '';
            Object.entries(grupos).forEach(([modalidade, alunos]) => {
                html += `<section class="mb-3"><h6 class="d-flex align-items-center gap-2 fw-bold text-body-secondary border-bottom pb-2 mb-2 mt-4"><i class="bi bi-trophy-fill text-primary"></i>${esc(modalidade)}</h6>`;
                html += alunos.map(montarItemDestaque).join('');
                html += '</section>';
            });
            corpo.innerHTML = html;
        } catch (error) {
            console.error('Erro ao carregar destaques:', error);
            corpo.innerHTML = '<p class="text-danger text-center py-4">Erro ao carregar os destaques.</p>';
        }
    }

    pageScope.listen(document.getElementById('modalDestaques'), 'show.bs.modal', carregarDestaques);

    // 5. INICIALIZAÇÃO: Onde a mágica acontece
    window.SGIPage.ready( async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
        atualizarBotaoContinuar();
    });

return {esc, resolverInterclasse, atualizarBotaoContinuar, renderizarCard, renderizarModalidades, ligarEventosCards, selecionarModalidade, carregarModalidades, carregarTiposModalidades, carregarCategoriasModalidades, montarItemDestaque, carregarDestaques};
});
