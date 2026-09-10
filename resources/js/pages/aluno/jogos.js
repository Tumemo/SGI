window.SGIPage.mount("aluno/jogos", function (pageConfig, pageScope) {

    const APP_BASE = window.SGI_BASE_PATH || '';

    let todosOsJogos = [];
    let filtroStatus = 'all';
    let filtroModalidade = 'all';
    let filtroCategoria = pageConfig.value2;
    let anoInterclasse = new Date().getFullYear();
    let idInterclasse = null;

    // Função de segurança para escapar HTML
    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    // Mapa de ícones por modalidade (visual)
    function iconeModalidade(nome) {
        const n = (nome || '').toLowerCase();
        if (n.includes('futebol')) return 'bi-dribbble';
        if (n.includes('volei')) return 'bi-people-fill';
        if (n.includes('queimada')) return 'bi-bullseye';
        if (n.includes('basquet')) return 'bi-basket-fill';
        if (n.includes('handebol') || n.includes('handball')) return 'bi-person-arms-up';
        if (n.includes('corrida')) return 'bi-lightning-charge-fill';
        if (n.includes('atletis')) return 'bi-lightning-charge-fill';
        if (n.includes('xadrez')) return 'bi-puzzle-fill';
        if (n.includes('nata')) return 'bi-droplet-fill';
        if (n.includes('judo') || n.includes('judô') || n.includes('luta')) return 'bi-shield-fill';
        return 'bi-trophy-fill';
    }

    // Abrevia o nome da turma para uma etiqueta curta (ex: "3º Ano Médio" -> "3º MED")
    function abreviarTurma(nomeTurma, nomeFantasia) {
        const base = nomeFantasia || nomeTurma || '';
        const s = String(base).toLowerCase();
        if (s.includes('fundamental')) return String(base).trim();
        const m = String(base).match(/(\d)(º|o|°)\.?\s*(ano\s*)?(médio|medio|méd|ef|ensino\s*fundamental|ensino\s*médio)/i);
        if (m) {
            const grau = m[1];
            const tipo = /méd|medio/.test(m[4]) ? 'MED' : 'EF';
            return `${grau}º ${tipo}`;
        }
        return base;
    }

    // Formata data ISO (YYYY-MM-DD) para dd/mm/aaaa
    function formatarData(dataStr) {
        if (!dataStr) return '—';
        const partes = String(dataStr).split('-');
        if (partes.length !== 3) return dataStr;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    // Formata horário (HH:MM:SS) para HH:MM
    function formatarHora(horaStr) {
        if (!horaStr) return '—';
        return String(horaStr).substring(0, 5);
    }

    // Função para traduzir os códigos "MM:4:0:B" / "POS:3:0:N" para nomes reais
    function traduzirNomeJogo(codigo) {
        if (!codigo || typeof codigo !== 'string') return 'Partida';

        if (codigo.startsWith('POS:')) {
            const partes = codigo.split(':');
            const posicao = partes[1] || '3';
            return `Disputa de ${posicao}º lugar`;
        }

        // Se não for um código automático do Mata-Mata, exibe como está no banco
        if (!codigo.startsWith('MM:')) return codigo;

        const partes = codigo.split(':');
        const fase = partes[1];
        const indexJogo = parseInt(partes[2] || '0') + 1;

        let nomeFase = 'Eliminatórias';

        if (fase === '16') nomeFase = 'Oitavas de Final';
        else if (fase === '8') nomeFase = 'Quartas de Final';
        else if (fase === '4') nomeFase = 'Semifinal';
        else if (fase === '2') nomeFase = 'Final';

        return `${nomeFase} (Jogo ${indexJogo})`;
    }

    // Mapa de badge de status (EM ANDAMENTO / AGUARDANDO / FINALIZADO)
    function badgeStatus(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'iniciado') {
            return {
                classe: 'text-bg-primary',
                texto: 'Em Andamento',
                dot: true
            };
        }
        if (s === 'concluido' || s === 'finalizado') {
            return {
                classe: 'text-bg-success',
                texto: 'Finalizado',
                dot: false
            };
        }
        return {
            classe: 'text-bg-secondary',
            texto: s === 'pausado' ? 'Pausado' : 'Aguardando',
            dot: false
        };
    }

    async function inicializarJogos() {
        const container = document.getElementById('listaJogos');

        try {
            // 1. Descobrir o Interclasse Ativo
            const resInter = await fetch('/api/v1/edicoes?regulamento=true');
            const dataInter = await resInter.json();
            const listaInter = Array.isArray(dataInter) ? dataInter : [dataInter];
            const ativo = listaInter.find(i => String(i.status_interclasse) === '1');

            if (!ativo) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nenhuma competição ativa no momento.</div>';
                return;
            }

            idInterclasse = ativo.id_interclasse;
            if (ativo.ano_interclasse) {
                const anoMatch = String(ativo.ano_interclasse).match(/^\d{4}/);
                if (anoMatch) anoInterclasse = parseInt(anoMatch[0], 10);
            }

            // 2. Buscar as partidas da API (agora com data, horário, local e modalidade)
            const resJogos = await fetch(`/api/v1/partidas?id_interclasse=${idInterclasse}`);

            if (!resJogos.ok) throw new Error('Erro ao buscar partidas');

            const rawData = await resJogos.json();

            // 3. AGRUPAR OS DADOS: A API retorna uma linha por time, então agrupamos pelo "id_jogo"
            const jogosAgrupados = {};

            rawData.forEach(row => {
                if (!jogosAgrupados[row.id_jogo]) {
                    jogosAgrupados[row.id_jogo] = {
                        id_jogo: row.id_jogo,
                        nome_jogo: traduzirNomeJogo(row.nome_jogo),
                        nome_jogo_raw: row.nome_jogo,
                        status_jogo: row.status_jogo,
                        nome_modalidade: row.nome_modalidade,
                        id_modalidade: row.modalidades_id_modalidade,
                        id_categoria: row.categorias_id_categoria,
                        nome_categoria: row.nome_categoria,
                        data_jogo: row.data_jogo,
                        inicio_jogo: row.inicio_jogo,
                        termino_jogo: row.termino_jogo,
                        nome_local: row.nome_local,
                        equipes: []
                    };
                }
                jogosAgrupados[row.id_jogo].equipes.push({
                    nome: row.nome_fantasia_turma || row.nome_turma || 'Time Desconhecido',
                    tag: abreviarTurma(row.nome_turma, row.nome_fantasia_turma),
                    placar: row.resultado_partida
                });
            });

            todosOsJogos = Object.values(jogosAgrupados);

            if (todosOsJogos.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhum jogo agendado ainda.</div>';
                return;
            }

            // Preencher os dropdowns de modalidade e categoria
            preencherFiltroModalidades();
            preencherFiltroCategorias();

            renderizarJogos();

        } catch (error) {
            console.error("Erro ao carregar jogos:", error);
            container.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    Erro ao carregar a tabela de jogos. Tente novamente mais tarde.
                </div>`;
        }
    }

    function preencherFiltroModalidades() {
        const select = document.getElementById('filtroModalidade');
        const modalidades = [];
        const vistos = new Set();

        todosOsJogos.forEach(j => {
            const chave = String(j.id_modalidade);
            if (!vistos.has(chave)) {
                vistos.add(chave);
                modalidades.push({ id: j.id_modalidade, nome: j.nome_modalidade });
            }
        });

        select.innerHTML = '<option value="all">Todas</option>' +
            modalidades.map(m => `<option value="${esc(m.id)}">${esc(m.nome)}</option>`).join('');
    }

    function preencherFiltroCategorias() {
        const select = document.getElementById('filtroCategoria');
        const categorias = [];
        const vistos = new Set();

        todosOsJogos.forEach(j => {
            const chave = String(j.id_categoria);
            if (!vistos.has(chave)) {
                vistos.add(chave);
                categorias.push({ id: j.id_categoria, nome: j.nome_categoria });
            }
        });

        select.innerHTML = '<option value="all">Todas</option>' +
            categorias.map(c => `<option value="${esc(c.id)}">${esc(c.nome)}</option>`).join('');

        if (filtroCategoria !== 'all' && vistos.has(String(filtroCategoria))) {
            select.value = String(filtroCategoria);
        } else {
            filtroCategoria = 'all';
            select.value = 'all';
        }
    }

    function renderizarJogos() {
        const container = document.getElementById('listaJogos');

        let jogosFiltrados = todosOsJogos.slice();

        if (filtroStatus === 'agendado') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.status_jogo).toLowerCase() !== 'concluido');
        } else if (filtroStatus === 'finalizado') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.status_jogo).toLowerCase() === 'concluido');
        }

        if (filtroModalidade !== 'all') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.id_modalidade) === String(filtroModalidade));
        }

        if (filtroCategoria !== 'all') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.id_categoria) === String(filtroCategoria));
        }

        if (jogosFiltrados.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-search fs-1 d-block mb-2"></i>Nenhum jogo encontrado para este filtro.</div>';
            return;
        }

        container.innerHTML = jogosFiltrados.map(jogo => {
            const status = badgeStatus(jogo.status_jogo);
            const isFinalizado = String(jogo.status_jogo).toLowerCase() === 'concluido';

            const eqA = jogo.equipes[0] || { nome: 'A Definir', tag: '??', placar: '-' };
            const eqB = jogo.equipes[1] || { nome: 'A Definir', tag: '??', placar: '-' };

            const placarA = isFinalizado ? (eqA.placar ?? '0') : '-';
            const placarB = isFinalizado ? (eqB.placar ?? '0') : '-';

            const metaInfo = `
                <span class="meta-item"><i class="bi bi-calendar3"></i>${formatarData(jogo.data_jogo)}</span>
                <span class="meta-item"><i class="bi bi-clock"></i>${formatarHora(jogo.inicio_jogo)}${jogo.termino_jogo ? '–' + formatarHora(jogo.termino_jogo) : ''}</span>
                <span class="meta-item"><i class="bi bi-geo-alt"></i>${esc(jogo.nome_local || 'Quadra')}</span>
            `;

            const dot = status.dot ? '<i class="bi bi-circle-fill me-1" aria-hidden="true"></i>' : '';

            return `
                <div class="jogo-card" data-jogo-id="${esc(jogo.id_jogo)}"
                     onclick="abrirDetalhesJogo(this)" role="button" tabindex="0">
                    <div class="jogo-top">
                        <div class="jogo-meta">${metaInfo}</div>
                        <span class="badge rounded-pill ${status.classe}">${dot}${esc(status.texto)}</span>
                    </div>

                    <div class="modalidade-row">
                        <span class="modalidade-chip">
                            <i class="bi ${iconeModalidade(jogo.nome_modalidade)}"></i>${esc(jogo.nome_modalidade)}
                        </span>
                        <span class="fase-tag"><i class="bi bi-diagram-3 me-1"></i>${esc(jogo.nome_jogo)}</span>
                    </div>

                    <div class="confronto-area">
                        <div class="equipe">
                            <span class="turma-tag">${esc(eqA.tag)}</span>
                            <span class="equipe-nome">${esc(eqA.nome)}</span>
                        </div>

                        <div class="placar-box">
                            <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarA)}</span>
                            <span class="vs-text mx-1">x</span>
                            <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarB)}</span>
                        </div>

                        <div class="equipe">
                            <span class="turma-tag">${esc(eqB.tag)}</span>
                            <span class="equipe-nome">${esc(eqB.nome)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ============================ MODAL DE DETALHES ============================

    async function abrirDetalhesJogo(btn) {
        const idJogo = btn.dataset.jogoId;
        const jogo = todosOsJogos.find(j => String(j.id_jogo) === String(idJogo));
        const corpo = document.getElementById('modalModalidadeCorpo');

        document.getElementById('modalModalidadeTitle').innerHTML =
            '<i class="bi bi-trophy-fill me-2"></i>Resumo da Partida';

        corpo.innerHTML = `
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
                Carregando detalhes...
            </div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalModalidade'));
        modal.show();

        if (!jogo) {
            corpo.innerHTML = `
                <div class="modal-empty">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2 text-danger"></i>
                    Partida não encontrada.
                </div>`;
            return;
        }

        try {
            const [resPartidas, resDestaques] = await Promise.all([
                fetch(`/api/v1/partidas?id_jogo=${idJogo}`),
                fetch(`/api/v1/artilheiros?id_jogo=${idJogo}&ano=${anoInterclasse}`)
            ]);

            const partidas = await resPartidas.json();
            const destaques = await resDestaques.json();

            corpo.innerHTML = montarHTMLResumoJogo(jogo, partidas, destaques);
            inicializarAcordeoes();
        } catch (e) {
            console.error('Erro ao carregar resumo da partida:', e);
            corpo.innerHTML = `
                <div class="modal-empty">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2 text-danger"></i>
                    Erro ao carregar o resumo da partida. Tente novamente.
                </div>`;
        }
    }

    function montarHTMLResumoJogo(jogo, partidas, destaques) {
        const status = badgeStatus(jogo.status_jogo);
        const isFinalizado = String(jogo.status_jogo).toLowerCase() === 'concluido';

        const eqA = jogo.equipes[0] || { nome: 'A Definir', tag: '??', placar: '-' };
        const eqB = jogo.equipes[1] || { nome: 'A Definir', tag: '??', placar: '-' };

        const placarA = isFinalizado ? (eqA.placar ?? '0') : '-';
        const placarB = isFinalizado ? (eqB.placar ?? '0') : '-';
        const dot = status.dot ? '<i class="bi bi-circle-fill me-1" aria-hidden="true"></i>' : '';

        let html = '';

        // ===== CONFRONTO =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-shield-fill"></i>Partida</div>';
        html += `
            <div class="confronto-area mb-3">
                <div class="equipe">
                    <span class="turma-tag">${esc(eqA.tag)}</span>
                    <span class="equipe-nome">${esc(eqA.nome)}</span>
                </div>
                <div class="placar-box">
                    <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarA)}</span>
                    <span class="vs-text mx-1">x</span>
                    <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarB)}</span>
                </div>
                <div class="equipe">
                    <span class="turma-tag">${esc(eqB.tag)}</span>
                    <span class="equipe-nome">${esc(eqB.nome)}</span>
                </div>
            </div>
            <div class="text-center">
                <span class="badge rounded-pill ${status.classe}">${dot}${esc(status.texto)}</span>
            </div>
        `;
        html += '</div>';

        // ===== DESTAQUE DA PARTIDA =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-lightning-charge-fill"></i>Destaque da Partida</div>';

        const artilheiros = Array.isArray(destaques) ? destaques : [];
        const artilheiroTop = artilheiros[0];
        if (artilheiroTop) {
            const temFotoReal = artilheiroTop.foto_usuario && !/^default\.(jpg|jpeg|png|gif|webp)$/i.test(artilheiroTop.foto_usuario);
            const foto = temFotoReal ? `${APP_BASE}/uploads/fotosUsuarios/${encodeURIComponent(artilheiroTop.foto_usuario)}` : '';
            html += `
                <div class="destaque-card">
                    ${foto
                        ? `<img class="destaque-foto" src="${foto}" alt="${esc(artilheiroTop.nome_usuario)}" onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');">`
                        : ''}
                    <span class="destaque-icone ${foto ? 'd-none' : ''}"><i class="bi bi-award-fill"></i></span>
                    <div>
                        <div class="destaque-nome">${esc(artilheiroTop.nome_usuario)} <i class="bi bi-star-fill text-warning"></i></div>
                        <div class="destaque-sub">${esc(artilheiroTop.nome_fantasia_turma || artilheiroTop.nome_turma || '')}</div>
                    </div>
                    <div class="destaque-valor">${esc(artilheiroTop.total_gols)}<small>gols</small></div>
                </div>`;
        } else {
            html += '<div class="modal-empty"><i class="bi bi-person-dash d-block mb-1"></i>Ainda não há destaque registrado para esta partida.</div>';
        }
        html += '</div>';

        // ===== EQUIPES DA PARTIDA =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-people-fill"></i>Equipes da Partida</div>';

        const equipesMap = {};
        (Array.isArray(partidas) ? partidas : []).forEach(row => {
            if (!row.equipes_id_equipe) return;
            if (!equipesMap[row.equipes_id_equipe]) {
                equipesMap[row.equipes_id_equipe] = {
                    id_equipe: row.equipes_id_equipe,
                    nome: row.nome_fantasia_turma || row.nome_turma || 'Equipe',
                    tag: abreviarTurma(row.nome_turma, row.nome_fantasia_turma)
                };
            }
        });
        const equipesLista = Object.values(equipesMap);

        if (equipesLista.length > 0) {
            html += '<div class="accordion accordion-soft" id="accordionEquipes">';
            html += equipesLista.map(e => `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#equipe-${e.id_equipe}"
                            aria-expanded="false"
                            aria-controls="equipe-${e.id_equipe}">
                            <span class="turma-tag me-2">${esc(e.tag)}</span>
                            <span class="flex-grow-1 text-start">${esc(e.nome)}</span>
                        </button>
                    </h2>
                    <div id="equipe-${e.id_equipe}" class="accordion-collapse collapse"
                        data-bs-parent="#accordionEquipes">
                        <div class="accordion-body">
                            <div class="membros-carregando text-muted small py-2">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Carregando integrantes...
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            html += '</div>';
        } else {
            html += '<div class="modal-empty"><i class="bi bi-people d-block mb-1"></i>Nenhuma equipe vinculada a esta partida.</div>';
        }
        html += '</div>';

        // ===== INFORMAÇÕES DA PARTIDA =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-info-circle-fill"></i>Informações</div>';

        const itens = [
            { icone: 'bi-calendar3', rotulo: 'Data', valor: formatarData(jogo.data_jogo) },
            { icone: 'bi-clock', rotulo: 'Horário', valor: formatarHora(jogo.inicio_jogo) + (jogo.termino_jogo ? ' às ' + formatarHora(jogo.termino_jogo) : '') },
            { icone: 'bi-geo-alt', rotulo: 'Local', valor: jogo.nome_local || 'A definir' },
            { icone: 'bi-trophy', rotulo: 'Modalidade', valor: jogo.nome_modalidade || '—' },
            { icone: 'bi-tags', rotulo: 'Categoria', valor: jogo.nome_categoria || '—' },
            { icone: 'bi-diagram-3', rotulo: 'Fase', valor: jogo.nome_jogo || '—' }
        ];

        html += itens.map(item => `
            <div class="fase-item">
                <i class="bi ${item.icone} text-danger"></i>
                <span class="text-muted">${esc(item.rotulo)}:</span>
                <span class="fase-nome">${esc(item.valor)}</span>
            </div>
        `).join('');
        html += '</div>';

        return html;
    }

    // Agrupa jogos do chaveamento por fase e calcula progresso
    function agruparFases(jogos) {
        const mapa = {};
        jogos.forEach(j => {
            const nome = j.nome_fase || 'Fase';
            if (!mapa[nome]) {
                mapa[nome] = { nome: nome, total: 0, concluidos: 0 };
            }
            mapa[nome].total++;
            const s = String(j.status_jogo || '').toLowerCase();
            if (s === 'concluido' || s === 'finalizado') mapa[nome].concluidos++;
        });

        const fases = Object.values(mapa);
        fases.sort((a, b) => (b.total - a.total));

        fases.forEach(f => { f.pendentes = f.total - f.concluidos; });
        return fases;
    }

    // Carrega os membros quando um acordeão é aberto
    function inicializarAcordeoes() {
        document.querySelectorAll('#accordionEquipes .accordion-item').forEach(item => {
            pageScope.listen(item.querySelector('.accordion-collapse'), 'show.bs.collapse', () => {
                const corpo = item.querySelector('.accordion-body');
                if (!corpo.dataset.carregado) {
                    carregarMembrosEquipe(item, corpo);
                }
            });
        });
    }

    async function carregarMembrosEquipe(item, corpo) {
        if (corpo.dataset.carregado) return;
        corpo.dataset.carregado = '1';

        const target = item.querySelector('.accordion-button').getAttribute('data-bs-target');
        const idEquipe = target.replace('#equipe-', '');

        try {
            const res = await fetch(`/api/v1/equipes?id_equipe=${idEquipe}`);
            const membros = await res.json();
            const lista = Array.isArray(membros) ? membros : [];

            if (lista.length === 0) {
                corpo.innerHTML = '<div class="modal-empty text-start p-0 py-1">Nenhum integrante vinculado a esta equipe.</div>';
                return;
            }

            corpo.innerHTML = lista.map(m => `
                <div class="membro-item">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span>${esc(m.nome_usuario)}</span>
                </div>
            `).join('');
        } catch (e) {
            console.error('Erro ao carregar integrantes:', e);
            corpo.innerHTML = '<div class="text-danger small">Erro ao carregar integrantes.</div>';
        }
    }

    // Filtro de status
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        pageScope.listen(btn, 'click', () => {
            document.querySelectorAll('.filtro-btn').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-pressed', 'true');
            filtroStatus = btn.dataset.filter;
            renderizarJogos();
        });
    });

    // Filtro de modalidade
    pageScope.listen(document.getElementById('filtroModalidade'), 'change', (e) => {
        filtroModalidade = e.target.value;
        renderizarJogos();
    });

    // Filtro de categoria
    pageScope.listen(document.getElementById('filtroCategoria'), 'change', (e) => {
        filtroCategoria = e.target.value;
        renderizarJogos();
    });

    window.SGIPage.ready( inicializarJogos);

return {esc, iconeModalidade, abreviarTurma, formatarData, formatarHora, traduzirNomeJogo, badgeStatus, inicializarJogos, preencherFiltroModalidades, preencherFiltroCategorias, renderizarJogos, abrirDetalhesJogo, montarHTMLResumoJogo, agruparFases, inicializarAcordeoes, carregarMembrosEquipe};
});
