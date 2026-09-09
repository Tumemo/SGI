window.SGIPage.mount("eventos/configurar-resumo", function (pageConfig, pageScope) {

    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    async function resolverInterclasseAtual() {
        if (idInterclasse) {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
            return dados;
        }
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        if (ativo) {
            idInterclasse = ativo.id_interclasse;
        }
        return ativo;
    }

    // Transferindo IDs para os links
    async function configurarLinks() {
        const interclasse = await resolverInterclasseAtual();
        if (!idInterclasse) return;
        const nome = interclasse?.nome_interclasse || 'Interclasse';

        ['btnVoltarMobile', 'btnVoltarResumoTopo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `/edicoes/pontuacao?id=${idInterclasse}&modo=create`;
        });
        document.getElementById('btnVoltarDesktop').href = `/edicoes/pontuacao?id=${idInterclasse}&modo=create`;
        const spanDesk = document.getElementById('nomeInterclasseResumoDesk');
        if (spanDesk) spanDesk.innerText = nome;

        document.getElementById('linkEditarModalidadesMobile').href = `/edicoes/modalidades?id=${idInterclasse}&modo=create`;
        document.getElementById('linkEditarModalidadesDesktop').href = `/edicoes/modalidades?id=${idInterclasse}&modo=create`;

        document.getElementById('linkEditarRegulamentosMobile').href = `/edicoes/pontuacao?id=${idInterclasse}&modo=create`;
        document.getElementById('linkEditarRegulamentosDesktop').href = `/edicoes/pontuacao?id=${idInterclasse}&modo=create`;
        document.getElementById('linkEditarCategoriasMobile').href = `/edicoes/categorias?id=${idInterclasse}&modo=create`;
        document.getElementById('linkEditarCategoriasDesktop').href = `/edicoes/categorias?id=${idInterclasse}&modo=create`;
        window.SGIInterclasse.getActiveInterclasse().then((ativo) => {
            const idTurmas = ativo?.id_interclasse || idInterclasse;
            document.getElementById('linkEditarTurmasMobile').href = `/turmas?id=${idTurmas}`;
            document.getElementById('linkEditarTurmasDesktop').href = `/turmas?id=${idTurmas}`;
        }).catch(() => {
            document.getElementById('linkEditarTurmasMobile').href = `/turmas?id=${idInterclasse}`;
            document.getElementById('linkEditarTurmasDesktop').href = `/turmas?id=${idInterclasse}`;
        });
        document.getElementById('btnCriarInterclasseFinal').href = `/painel?id=${idInterclasse}`;
        ['nomeInterclasseResumo', 'nomeInterclasseResumoMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = nome;
        });
        window.SGIInterclasse.updatePageTitle(nome);
    }

    // Carregar Resumos
    async function carregarResumos() {
        if (!idInterclasse) {
            const alerta = '(Nenhum interclasse ativo encontrado)';
            document.getElementById('resumoModalidadesMobile').innerText = alerta;
            document.getElementById('resumoModalidadesDesktop').innerText = alerta;
            return;
        }

        // --- Tentativa de carregar Modalidades ---
        try {
            const resMod = await fetch(`/api/v1/modalidades?id_interclasse=${idInterclasse}`);
            const dataMod = await resMod.json();

            let textoModalidades = "(Nenhuma modalidade cadastrada)";

            if (Array.isArray(dataMod) && dataMod.length > 0) {
                const nomes = dataMod.map(m => m.nome_modalidade).join(', ');
                textoModalidades = `(${nomes})`;
            }

            document.getElementById('resumoModalidadesMobile').innerText = textoModalidades;
            document.getElementById('resumoModalidadesDesktop').innerText = textoModalidades;
        } catch (error) {
            // Em caso de erro, mostramos diretamente que não há nada
            const textoPadrao = "(Nenhuma modalidade cadastrada)";
            document.getElementById('resumoModalidadesMobile').innerText = textoPadrao;
            document.getElementById('resumoModalidadesDesktop').innerText = textoPadrao;
        }

        // --- Tentativa de carregar Regulamentos ---
        try {
            // A API de regulamento ainda não existe neste projeto.
            // Mantemos um resumo estático para não quebrar o fluxo visual.
            let textoRegulamento = "(Configuração de pontuação disponível na tela anterior)";

            document.getElementById('resumoRegulamentosMobile').innerText = textoRegulamento;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoRegulamento;

        } catch (error) {
            const textoPadrao = "(Configuração de pontuação disponível na tela anterior)";
            document.getElementById('resumoRegulamentosMobile').innerText = textoPadrao;
            document.getElementById('resumoRegulamentosDesktop').innerText = textoPadrao;
        }

        try {
            const [resCategorias, resTurmas] = await Promise.all([
                fetch(`/api/v1/categorias?id_interclasse=${idInterclasse}`),
                fetch(`/api/v1/turmas?id_interclasse=${idInterclasse}`)
            ]);
            const categorias = await resCategorias.json();
            const turmas = await resTurmas.json();
            const textoCategorias = Array.isArray(categorias) && categorias.length
                ? `${categorias.length} categoria(s) cadastrada(s)`
                : '(Nenhuma categoria cadastrada)';
            const textoTurmas = Array.isArray(turmas) && turmas.length
                ? `${turmas.length} turma(s) cadastrada(s)`
                : '(Nenhuma turma cadastrada)';
            document.getElementById('resumoCategoriasMobile').innerText = textoCategorias;
            document.getElementById('resumoCategoriasDesktop').innerText = textoCategorias;
            document.getElementById('resumoTurmasMobile').innerText = textoTurmas;
            document.getElementById('resumoTurmasDesktop').innerText = textoTurmas;
        } catch (error) {
            document.getElementById('resumoCategoriasMobile').innerText = '(Erro ao carregar categorias)';
            document.getElementById('resumoCategoriasDesktop').innerText = '(Erro ao carregar categorias)';
            document.getElementById('resumoTurmasMobile').innerText = '(Erro ao carregar turmas)';
            document.getElementById('resumoTurmasDesktop').innerText = '(Erro ao carregar turmas)';
        }
    }

    window.SGIPage.ready( async () => {
        await configurarLinks();
        await carregarResumos();
    });

    pageScope.listen(document.getElementById('btnCriarInterclasseFinal'), 'click', async (event) => {
        if (!idInterclasse) return;
        event.preventDefault();
        try {
            const lista = await window.SGIInterclasse.getInterclasses();
            for (const item of lista) {
                if (String(item.id_interclasse) !== String(idInterclasse) && String(item.status_interclasse) === '1') {
                    const body = new FormData();
                    body.append('status_interclasse', '0');
                    await fetch(`/api/v1/edicoes?id=${item.id_interclasse}`, { method: 'POST', body });
                }
            }
            const bodyAtual = new FormData();
            bodyAtual.append('status_interclasse', '1');
            await fetch(`/api/v1/edicoes?id=${idInterclasse}`, { method: 'POST', body: bodyAtual });
        } catch (error) {
            console.error(error);
        } finally {
            await window.SGIInterclasse.refreshNavigation();
            window.location.href = `/painel?id=${idInterclasse}`;
        }
    });

return {resolverInterclasseAtual, configurarLinks, carregarResumos};
});
