window.SGIPage.mount("eventos/dashboard", function (pageConfig, pageScope) {

(async function() {
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    if (pageConfig.value0) {
    // Mesário só pode operar na edição ativa no momento.
    const ativoAtual = await window.SGIInterclasse.getActiveInterclasse();
    const idAtivoMesario = ativoAtual ? String(ativoAtual.id_interclasse) : null;

    const exibirSemAtivo = () => {
        ['subtituloMobile', 'subtituloDesktop'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = 'Nenhuma edição de interclasse está ativa no momento.';
        });
    };

    if (!idAtivoMesario) {
        exibirSemAtivo();
        return;
    }

    if (!idInterclasse || String(idInterclasse) !== idAtivoMesario) {
        try {
            window.history.replaceState(null, '', './dashboard.php?id=' + idAtivoMesario);
        } catch (_) {}
    }
    idInterclasse = idAtivoMesario;

    const linkAgenda = document.getElementById('linkAgenda');
    if (linkAgenda) linkAgenda.href = 'edicao_agenda.php?id=' + idAtivoMesario;
    const linkChaveamentos = document.getElementById('linkChaveamentos');
    if (linkChaveamentos) linkChaveamentos.href = 'chaveamento_arvore.php?id=' + idAtivoMesario;
    const linkOcorrencias = document.getElementById('linkOcorrencias');
    if (linkOcorrencias) linkOcorrencias.href = 'ocorrencias.php?id=' + idAtivoMesario;
    } else {
    if (!idInterclasse) {
        const ativo = await window.SGIInterclasse.getActiveInterclasse();
        idInterclasse = ativo?.id_interclasse || null;
    }
    }

    if (!idInterclasse) {
        const subtituloMobile = document.getElementById('subtituloMobile');
        const subtituloDesktop = document.getElementById('subtituloDesktop');
        if (subtituloMobile) subtituloMobile.textContent = 'Nenhum interclasse selecionado.';
        if (subtituloDesktop) subtituloDesktop.textContent = 'Nenhum interclasse selecionado.';
        return;
    }

    const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
    if (dados) {
        const tituloDashboard = document.getElementById('tituloDashboard');
        if (tituloDashboard) tituloDashboard.textContent = (pageConfig.value0 ? dados.nome_interclasse : "Dashboard");
        const subtituloMobile = document.getElementById('subtituloMobile');
        const subtituloDesktop = document.getElementById('subtituloDesktop');
        if (subtituloMobile) subtituloMobile.textContent = 'Selecione uma opção';
        if (subtituloDesktop) subtituloDesktop.textContent = 'Selecione uma opção';
        window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);

        if (pageConfig.value1) {
        if (String(dados.status_interclasse) !== '1') {
            const aviso = document.getElementById('avisoFinalizacaoInterclasse');
            if (aviso) {
                aviso.classList.remove('d-none');
                document.getElementById('linkConcluirInterclasse').href = `./edicao_resumo.php?id=${idInterclasse}`;
            }
        }
        }
    }

    const modoParam = "modo=view";

    if (pageConfig.value0) {
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./categorias.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkAgenda').forEach(link => { link.href = `./edicao_agenda.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkChaveamentos').forEach(link => { link.href = `./chaveamento_arvore.php?id=${idInterclasse}&${modoParam}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkOcorrencias').forEach(link => { link.href = `./ocorrencias.php?id=${idInterclasse}`; });
    }

    if (pageConfig.value2) {
    document.querySelectorAll('#linkModalidades').forEach(link => { link.href = `./modalidades.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkPontuacoes').forEach(link => { link.href = `./pontuacoes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkLocais').forEach(link => { link.href = `./edicao_locais.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkColaboradores').forEach(link => { link.href = `./colaboradores.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkArrecadacoes').forEach(link => { link.href = `./edicao_arrecadacao.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkOcorrencias').forEach(link => { link.href = `./ocorrencias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./categorias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkTurmas').forEach(link => { link.href = `./turmas.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkEquipes').forEach(link => { link.href = `./edicao_equipes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    }

    if (pageConfig.value1) {
    document.querySelectorAll('#linkModalidades').forEach(link => { link.href = `./edicao_modalidades.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkPontuacoes').forEach(link => { link.href = `./edicao_pontuacao.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkArrecadacoes').forEach(link => { link.href = `./edicao_arrecadacao.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkOcorrencias').forEach(link => { link.href = `./ocorrencias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkOcorrenciasAdmin').forEach(link => { link.href = `./ocorrencias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkOcorrenciasColab').forEach(link => { link.href = `./ocorrencias.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkCategorias').forEach(link => { link.href = `./edicao_categorias.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkLocais').forEach(link => { link.href = `./edicao_locais.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkAgenda').forEach(link => { link.href = `./edicao_agenda.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkColaboradores').forEach(link => { link.href = `./colaboradores.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkTurmas').forEach(link => { link.href = `./turmas.php?id=${idInterclasse}&modo=view`; });
    document.querySelectorAll('#linkEquipes').forEach(link => { link.href = `./edicao_equipes.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkChaveamento').forEach(link => { link.href = `./chaveamento_arvore.php?id=${idInterclasse}`; });
    document.querySelectorAll('#linkRanking').forEach(link => { link.href = `./ranking.php?id=${idInterclasse}`; });
    }
})();

return {};
});
