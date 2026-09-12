window.SGIPage.mount("aluno/modalidade", function (pageConfig, pageScope) {

    const APP_BASE = window.SGI_BASE_PATH || '';

    const urlParams = new URLSearchParams(window.location.search);
    
    // CORREÇÃO: Transformado de "const" para "let" para permitir a reatribuição da variável depois
    let idInterclasse = urlParams.get('id'); 
    
    const generoUsuario = pageConfig.value2;
    const categoriaUsuario = pageConfig.value3;
    const idTurmaUsuario = pageConfig.value4;
    const modalidadesInscritas = pageConfig.value5;
    const estaInscrito = modalidadesInscritas.length > 0;
    let modalidadesData = [];

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

    async function carregarDados() {
        try {
            if (!idInterclasse) {
                const listaInter = await (await fetch('/api/v1/edicoes?regulamento=true')).json();
                const ativos = (Array.isArray(listaInter) ? listaInter : []).filter(i => String(i.status_interclasse) === '1');
                if (ativos.length === 0) {
                    return;
                }
                idInterclasse = String(ativos[0].id_interclasse);
                const url = new URL(window.location);
                url.searchParams.set('id', idInterclasse);
                window.history.replaceState({}, '', url);
            }

            const resInter = await fetch('/api/v1/edicoes?regulamento=true');
            const listaInter = await resInter.json();
            const dadosInter = (Array.isArray(listaInter) ? listaInter : []).find(i => String(i.id_interclasse) === String(idInterclasse));
            if (dadosInter) {
                const msg = estaInscrito ? ' — Suas inscrições' : ' — Selecione até 3 modalidades';
            }

            let urlMod = `/api/v1/modalidades?id_interclasse=${idInterclasse}`;
            if (idTurmaUsuario > 0) urlMod += `&id_turma=${idTurmaUsuario}`;
            const res = await fetch(urlMod);
            const lista = await res.json();
            modalidadesData = Array.isArray(lista) ? lista.filter(m => String(m.status_modalidade) === '1') : [];

            if (estaInscrito) {
                renderizarInscricoes();
            } else {
                document.getElementById('inscricoesAtuais').innerHTML = '';
                const secao = document.getElementById('secaoInscricoes');
                if (secao) secao.classList.add('d-none');
            }
            renderizarSelecao();

        } catch (e) {
            console.error(e);
            document.getElementById('modalidadesGrid').innerHTML = '<div class="col-12 text-center text-danger py-5">Erro ao carregar modalidades.</div>';
        }
    }

    function atualizarContador() {
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;
        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const total = inscritos + selecionados;
        const restantes = Math.max(0, 3 - inscritos);
        document.getElementById('contador').textContent = `Você pode escolher até ${restantes} modalidade(s) (${total}/3)`;
    }

    // Atualiza o painel visual de progresso (contador grande, barra e botão)
    function atualizarProgresso() {
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;
        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const total = Math.min(3, inscritos + selecionados);

        const numEl = document.getElementById('counterNum');
        if (numEl) numEl.textContent = total;

        const badge = document.getElementById('limiteBadge');
        const statusDefault = document.getElementById('statusDefault');
        if (badge && statusDefault) {
            const atingiu = total >= 3;
            badge.classList.toggle('d-none', !atingiu);
            statusDefault.classList.toggle('d-none', atingiu);
        }

        const countEl = document.getElementById('progressCount');
        if (countEl) countEl.textContent = `${total} de 3`;
        const progress = document.querySelector('#acoesInscricao .progress');
        const progressBar = document.getElementById('progressBar');
        if (progress) progress.setAttribute('aria-valuenow', String(total));
        if (progressBar) progressBar.style.width = `${(total / 3) * 100}%`;

        const btn = document.getElementById('btnSalvar');
        if (btn && btn.innerHTML.indexOf('Salvando') === -1) {
            btn.disabled = selecionados === 0;
        }
    }

    // Observa as mudanças de seleção dos cards para manter o progresso em dia
    function inicializarProgresso() {
        atualizarProgresso();
        const grid = document.getElementById('modalidadesGrid');
        if (!grid) return;
        new MutationObserver(() => atualizarProgresso())
            .observe(grid, { attributes: true, childList: true, subtree: true, attributeFilter: ['class'] });
    }

    // Define o status de vagas de uma modalidade com base na capacidade da turma do aluno.
    // Capacidade por turma = max_inscrito_modalidade x max_equipes (ilimitado se algum for ilimitado).
    function statusVagas(mod) {
        const maxInscrito = parseInt(mod.max_inscrito_modalidade) || 0;
        const maxEquipes = mod.max_equipes ? parseInt(mod.max_equipes) || 0 : 0;
        const capacidade = (maxInscrito > 0 && maxEquipes > 0) ? maxInscrito * maxEquipes : 0;
        if (capacidade <= 0) {
            return { cls: '', icon: '', label: '' };
        }
        const inscritos = parseInt(mod.qtd_inscritos_turma) || 0;
        const restantes = capacidade - inscritos;
        if (restantes <= 0) {
            return { state: 'lotado', badge: 'text-bg-danger', icon: 'bi-x-circle-fill', label: 'Lotado' };
        }
        if (restantes <= 2) {
            return { state: 'limited', badge: 'text-bg-warning', icon: 'bi-exclamation-triangle-fill', label: 'Poucas vagas' };
        }
        return { state: '', badge: '', icon: '', label: '' };
    }

    function atualizarEstadoCard(card, selecionado) {
        card.classList.toggle('selected', selecionado);
        card.classList.toggle('border-primary', selecionado);
        card.classList.toggle('bg-primary-subtle', selecionado);
        card.classList.toggle('shadow', selecionado);
        const chip = card.querySelector('.card-equipe');
        if (chip) chip.classList.toggle('d-none', !selecionado);
        const check = card.querySelector('.card-check');
        if (check) check.classList.toggle('d-none', !selecionado);
    }

    function renderizarSelecao() {
        const grid = document.getElementById('modalidadesGrid');
        const acoes = document.getElementById('acoesInscricao');
        grid.innerHTML = '';

        const inscritosIds = new Set(modalidadesInscritas.map(m => String(m.id_modalidade)));
        const qtdInscritos = inscritosIds.size;

        if (qtdInscritos >= 3) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12 w-100" ><div class="d-flex flex-column align-items-center justify-content-center text-center text-success py-5" ><i class="bi bi-check-circle-fill fs-1 mb-2"></i><span>Você já está inscrito em 3 modalidades. Limite atingido.</span></div></div>';
            return;
        }

        const filtradas = modalidadesData.filter(mod =>
            (mod.genero_modalidade === 'MISTO' || mod.genero_modalidade === generoUsuario) &&
            parseInt(mod.categorias_id_categoria) === categoriaUsuario
        );

        const disponiveis = filtradas.filter(mod => !inscritosIds.has(String(mod.id_modalidade)));

        atualizarContador();

        if (disponiveis.length === 0) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12 w-100" ><div class="d-flex flex-column align-items-center justify-content-center text-center text-muted py-5" ><i class="bi bi-inbox fs-1 mb-2"></i><span>Nenhuma modalidade disponível para sua categoria no momento.</span></div></div>';
            return;
        }

        acoes.classList.remove('d-none');

        disponiveis.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            const vagas = statusVagas(mod);
            const lotado = vagas.state === 'lotado';
            col.innerHTML = `
                <div class="modalidade-card card border shadow-sm position-relative h-100 p-4 text-center d-flex flex-column align-items-center gap-2 sgi-u-cursor-pointer${lotado ? ' lotado opacity-50' : ''}" role="button" tabindex="0" data-sgi-action="open-equipe" data-id="${esc(mod.id_modalidade)}" data-nome="${esc(mod.nome_modalidade)}">
                    <span class="card-check position-absolute top-0 end-0 translate-middle badge rounded-circle text-bg-primary d-none"><i class="bi bi-check-lg"></i></span>
                    ${vagas.label ? `<span class="badge ${vagas.badge} position-absolute top-0 start-0 translate-middle-y ms-2"><i class="bi ${vagas.icon} me-1"></i>${vagas.label}</span>` : ''}
                    <div class="card-icon-wrap bg-primary-subtle text-primary rounded-3 p-3 fs-3 d-inline-flex"><i class="bi ${iconeModalidade(mod.nome_modalidade)}"></i></div>
                    <div class="card-info d-flex flex-column align-items-center gap-1">
                        <span class="card-nome fw-semibold">${esc(mod.nome_modalidade)}</span>
                        <span class="card-categoria badge text-bg-light border text-body-secondary text-uppercase">${esc(mod.nome_categoria || 'Categoria')}</span>
                        <span class="card-equipe badge bg-primary-subtle text-primary d-none"></span>
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function renderizarInscricoes() {
        const container = document.getElementById('inscricoesAtuais');
        container.innerHTML = '';

        if (modalidadesInscritas.length === 0) {
            document.getElementById('secaoInscricoes').classList.add('d-none');
            return;
        }

        document.getElementById('secaoInscricoes').classList.remove('d-none');
        const badge = document.getElementById('badgeInscricoes');
        if (badge) badge.textContent = `${modalidadesInscritas.length}/3`;

        const wrapper = document.createElement('div');
        wrapper.className = 'row row-cols-1 row-cols-md-3 g-3';

        const equipesParaCarregar = [];

        modalidadesInscritas.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="card border-0 border-start border-4 border-success shadow-sm p-3 h-100" data-equipe="${mod.id_equipe}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-trophy fs-4 text-success"></i>
                        <div>
                            <strong class="d-block">${esc(mod.nome_modalidade)}</strong>
                            <small class="text-muted">${esc(mod.nome_categoria)}</small>
                        </div>
                        <span class="badge bg-success-subtle text-success ms-auto rounded-pill">Inscrito</span>
                    </div>
                    <div class="membros-equipe mt-2" id="membros-${mod.id_equipe}">
                        <div class="text-center text-muted small py-2">
                            <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                            Carregando equipe...
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1"
                            data-modalidade-id="${mod.id_modalidade}"
                            data-modalidade-nome="${esc(mod.nome_modalidade)}"
                            data-sgi-action="details-modalidade">
                            <i class="bi bi-calendar-event me-1"></i> Ver detalhes
                        </button>
                    </div>
                </div>
            `;
            wrapper.appendChild(col);
            equipesParaCarregar.push(mod.id_equipe);
        });

        container.appendChild(wrapper);

        equipesParaCarregar.forEach(idEquipe => carregarMembros(idEquipe));
    }

    function formatarData(dataStr) {
        if (!dataStr) return 'A definir';
        const d = new Date(dataStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dataStr;
        return d.toLocaleDateString('pt-BR');
    }

    async function verDetalhesModalidade(btn) {
        const idModalidade = btn.dataset.modalidadeId;
        const nomeModalidade = btn.dataset.modalidadeNome;
        const corpo = document.getElementById('modalDetalhesCorpo');

        document.getElementById('modalDetalhesTitle').textContent = nomeModalidade || 'Detalhes';
        corpo.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando jogos...</div>';

        const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
        modal.show();

        try {
            const res = await fetch(`/api/v1/jogos?id_modalidade=${idModalidade}&id_interclasse=${idInterclasse}`);
            const jogos = await res.json();
            const lista = Array.isArray(jogos) ? jogos : [];

            if (lista.length === 0) {
                corpo.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nenhum jogo agendado para esta modalidade ainda.</div>';
                return;
            }

            corpo.innerHTML = lista.map(j => {
                const status = j.status_jogo || 'Agendado';
                const ehFinalizado = String(status).toLowerCase() === 'concluido';
                const badgeCls = ehFinalizado ? 'text-bg-success' : 'text-bg-warning';
                const badgeTxt = ehFinalizado ? 'Finalizado' : (String(status).toLowerCase() === 'iniciado' ? 'Em andamento' : 'Agendado');

                const hora = j.inicio_jogo ? String(j.inicio_jogo).substring(0, 5) : '--:--';
                const horaFim = j.termino_jogo ? String(j.termino_jogo).substring(0, 5) : '';
                const local = j.nome_local || 'A definir';
                const confronto = j.equipes_nomes || 'A definir';

                return `
                    <div class="py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-calendar-event me-2 text-primary"></i>${formatarData(j.data_jogo)}</span>
                            <span class="badge rounded-pill ${badgeCls}">${badgeTxt}</span>
                        </div>
                        <div class="small text-body-secondary">
                            <i class="bi bi-clock me-1"></i>${hora}${horaFim ? ' - ' + horaFim : ''}
                            <span class="mx-2">|</span>
                            <i class="bi bi-geo-alt me-1"></i>${esc(local)}
                        </div>
                        <div class="small text-body-secondary mt-1">
                            <i class="bi bi-shield me-1"></i>${esc(confronto)}
                        </div>
                    </div>
                `;
            }).join('');

        } catch (e) {
            console.error('Erro ao carregar jogos:', e);
            corpo.innerHTML = '<div class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>Erro ao carregar os jogos. Tente novamente.</div>';
        }
    }

    async function carregarMembros(idEquipe) {
        const container = document.getElementById('membros-' + idEquipe);
        try {
            const res = await fetch(`/api/v1/equipes?id_equipe=${idEquipe}`);
            const data = await res.json();
            const membros = Array.isArray(data) ? data : [];

            container.innerHTML = '<div class="fw-semibold small text-body-secondary mb-1"><i class="bi bi-people-fill me-1"></i>Sua equipe:</div>';

            if (membros.length === 0) {
                container.innerHTML += '<div class="text-body-secondary small">Nenhum colega na equipe ainda.</div>';
                return;
            }

            const userId = pageConfig.value6;
            membros.forEach(m => {
                const ehVoce = String(m.id_usuario) === String(userId);
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center gap-2 py-1';
                const img = document.createElement('img');
                img.className = 'rounded-circle d-none object-fit-cover';
                img.width = 26; img.height = 26;
                img.alt = '';
                img.onload = function() { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                img.onerror = function() { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                const icon = document.createElement('i');
                icon.className = 'bi bi-person-circle text-secondary';
                div.appendChild(img);
                div.appendChild(icon);
                const span = document.createElement('span');
                span.className = ehVoce ? 'fw-semibold text-success' : '';
                span.textContent = String(m.nome_usuario || '') + (ehVoce ? ' (Você)' : '');
                div.appendChild(span);
                container.appendChild(div);
                fetch('/api/v1/foto?user_id=' + m.id_usuario)
                    .then(r => r.json())
                    .then(d => { if (d.foto_usuario) img.src = APP_BASE + '/uploads/fotosUsuarios/' + encodeURIComponent(d.foto_usuario); })
                    .catch(function() {});
            });
        } catch (e) {
            console.error('Erro ao carregar membros:', e);
            container.innerHTML = '<div class="text-danger small">Erro ao carregar equipe.</div>';
        }
    }

    async function abrirEquipesModalidade(card) {
        const idModalidade = card.dataset.id;
        const nomeModalidade = card.dataset.nome;

        if (card.classList.contains('lotado')) {
            document.getElementById('msgFeedback').textContent = 'Modalidade lotada. Não é possível se inscrever.';
            card.classList.add('border-danger');
            setTimeout(() => card.classList.remove('border-danger'), 500);
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
            return;
        }

        if (card.classList.contains('selected')) {
            atualizarEstadoCard(card, false);
            delete card.dataset.equipe;
            delete card.dataset.equipeNome;
            const chip = card.querySelector('.card-equipe');
            if (chip) chip.textContent = '';
            atualizarContador();
            return;
        }

        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;

        if (selecionados + inscritos >= 3) {
            document.getElementById('msgFeedback').textContent = 'Você já selecionou o número máximo de modalidades.';
            card.classList.add('border-danger');
            setTimeout(() => card.classList.remove('border-danger'), 500);
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
            return;
        }

        const chip = card.querySelector('.card-equipe');
        if (chip) chip.textContent = 'Carregando...';

        try {
            const res = await fetch(`/api/v1/equipes?id_modalidade=${idModalidade}&id_turma=${idTurmaUsuario}`);
            const dados = await res.json();
            const equipes = Array.isArray(dados) ? dados : [];

            if (chip) chip.textContent = '';

            if (equipes.length === 0) {
                document.getElementById('msgFeedback').textContent = 'Nenhuma equipe disponível para esta modalidade.';
                setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
                return;
            }

            document.getElementById('modalEquipesTitle').textContent = `Escolha a equipe`;
            document.getElementById('modalEquipesSubtitulo').textContent = nomeModalidade;
            
            const corpo = document.getElementById('modalEquipesCorpo');
            corpo.innerHTML = equipes.map(e => `
                <div class="equipe-pick-row d-flex align-items-center gap-3 p-3 mb-2 border rounded-3 bg-body sgi-u-cursor-pointer" role="button" tabindex="0" data-sgi-action="select-equipe" data-modalidade-id="${esc(idModalidade)}" data-equipe="${esc(e.id_equipe)}" data-equipe-nome="${esc(e.nome_equipe)}">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-inline-flex"><i class="bi bi-people-fill"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${esc(e.nome_equipe)}</div>
                        <div class="small text-body-secondary">Equipe da turma</div>
                    </div>
                    <div class="d-none bg-primary text-white rounded-circle p-1"><i class="bi bi-check-lg"></i></div>
                </div>
            `).join('');
            
            const modal = new bootstrap.Modal(document.getElementById('modalEquipes'));
            modal.show();

        } catch (e) {
            console.error(e);
            if (chip) chip.textContent = 'Erro ao carregar';
        }
    }

    function selecionarEquipe(row, idModalidade) {
        const equipe = row.dataset.equipe;
        const equipeNome = row.dataset.equipeNome;

        document.querySelectorAll('.modalidade-card').forEach(card => {
            if (String(card.dataset.id) === String(idModalidade)) {
                atualizarEstadoCard(card, true);
                card.dataset.equipe = equipe;
                card.dataset.equipeNome = equipeNome;
                const chip = card.querySelector('.card-equipe');
                if (chip) chip.textContent = 'Equipe: ' + equipeNome;
            }
        });

        bootstrap.Modal.getInstance(document.getElementById('modalEquipes'))?.hide();
        atualizarContador();
    }

    function removerEquipeSelecionada(idModalidade) {
        document.querySelectorAll('.modalidade-card').forEach(card => {
            if (String(card.dataset.id) === String(idModalidade)) {
                atualizarEstadoCard(card, false);
                delete card.dataset.equipe;
                delete card.dataset.equipeNome;
                const chip = card.querySelector('.card-equipe');
                if (chip) chip.textContent = '';
            }
        });

        bootstrap.Modal.getInstance(document.getElementById('modalEquipes'))?.hide();
        atualizarContador();
    }

    async function salvarEscolhas() {
        const selecionados = document.querySelectorAll('.modalidade-card.selected');
        if (selecionados.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Por favor, escolha pelo menos 1 modalidade.';
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
            return;
        }

        const lotados = Array.from(selecionados).filter(c => c.classList.contains('lotado'));
        if (lotados.length > 0) {
            document.getElementById('msgFeedback').textContent = 'Uma ou mais modalidades selecionadas ficaram lotadas. Remova-as e tente novamente.';
            lotados.forEach(c => {
                atualizarEstadoCard(c, false);
                delete c.dataset.equipe;
                delete c.dataset.equipeNome;
                const chip = c.querySelector('.card-equipe');
                if (chip) chip.textContent = '';
            });
            atualizarContador();
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 3000);
            return;
        }

        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Salvando...';

        const ids = [];
        selecionados.forEach(card => {
            const equipe = parseInt(card.dataset.equipe || 0);
            if (equipe > 0) ids.push(equipe);
        });

        if (ids.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Escolha uma equipe para cada modalidade selecionada.';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
            return;
        }

        try {
            const res = await fetch('/api/v1/inscricoes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_interclasse: parseInt(idInterclasse),
                    id_equipes: ids
                })
            });
            const result = await res.json();
            document.getElementById('msgFeedback').textContent = result.message;
            if (result.success) {
                document.getElementById('msgFeedback').className = 'small text-success text-center mb-0 mt-2';
                setTimeout(() => window.location.href = APP_BASE + '/aluno/inicio', 1500);
            } else {
                document.getElementById('msgFeedback').className = 'small text-danger text-center mb-0 mt-2';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
            }
        } catch (e) {
            console.error(e);
            document.getElementById('msgFeedback').textContent = 'Erro de conexão. Tente novamente.';
            document.getElementById('msgFeedback').className = 'small text-danger text-center mb-0 mt-2';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
        }
    }

    function vincularEventos() {
        const grid = document.getElementById('modalidadesGrid');
        const inscricoes = document.getElementById('inscricoesAtuais');
        const equipes = document.getElementById('modalEquipesCorpo');

        const ativar = (event) => {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
            if (event.type === 'keydown') event.preventDefault();
            const card = event.target.closest('[data-sgi-action="open-equipe"]');
            if (card) abrirEquipesModalidade(card);
        };
        pageScope.listen(grid, 'click', ativar);
        pageScope.listen(grid, 'keydown', ativar);

        pageScope.listen(inscricoes, 'click', (event) => {
            const button = event.target.closest('[data-sgi-action="details-modalidade"]');
            if (button) verDetalhesModalidade(button);
        });

        const selecionar = (event) => {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
            if (event.type === 'keydown') event.preventDefault();
            const row = event.target.closest('[data-sgi-action="select-equipe"]');
            if (row) selecionarEquipe(row, row.dataset.modalidadeId);
        };
        pageScope.listen(equipes, 'click', selecionar);
        pageScope.listen(equipes, 'keydown', selecionar);
    }

    window.SGIPage.ready(() => {
        vincularEventos();
        carregarDados();
    });
    window.SGIPage.ready( inicializarProgresso);

return {esc, iconeModalidade, carregarDados, atualizarContador, atualizarProgresso, inicializarProgresso, statusVagas, renderizarSelecao, renderizarInscricoes, formatarData, verDetalhesModalidade, carregarMembros, abrirEquipesModalidade, selecionarEquipe, removerEquipeSelecionada, salvarEscolhas};
});
