window.SGIPage.mount("competicoes/jogos", function (pageConfig, pageScope) {

    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');
    const API_BASE = String(window.SGI_API_BASE || `${window.SGI_BASE_PATH || ''}/api/v1/`).replace(/\/?$/, '/');
    const container = document.getElementById('listaJogos');
    let carregando = false;

    function renderizarCarregamento() {
        container.innerHTML = '<div class="col-12 text-center text-body-secondary py-5"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>Carregando jogos...</span></div>';
        container.setAttribute('aria-busy', 'true');
    }

    function renderizarErro() {
        container.innerHTML = '<div class="col-12 text-center py-5" role="alert"><i class="bi bi-exclamation-triangle display-5 d-block mb-3 text-danger" aria-hidden="true"></i><p class="mb-3">Não foi possível carregar os jogos deste interclasse.</p><button type="button" class="btn btn-outline-primary" data-jogos-retry>Tentar novamente</button></div>';
        container.setAttribute('aria-busy', 'false');
    }

    function focarResultado() {
        const link = container.querySelector('[data-jogo-link]');
        const retry = container.querySelector('[data-jogos-retry]');
        const destino = link || retry;
        if (destino) destino.focus({ preventScroll: true });
    }

    async function carregarDados() {
        if (carregando) return;
        if (!container) return;

        if (!idInterclasse) {
            container.innerHTML = '<div class="col-12 text-center text-body-secondary py-5" role="status" tabindex="-1">Nenhum interclasse informado.</div>';
            container.setAttribute('aria-busy', 'false');
            return;
        }

        const preservarFoco = container.contains(document.activeElement);
        carregando = true;
        renderizarCarregamento();

        try {
            // O título é secundário: se o snapshot de interclasse não estiver
            // mais em cache offline, a lista de jogos (por URL/localGet) não
            // pode deixar de renderizar por causa disso.
            const [dadosInter, resJogos] = await Promise.all([
                window.SGIInterclasse.getInterclasseById(idInterclasse).catch(() => null),
                fetch(`${API_BASE}jogos?x=1&id_interclasse=${encodeURIComponent(idInterclasse)}`)
            ]);

            if (dadosInter) {
                window.SGIInterclasse.updatePageTitle(dadosInter.nome_interclasse);
            }

            const jogos = await resJogos.json();
            if (!resJogos.ok || !Array.isArray(jogos)) {
                throw new Error('A resposta da lista de jogos não foi válida.');
            }
            const lista = jogos;

            if (lista.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-body-secondary py-5" role="status" tabindex="-1"><i class="bi bi-calendar-x display-5 d-block mb-3 text-body-tertiary" aria-hidden="true"></i>Nenhum jogo encontrado para este interclasse.</div>';
                container.setAttribute('aria-busy', 'false');
                if (preservarFoco) container.querySelector('[role="status"]')?.focus({ preventScroll: true });
                return;
            }

            container.innerHTML = lista.map(j => {
                const escapar = window.SGIHtml?.escape || ((valor) => String(valor == null ? '' : valor));
                const status = j.status_jogo || 'Agendado';
                const statusClass = status === 'Concluido' ? 'success' :
                                    status === 'Iniciado' || status === 'Pausado' ? 'warning' :
                                    'secondary';
                const data = j.data_jogo ? new Date(j.data_jogo + 'T' + (j.inicio_jogo || '00:00')).toLocaleDateString('pt-BR') : '---';
                const horario = j.inicio_jogo ? j.inicio_jogo.slice(0, 5) : '---';
                const equipes = escapar(j.equipes_nomes || '---');
                const idJogo = encodeURIComponent(String(j.id_jogo ?? ''));
                const urlPlacar = `${String(pageConfig.placarUrl || `${window.SGI_BASE_PATH || ''}/jogos/placar`).replace(/\/$/, '')}?id_jogo=${idJogo}`;
                return `
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="${escapar(urlPlacar)}" class="text-decoration-none text-body" data-jogo-link>
                            <div class="card border-0 shadow-sm p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fw-bold">${escapar(j.nome_modalidade || '---')}</span>
                                    <span class="badge rounded-pill text-bg-${statusClass}">${escapar(status)}</span>
                                </div>
                                <p class="mb-1 small text-muted">${equipes}</p>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>${escapar(data)}</span>
                                    <span><i class="bi bi-clock me-1" aria-hidden="true"></i>${escapar(horario)}</span>
                                </div>
                                ${j.nome_local ? `<p class="mt-1 mb-0 small text-muted"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>${escapar(j.nome_local)}</p>` : ''}
                            </div>
                        </a>
                    </div>
                `;
            }).join('');
            container.setAttribute('aria-busy', 'false');
            if (preservarFoco) focarResultado();

        } catch (e) {
            console.error(e);
            renderizarErro();
            if (preservarFoco) focarResultado();
        } finally {
            carregando = false;
        }
    }

    pageScope.listen(container, 'click', (event) => {
        const retry = event.target.closest('[data-jogos-retry]');
        if (!retry) return;
        event.preventDefault();
        carregarDados();
    });

    window.SGIPage.ready( carregarDados);

return {carregarDados};
});
