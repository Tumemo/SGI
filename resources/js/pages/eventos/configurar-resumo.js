window.SGIPage.mount("eventos/configurar-resumo", function (pageConfig, pageScope) {
    const APP_BASE = String(window.SGI_BASE_PATH || '').replace(/\/+$/, '');
    const API_BASE = String(window.SGI_API_BASE || `${APP_BASE}/api/v1/`).replace(/\/?$/, '/');
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') === 'create' ? 'create' : 'view';
    let edicaoSelecionada = null;
    let finalizacaoEmAndamento = false;

    function appUrl(path, parametros = {}) {
        const query = new URLSearchParams();
        Object.entries(parametros).forEach(([chave, valor]) => {
            if (valor !== null && valor !== undefined && String(valor) !== '') query.set(chave, String(valor));
        });
        const base = `${APP_BASE}/${String(path).replace(/^\/+/, '')}`;
        return query.size ? `${base}?${query.toString()}` : base;
    }

    function parametrosEdicao(comModo = true) {
        return { id: idInterclasse, ...(comModo ? { modo } : {}) };
    }

    async function resolverInterclasseAtual() {
        if (idInterclasse) return window.SGIInterclasse.getInterclasseById(idInterclasse);
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        if (ativo) idInterclasse = ativo.id_interclasse;
        return ativo;
    }

    function definirTexto(ids, texto) {
        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.textContent = texto;
        });
    }

    function configurarEtapas() {
        const etapas = {
            categorias: 'edicoes/categorias',
            turmas: 'turmas',
            modalidades: 'edicoes/modalidades',
            pontuacao: 'edicoes/pontuacao',
        };
        document.querySelectorAll('[data-sgi-etapa]').forEach((link) => {
            const rota = etapas[link.dataset.sgiEtapa];
            if (rota) link.href = appUrl(rota, parametrosEdicao());
        });
    }

    function configurarAcaoFinalizacao() {
        const ativa = String(edicaoSelecionada?.status_interclasse) === '1';
        const href = appUrl('painel', { id: idInterclasse });
        const acao = ativa ? 'concluir' : 'ativar';
        ['btnAcaoFinalizacaoResumoMobile', 'btnAcaoFinalizacaoResumoDesktop'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.href = href;
            el.dataset.sgiAcaoFinalizacao = acao;
            el.textContent = ativa ? 'Concluir configuração' : 'Ativar edição e abrir painel';
            el.removeAttribute('aria-disabled');
        });
    }

    async function configurarLinks() {
        edicaoSelecionada = await resolverInterclasseAtual();
        if (!idInterclasse || !edicaoSelecionada) return;

        const id = String(idInterclasse);
        const parametros = parametrosEdicao();
        const nome = edicaoSelecionada.nome_interclasse || 'Interclasse';
        const ano = window.SGIInterclasse.toYear(edicaoSelecionada.ano_interclasse) || 'Ano não informado';
        const ativa = String(edicaoSelecionada.status_interclasse) === '1';
        const status = ativa ? 'Ativa' : 'Inativa';
        const statusClasse = ativa ? 'badge text-bg-success' : 'badge text-bg-secondary';

        ['btnVoltarMobile', 'btnVoltarResumoTopo'].forEach((elementId) => {
            const el = document.getElementById(elementId);
            if (el) el.href = appUrl('edicoes/pontuacao', parametros);
        });

        const destinos = {
            linkEditarModalidadesMobile: 'edicoes/modalidades',
            linkEditarModalidadesDesktop: 'edicoes/modalidades',
            linkEditarRegulamentosMobile: 'edicoes/pontuacao',
            linkEditarRegulamentosDesktop: 'edicoes/pontuacao',
            linkEditarCategoriasMobile: 'edicoes/categorias',
            linkEditarCategoriasDesktop: 'edicoes/categorias',
            linkEditarCategoriasAcaoMobile: 'edicoes/categorias',
            linkEditarCategoriasAcaoDesktop: 'edicoes/categorias',
            linkEditarTurmasMobile: 'turmas',
            linkEditarTurmasDesktop: 'turmas',
        };
        Object.entries(destinos).forEach(([elementId, rota]) => {
            const el = document.getElementById(elementId);
            if (el) el.href = appUrl(rota, parametros);
        });

        definirTexto(['nomeInterclasseResumo', 'nomeInterclasseResumoMob'], nome);
        definirTexto(['anoInterclasseResumo', 'anoInterclasseResumoMob'], `Ano ${ano}`);
        ['statusInterclasseResumo', 'statusInterclasseResumoMob'].forEach((elementId) => {
            const el = document.getElementById(elementId);
            if (!el) return;
            el.textContent = status;
            el.className = statusClasse;
        });
        window.SGIInterclasse.updatePageTitle(nome);
        configurarEtapas();
        configurarAcaoFinalizacao();
    }

    function resumoPontuacao(interclasse) {
        const primeiro = Number(interclasse?.ponto_1_lugar);
        const segundo = Number(interclasse?.ponto_2_lugar);
        const terceiro = Number(interclasse?.ponto_3_lugar);
        const arrecadacao = Number(interclasse?.valor_item_arrecadacao);
        if (![primeiro, segundo, terceiro, arrecadacao].every(Number.isFinite)) return '(Pontuação não configurada)';
        return `1º: ${primeiro} · 2º: ${segundo} · 3º: ${terceiro} · arrecadação/kg: ${arrecadacao}`;
    }

    const areasResumo = {
        modalidades: {
            ids: ['resumoModalidadesMobile', 'resumoModalidadesDesktop'],
            cards: ['linkEditarModalidadesMobile', 'linkEditarModalidadesDesktop'],
        },
        categorias: {
            ids: ['resumoCategoriasMobile', 'resumoCategoriasDesktop'],
            cards: ['linkEditarCategoriasMobile', 'linkEditarCategoriasDesktop'],
        },
        turmas: {
            ids: ['resumoTurmasMobile', 'resumoTurmasDesktop'],
            cards: ['linkEditarTurmasMobile', 'linkEditarTurmasDesktop'],
        },
    };

    function atualizarResumoLista(chave, texto, estado = 'ready') {
        const area = areasResumo[chave];
        if (!area) return;

        area.ids.forEach((id, indice) => {
            const elemento = document.getElementById(id);
            const card = document.getElementById(area.cards[indice]);
            const variante = indice === 0 ? 'mobile' : 'desktop';
            const seletorBotao = `[data-sgi-resumo-retry="${chave}"][data-sgi-variante="${variante}"]`;
            document.querySelectorAll(seletorBotao).forEach((botao) => botao.remove());
            if (!elemento) return;

            elemento.textContent = texto;
            elemento.classList.toggle('text-danger', estado === 'error');
            elemento.classList.toggle('text-secondary', estado !== 'error');
            elemento.classList.toggle('text-truncate', estado !== 'error');
            if (estado === 'loading' || estado === 'error') {
                elemento.setAttribute('role', 'status');
                elemento.setAttribute('aria-live', 'polite');
            } else {
                elemento.removeAttribute('role');
                elemento.removeAttribute('aria-live');
            }

            if (estado === 'error' && card) {
                const botao = document.createElement('button');
                botao.type = 'button';
                botao.className = variante === 'mobile'
                    ? 'btn btn-link btn-sm p-0 mt-1 align-self-start d-md-none'
                    : 'btn btn-link btn-sm p-0 mt-1 align-self-start d-none d-md-inline-flex';
                botao.dataset.sgiResumoRetry = chave;
                botao.dataset.sgiVariante = variante;
                botao.textContent = 'Tentar novamente';
                card.after(botao);
            }
        });
    }

    async function carregarResumoLista(chave, endpoint, formatar, vazio) {
        if (!idInterclasse || !edicaoSelecionada) {
            atualizarResumoLista(chave, '(Nenhuma edição selecionada)', 'ready');
            return false;
        }

        atualizarResumoLista(chave, '(Carregando...)', 'loading');
        try {
            const resposta = await fetch(`${API_BASE}${endpoint}?id_interclasse=${encodeURIComponent(idInterclasse)}`);
            if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
            const dados = await resposta.json();
            if (!Array.isArray(dados)) throw new Error('Resposta inválida ao carregar o resumo.');
            atualizarResumoLista(chave, dados.length > 0 ? formatar(dados) : vazio, 'ready');
            return true;
        } catch (error) {
            console.error(`Erro ao carregar ${chave} do resumo:`, error);
            const mensagens = {
                modalidades: '(Erro ao carregar modalidades.)',
                categorias: '(Erro ao carregar categorias.)',
                turmas: '(Erro ao carregar turmas.)',
            };
            atualizarResumoLista(chave, mensagens[chave], 'error');
            return false;
        }
    }

    function carregarResumoModalidades() {
        return carregarResumoLista(
            'modalidades',
            'modalidades',
            (dados) => `(${dados.map((modalidade) => modalidade?.nome_modalidade).filter(Boolean).join(', ')})`,
            '(Nenhuma modalidade cadastrada)',
        );
    }

    function carregarResumoCategorias() {
        return carregarResumoLista(
            'categorias',
            'categorias',
            (dados) => `${dados.length} categoria(s) cadastrada(s)`,
            '(Nenhuma categoria cadastrada)',
        );
    }

    function carregarResumoTurmas() {
        return carregarResumoLista(
            'turmas',
            'turmas',
            (dados) => `${dados.length} turma(s) cadastrada(s)`,
            '(Nenhuma turma cadastrada)',
        );
    }

    async function carregarResumos() {
        if (!idInterclasse || !edicaoSelecionada) {
            const alerta = '(Nenhuma edição selecionada)';
            definirTexto(['resumoRegulamentosMobile', 'resumoRegulamentosDesktop'], alerta);
            Object.keys(areasResumo).forEach((chave) => atualizarResumoLista(chave, alerta));
            return;
        }

        definirTexto(['resumoRegulamentosMobile', 'resumoRegulamentosDesktop'], resumoPontuacao(edicaoSelecionada));
        await Promise.all([
            carregarResumoModalidades(),
            carregarResumoCategorias(),
            carregarResumoTurmas(),
        ]);
    }

    async function ativarEdicao() {
        if (finalizacaoEmAndamento || !idInterclasse || String(edicaoSelecionada?.status_interclasse) === '1') return;
        finalizacaoEmAndamento = true;
        const acoes = ['btnAcaoFinalizacaoResumoMobile', 'btnAcaoFinalizacaoResumoDesktop']
            .map((id) => document.getElementById(id)).filter(Boolean);
        acoes.forEach((el) => {
            el.classList.add('disabled');
            el.setAttribute('aria-disabled', 'true');
            el.setAttribute('aria-busy', 'true');
        });

        try {
            const ativaAtual = await window.SGIInterclasse.getActiveInterclasse();
            const mensagem = ativaAtual && String(ativaAtual.id_interclasse) !== String(idInterclasse)
                ? `A edição ativa atual (${ativaAtual.nome_interclasse || 'edição selecionada'}) será desativada para manter apenas uma edição ativa.`
                : 'Esta edição passará a ser a edição ativa do SGI.';
            const confirmado = await window.SGI.confirm({
                titulo: 'Ativar edição?',
                mensagem,
                textoConfirmar: 'Ativar edição',
            });
            if (!confirmado) return;

            const body = new FormData();
            body.append('status_interclasse', '1');
            const response = await fetch(`${API_BASE}edicoes?id=${encodeURIComponent(idInterclasse)}`, {
                method: 'POST',
                body,
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || typeof data !== 'object' || Array.isArray(data) || data.success !== true) {
                const mensagemErro = data && typeof data.message === 'string' && data.message.trim()
                    ? data.message
                    : `A resposta do servidor não confirmou a ativação (HTTP ${response.status}).`;
                throw new Error(mensagemErro);
            }

            await window.SGIInterclasse.refreshNavigation();
            window.location.href = appUrl('painel', { id: idInterclasse });
        } catch (error) {
            await window.SGI.alert({
                titulo: 'Não foi possível ativar a edição',
                mensagem: error?.message || 'A ativação não foi confirmada. Tente novamente.',
                tipo: 'error',
            });
        } finally {
            finalizacaoEmAndamento = false;
            acoes.forEach((el) => {
                el.classList.remove('disabled');
                el.removeAttribute('aria-disabled');
                el.removeAttribute('aria-busy');
            });
        }
    }

    window.SGIPage.ready(async () => {
        await configurarLinks();
        await carregarResumos();
    });

    pageScope.listen(document, 'click', async (event) => {
        const alvo = event.target;
        const botao = alvo && typeof alvo.closest === 'function'
            ? alvo.closest('[data-sgi-resumo-retry]')
            : null;
        if (!botao) return;
        event.preventDefault();
        const recarregar = {
            modalidades: carregarResumoModalidades,
            categorias: carregarResumoCategorias,
            turmas: carregarResumoTurmas,
        }[botao.dataset.sgiResumoRetry];
        if (!recarregar) return;

        const chave = botao.dataset.sgiResumoRetry;
        const variante = botao.dataset.sgiVariante;
        const carregado = await recarregar();
        if (!carregado) {
            document.querySelector(`[data-sgi-resumo-retry="${chave}"][data-sgi-variante="${variante}"]`)?.focus();
            return;
        }
        const indice = variante === 'mobile' ? 0 : 1;
        const destinoFoco = document.getElementById(areasResumo[chave].ids[indice]);
        if (destinoFoco) {
            destinoFoco.tabIndex = -1;
            destinoFoco.focus();
        }
    });

    ['btnAcaoFinalizacaoResumoMobile', 'btnAcaoFinalizacaoResumoDesktop'].forEach((id) => {
        pageScope.listen(document.getElementById(id), 'click', (event) => {
            const el = event.currentTarget;
            if (el.dataset.sgiAcaoFinalizacao !== 'ativar') return;
            event.preventDefault();
            void ativarEdicao();
        });
    });

    return {
        resolverInterclasseAtual,
        configurarLinks,
        carregarResumos,
        carregarResumoModalidades,
        carregarResumoCategorias,
        carregarResumoTurmas,
        resumoPontuacao,
        ativarEdicao,
    };
});
