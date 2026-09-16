window.SGIPage.mount("eventos/configurar-turmas", function (pageConfig, pageScope) {
    const appBase = String(window.SGI_BASE_PATH || '').replace(/\/+$/, '');
    const API = String(window.SGI_API_BASE || `${appBase}/api/v1/`).replace(/\/?$/, '/');
    const urlParams = new URLSearchParams(window.location.search);

    function positiveId(value) {
        const candidate = String(value == null ? '' : value).trim();
        if (!/^\d+$/.test(candidate)) return null;
        const parsed = Number(candidate);
        return Number.isSafeInteger(parsed) && parsed > 0 ? String(parsed) : null;
    }

    const idInterclasse = positiveId(urlParams.get('id'));
    const idCategoriaUrl = positiveId(urlParams.get('id_categoria'));

    function esc(value) {
        return window.SGIHtml.escape(String(value == null ? '' : value));
    }

    function caminhoApp(path, params) {
        const query = params ? `?${params.toString()}` : '';
        return `${appBase}/${String(path).replace(/^\/+/, '')}${query}`;
    }

    function getEl(id) {
        return document.getElementById(id);
    }

    if (!idInterclasse) {
        SGI.alert({
            titulo: 'Interclasse não selecionado',
            mensagem: 'Nenhum interclasse foi selecionado. Você será redirecionado.',
            tipo: 'warning',
        }).then(() => {
            window.location.href = caminhoApp('edicoes');
        });
    }

    const linksVoltar = ['btnVoltarTurmasMobile', 'btnVoltarTurmasDesk'];
    if (idInterclasse) {
        const paramsVoltar = new URLSearchParams({ id: idInterclasse });
        linksVoltar.forEach((id) => {
            const link = getEl(id);
            if (link) link.href = caminhoApp('edicoes/categorias', paramsVoltar);
        });
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseTurmasMob', 'nomeInterclasseTurmasDesk'].forEach((id) => {
                const el = getEl(id);
                if (el) el.textContent = nome;
            });
        }).catch(() => {});
    }

    let categoriaSelecionadaId = null;
    let categoriasAtuais = [];
    let todasTurmasAtuais = [];
    let termoBuscaTurma = '';
    let requisicaoTurmas = 0;
    let estadoTurmas = 'idle';
    let estadoCategorias = 'idle';

    const seletorCategoriaMobile = getEl('categoriaTurmasMobile');
    const instrucaoCategoriaMobile = getEl('instrucaoCategoriaTurmasMobile');
    const listaCategoriasDesktop = getEl('listaCategorias');
    const listaTurmasDesktop = getEl('listaTurmas');
    const listaTurmasMobile = getEl('listaTurmasMobile');
    const buscaDesktop = getEl('inputBuscaTurma');
    const buscaMobile = getEl('inputBuscaTurmaMobile');
    const botoesAdicionarTurma = [getEl('btnAdicionarTurmaMobile'), getEl('btnAdicionarTurmaDesktop')].filter(Boolean);

    function criarBotaoRetry(acao) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'btn btn-link btn-sm p-0 ms-1 align-baseline';
        botao.dataset.sgiRetry = acao;
        botao.textContent = 'Tentar novamente';
        return botao;
    }

    function mostrarInstrucao(texto, { erro = false, retryAction = null } = {}) {
        if (!instrucaoCategoriaMobile) return;
        instrucaoCategoriaMobile.replaceChildren(document.createTextNode(texto));
        instrucaoCategoriaMobile.classList.toggle('text-danger', erro);
        instrucaoCategoriaMobile.classList.toggle('text-secondary', !erro);
        instrucaoCategoriaMobile.setAttribute('role', erro ? 'alert' : 'status');
        instrucaoCategoriaMobile.setAttribute('aria-live', erro ? 'assertive' : 'polite');
        if (retryAction) {
            instrucaoCategoriaMobile.append(document.createTextNode(' '), criarBotaoRetry(retryAction));
        }
    }

    function mostrarMensagemLista(texto, estado = 'empty', retryAction = null) {
        [listaTurmasDesktop, listaTurmasMobile].forEach((container) => {
            if (!container) return;
            const wrapper = document.createElement('div');
            wrapper.className = `text-center my-4 ${estado === 'error' ? 'text-danger' : 'text-muted'}`;
            const mensagem = document.createElement('p');
            mensagem.className = 'mb-1';
            mensagem.textContent = texto;
            mensagem.setAttribute('role', estado === 'error' ? 'alert' : 'status');
            mensagem.setAttribute('aria-live', estado === 'error' ? 'assertive' : 'polite');
            wrapper.append(mensagem);
            if (retryAction) wrapper.append(criarBotaoRetry(retryAction));
            container.replaceChildren(wrapper);
        });
    }

    function atualizarEstadoCategorias() {
        const temCategoria = categoriaSelecionadaId !== null
            && categoriasAtuais.some((categoria) => categoria.id === categoriaSelecionadaId);

        if (seletorCategoriaMobile) {
            seletorCategoriaMobile.value = temCategoria ? categoriaSelecionadaId : '';
            seletorCategoriaMobile.disabled = categoriasAtuais.length === 0 || estadoCategorias === 'loading';
        }
        botoesAdicionarTurma.forEach((botao) => {
            botao.disabled = !temCategoria;
        });

        if (listaCategoriasDesktop) {
            listaCategoriasDesktop.querySelectorAll('[data-id-categoria]').forEach((botao) => {
                const selecionado = temCategoria && botao.dataset.idCategoria === categoriaSelecionadaId;
                botao.setAttribute('aria-pressed', selecionado ? 'true' : 'false');
                botao.classList.toggle('bg-light', selecionado);
                botao.classList.toggle('text-dark', selecionado);
                botao.classList.toggle('fw-bold', selecionado);
                botao.classList.toggle('text-secondary', !selecionado);
            });
        }
    }

    function instrucaoSemCategoria() {
        if (categoriasAtuais.length === 0) {
            return 'Esta edição ainda não tem categorias. Cadastre uma categoria para adicionar turmas.';
        }
        return 'Selecione uma categoria para ver as turmas e adicionar uma turma.';
    }

    function construirListaCategorias(categorias) {
        if (listaCategoriasDesktop) listaCategoriasDesktop.replaceChildren();
        if (seletorCategoriaMobile) {
            seletorCategoriaMobile.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Selecione uma categoria';
            seletorCategoriaMobile.append(placeholder);
        }

        categoriasAtuais = categorias.map((categoria) => ({
            id: String(Number(categoria.id_categoria)),
            nome: String(categoria.nome_categoria == null ? '' : categoria.nome_categoria),
        }));

        categoriasAtuais.forEach((categoria) => {
            if (seletorCategoriaMobile) {
                const option = document.createElement('option');
                option.value = categoria.id;
                option.textContent = categoria.nome;
                seletorCategoriaMobile.append(option);
            }

            if (listaCategoriasDesktop) {
                const botao = document.createElement('button');
                botao.type = 'button';
                botao.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-4 border-bottom border-0 fs-6 fw-medium text-secondary';
                botao.dataset.idCategoria = categoria.id;
                botao.setAttribute('aria-pressed', 'false');
                botao.append(document.createTextNode(categoria.nome));
                const icone = document.createElement('i');
                icone.className = 'bi bi-chevron-right text-muted';
                icone.setAttribute('aria-hidden', 'true');
                botao.append(icone);
                listaCategoriasDesktop.append(botao);
            }
        });

        if (categoriasAtuais.length === 0 && listaCategoriasDesktop) {
            const mensagem = document.createElement('p');
            mensagem.className = 'text-muted p-3 text-center mb-0';
            mensagem.textContent = 'Nenhuma categoria encontrada nesta edição.';
            mensagem.setAttribute('role', 'status');
            mensagem.setAttribute('aria-live', 'polite');
            listaCategoriasDesktop.append(mensagem);
        }
        if (seletorCategoriaMobile) seletorCategoriaMobile.disabled = categoriasAtuais.length === 0;
    }

    function normalizarTexto(valor) {
        return String(valor == null ? '' : valor).toLocaleLowerCase('pt-BR');
    }

    function construirLinksTurmas(turmas) {
        const categoria = categoriasAtuais.find((item) => item.id === categoriaSelecionadaId);
        const parametrosBase = new URLSearchParams({
            id: idInterclasse || '',
            id_categoria: categoria?.id || '',
        });

        return turmas.map((turma) => {
            const params = new URLSearchParams(parametrosBase);
            params.set('id_turma', String(turma.id_turma));
            const link = document.createElement('a');
            link.className = 'text-decoration-none';
            link.href = caminhoApp('turmas/alunos', params);

            const card = document.createElement('div');
            card.className = 'bg-white rounded-3 shadow-sm p-4 d-flex align-items-center justify-content-between';
            const nome = document.createElement('span');
            nome.className = 'fw-bold text-dark fs-5';
            nome.textContent = String(turma.nome_turma == null ? '' : turma.nome_turma);
            const icone = document.createElement('i');
            icone.className = 'bi bi-chevron-right text-muted';
            icone.setAttribute('aria-hidden', 'true');
            card.append(nome, icone);
            link.append(card);
            return link;
        });
    }

    function renderizarTurmas(turmas) {
        if (estadoTurmas === 'loading' || estadoTurmas === 'error') return;
        const selecionada = categoriaSelecionadaId !== null
            && categoriasAtuais.some((categoria) => categoria.id === categoriaSelecionadaId);
        if (!selecionada) {
            mostrarMensagemLista(instrucaoSemCategoria());
            return;
        }

        [listaTurmasDesktop, listaTurmasMobile].forEach((container) => {
            if (!container) return;
            container.replaceChildren();
            if (turmas.length === 0) {
                const mensagem = document.createElement('p');
                mensagem.className = 'text-muted text-center my-4';
                mensagem.tabIndex = -1;
                mensagem.setAttribute('role', 'status');
                mensagem.textContent = termoBuscaTurma
                    ? 'Nenhuma turma corresponde à busca.'
                    : 'Nenhuma turma adicionada nesta categoria.';
                container.append(mensagem);
                return;
            }
            container.append(...construirLinksTurmas(turmas));
        });
    }

    function filtrarTurmasGestao(termo) {
        termoBuscaTurma = String(termo == null ? '' : termo);
        if (estadoTurmas === 'loading' || estadoTurmas === 'error') return [];
        const filtro = normalizarTexto(termoBuscaTurma);
        const filtradas = todasTurmasAtuais.filter((turma) =>
            normalizarTexto(turma.nome_turma).includes(filtro)
        );
        renderizarTurmas(filtradas);
        return filtradas;
    }

    async function carregarTurmas(idCategoria) {
        const categoriaValida = categoriasAtuais.find((categoria) => categoria.id === String(idCategoria));
        if (!idInterclasse || !categoriaValida) return false;

        const requisicaoAtual = ++requisicaoTurmas;
        estadoTurmas = 'loading';
        mostrarMensagemLista('Carregando turmas...');
        const parametros = new URLSearchParams({
            id_categoria: categoriaValida.id,
            id_interclasse: idInterclasse,
        });

        try {
            const response = await fetch(`${API}turmas?${parametros.toString()}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const turmas = await response.json();
            if (!Array.isArray(turmas)) throw new Error('Resposta inválida ao carregar turmas.');
            if (requisicaoAtual !== requisicaoTurmas) return false;
            todasTurmasAtuais = turmas;
            estadoTurmas = 'ready';
            filtrarTurmasGestao(termoBuscaTurma);
            return true;
        } catch (error) {
            if (requisicaoAtual !== requisicaoTurmas) return false;
            console.error('Erro ao carregar turmas:', error);
            estadoTurmas = 'error';
            mostrarMensagemLista('Não foi possível carregar as turmas.', 'error', 'turmas');
            return false;
        }
    }

    async function selecionarCategoria(idCategoria) {
        const categoria = categoriasAtuais.find((item) => item.id === positiveId(idCategoria));
        if (!categoria) {
            requisicaoTurmas += 1;
            categoriaSelecionadaId = null;
            todasTurmasAtuais = [];
            estadoTurmas = 'idle';
            atualizarEstadoCategorias();
            mostrarInstrucao(instrucaoSemCategoria());
            mostrarMensagemLista(instrucaoSemCategoria());
            return false;
        }

        categoriaSelecionadaId = categoria.id;
        atualizarEstadoCategorias();
        mostrarInstrucao(`Categoria selecionada: ${categoria.nome}.`);
        return carregarTurmas(categoria.id);
    }

    async function carregarCategorias() {
        if (!idInterclasse) return;

        estadoCategorias = 'loading';
        if (seletorCategoriaMobile) seletorCategoriaMobile.disabled = true;
        botoesAdicionarTurma.forEach((botao) => { botao.disabled = true; });
        mostrarInstrucao('Carregando categorias...');
        if (categoriasAtuais.length === 0 && listaCategoriasDesktop) {
            const mensagem = document.createElement('p');
            mensagem.className = 'text-muted p-3 text-center mb-0';
            mensagem.textContent = 'Carregando categorias...';
            mensagem.setAttribute('role', 'status');
            mensagem.setAttribute('aria-live', 'polite');
            listaCategoriasDesktop.replaceChildren(mensagem);
        }
        try {
            const parametros = new URLSearchParams({ id_interclasse: idInterclasse });
            const response = await fetch(`${API}categorias?${parametros.toString()}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const resultado = await response.json();
            if (!Array.isArray(resultado)) throw new Error('Resposta inválida ao carregar categorias.');
            const categorias = resultado.filter((categoria) => positiveId(categoria?.id_categoria) !== null);
            const categoriaAnterior = categoriaSelecionadaId;
            construirListaCategorias(categorias);
            estadoCategorias = 'ready';
            atualizarEstadoCategorias();

            const categoriaDaUrl = categoriasAtuais.find((categoria) => categoria.id === idCategoriaUrl);
            const categoriaAnteriorValida = categoriasAtuais.find((categoria) => categoria.id === categoriaAnterior);
            const categoriaDaPreferencia = categoriaAnteriorValida || categoriaDaUrl;
            if (categoriaDaPreferencia) {
                await selecionarCategoria(categoriaDaPreferencia.id);
                return true;
            }

            categoriaSelecionadaId = null;
            todasTurmasAtuais = [];
            estadoTurmas = 'idle';
            atualizarEstadoCategorias();
            mostrarInstrucao(instrucaoSemCategoria());
            mostrarMensagemLista(instrucaoSemCategoria());
            return true;
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
            estadoCategorias = 'error';
            if (categoriasAtuais.length === 0 && listaCategoriasDesktop) {
                const mensagem = document.createElement('p');
                mensagem.className = 'text-danger p-3 text-center mb-0';
                mensagem.textContent = 'Não foi possível carregar as categorias.';
                mensagem.setAttribute('role', 'alert');
                mensagem.setAttribute('aria-live', 'assertive');
                mensagem.append(document.createElement('br'), criarBotaoRetry('categorias'));
                listaCategoriasDesktop.replaceChildren(mensagem);
            }
            if (categoriasAtuais.length === 0 && seletorCategoriaMobile) {
                seletorCategoriaMobile.replaceChildren(new Option('Categorias indisponíveis', ''));
            }
            atualizarEstadoCategorias();
            mostrarInstrucao('Não foi possível carregar as categorias.', {
                erro: true,
                retryAction: 'categorias',
            });
            if (categoriasAtuais.length === 0) {
                mostrarMensagemLista('Não foi possível carregar as categorias.', 'error');
            }
            return false;
        }
    }

    function sincronizarBusca(valor) {
        const texto = String(valor == null ? '' : valor);
        if (buscaDesktop && buscaDesktop.value !== texto) buscaDesktop.value = texto;
        if (buscaMobile && buscaMobile.value !== texto) buscaMobile.value = texto;
        filtrarTurmasGestao(texto);
    }

    if (seletorCategoriaMobile) {
        pageScope.listen(seletorCategoriaMobile, 'change', (event) => {
            selecionarCategoria(event.target.value);
        });
    }
    if (listaCategoriasDesktop) {
        pageScope.listen(listaCategoriasDesktop, 'click', (event) => {
            const alvo = event.target;
            const botao = alvo && typeof alvo.closest === 'function'
                ? alvo.closest('[data-id-categoria]')
                : null;
            if (!botao || !listaCategoriasDesktop.contains(botao)) return;
            selecionarCategoria(botao.dataset.idCategoria);
        });
    }
    if (buscaDesktop) {
        pageScope.listen(buscaDesktop, 'input', (event) => sincronizarBusca(event.target.value));
    }
    if (buscaMobile) {
        pageScope.listen(buscaMobile, 'input', (event) => sincronizarBusca(event.target.value));
    }
    pageScope.listen(document, 'click', async (event) => {
        const alvo = event.target;
        const botao = alvo && typeof alvo.closest === 'function'
            ? alvo.closest('[data-sgi-retry]')
            : null;
        if (!botao) return;
        event.preventDefault();
        if (botao.dataset.sgiRetry === 'categorias') {
            const carregado = await carregarCategorias();
            const foco = seletorCategoriaMobile && !seletorCategoriaMobile.disabled
                ? seletorCategoriaMobile
                : listaCategoriasDesktop?.querySelector('[data-id-categoria][aria-pressed="true"]')
                    || listaCategoriasDesktop?.querySelector('[data-id-categoria]');
            if (foco) foco.focus();
            else if (!carregado) instrucaoCategoriaMobile?.focus();
        } else if (botao.dataset.sgiRetry === 'turmas' && categoriaSelecionadaId) {
            const lista = botao.closest('#listaTurmasMobile') ? listaTurmasMobile : listaTurmasDesktop;
            const carregado = await carregarTurmas(categoriaSelecionadaId);
            if (!carregado) {
                lista?.querySelector('[data-sgi-retry="turmas"]')?.focus();
                return;
            }
            const foco = lista?.querySelector('a') || lista?.querySelector('[role="status"]');
            foco?.focus();
        }
    });

    const formTurma = getEl('formTurma');
    if (formTurma) {
        pageScope.listen(formTurma, 'submit', async (event) => {
            event.preventDefault();

            const categoria = categoriasAtuais.find((item) => item.id === categoriaSelecionadaId);
            if (!categoria || !idInterclasse) {
                mostrarInstrucao(instrucaoSemCategoria());
                if (seletorCategoriaMobile) seletorCategoriaMobile.focus();
                return;
            }

            const botaoSalvar = getEl('btnSalvarTurma');
            const inputNome = getEl('inputNomeTurma');
            const inputNomeFantasia = getEl('inputNomeFantasiaTurma');
            const inputTurno = getEl('inputTurnoTurma');
            const mensagem = getEl('msgTurma');
            const mapaTurno = {
                'manhã': 'manha',
                'manha': 'manha',
                'tarde': 'tarde',
                'noite': 'noite',
                'integral': 'integral',
            };
            const turnoRaw = (inputTurno.value || '').trim().toLowerCase();
            const turnoNormalizado = mapaTurno[turnoRaw] || (turnoRaw || null);
            const dadosTurma = {
                interclasses_id_interclasse: Number(idInterclasse),
                categorias_id_categoria: Number(categoria.id),
                nome_turma: inputNome.value.trim(),
                nome_fantasia_turma: inputNomeFantasia.value.trim(),
                turno_turma: turnoNormalizado,
                status_turma: '1',
            };

            try {
                botaoSalvar.disabled = true;
                mensagem.textContent = 'Salvando turma...';
                const response = await fetch(`${API}turmas`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dadosTurma),
                });
                const resultado = await response.json();
                if (response.ok && resultado.success) {
                    todasTurmasAtuais = [...todasTurmasAtuais, {
                        id_turma: resultado.id_turma,
                        nome_turma: inputNome.value.trim(),
                        nome_fantasia_turma: inputNomeFantasia.value.trim(),
                        turno_turma: turnoNormalizado,
                    }];
                    filtrarTurmasGestao(termoBuscaTurma);
                    mensagem.textContent = 'Turma adicionada.';
                    inputNome.value = '';
                    inputNomeFantasia.value = '';
                    inputTurno.value = '';
                    await carregarTurmas(categoria.id);
                    window.setTimeout(() => {
                        const modalEl = getEl('modalCriarTurma');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        mensagem.textContent = '';
                    }, 1500);
                } else {
                    SGI.alert(`Erro ao criar turma: ${resultado.message || 'Erro desconhecido.'}`);
                    mensagem.textContent = '';
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                SGI.alert('Erro de conexão.');
                mensagem.textContent = '';
            } finally {
                botaoSalvar.disabled = false;
            }
        });
    }

    if (idInterclasse) window.SGIPage.ready(carregarCategorias);

    return {
        esc,
        getEl,
        carregarCategorias,
        carregarTurmas,
        renderizarTurmas,
        filtrarTurmasGestao,
        selecionarCategoria,
    };
});
