window.SGIPage.mount("eventos/configurar-agenda", function (pageConfig, pageScope) {

(function () {
    // A tela de agenda pode continuar aguardando respostas enquanto a SPA
    // troca o conteúdo principal. Invalide a montagem anterior e permita que
    // as rotinas de renderização reconheçam que seus nós já não existem.
    if (typeof window.__SGI_TELA_CLEANUP__ === 'function') {
        try { window.__SGI_TELA_CLEANUP__(); } catch (_) {}
    }
    window.__SGI_TELA_CLEANUP__ = function () {};
    const API = '/api/v1/';
    const NIVEL_USUARIO = pageConfig.value2;
    let dataNavegacao = new Date();
    const params = new URLSearchParams(window.location.search);
    const idInterclasseAgenda = params.get('id');

    let interclasseAtual = null;
    let jogosCache = [];
    let modalidadesLista = [];
    let locaisLista = [];
    let jogoEmEdicao = null;
    let filtroData = null;
    let filtroStatus = '';
    let buscaAtual = '';

    function formatNomeJogo(nomeJogo) {
        if (/^IND:\d+$/.test(nomeJogo || '')) {
            return 'Competição Individual';
        }
        const mm = (nomeJogo || '').match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 16: 'Oitavas de final', 8: 'Quartas de final', 4: 'Semifinal', 2: 'Final', 1: 'Campeão' };
            const fase = fases[largura] || 'Fase';
            if (largura === 1) return fase;
            return `${fase} — Confronto ${slot + 1}${kind === 'B' ? ' (bye)' : ''}`;
        }
        return nomeJogo || 'Jogo';
    }

    function ymd(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function hojeISO() {
        return ymd(new Date());
    }

    function formatarHora(t) {
        if (!t) return '';
        const s = String(t);
        return s.length >= 5 ? s.slice(0, 5) : s;
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function labelStatus(status) {
        const map = {
            Agendado: 'Agendado',
            Iniciado: 'Em andamento',
            Pausado: 'Pausado',
            Concluido: 'Concluído',
            Finalizado: 'Concluído'
        };
        return map[status] || status || '—';
    }

    function podeIniciar(j) {
        if (!j || j.status_jogo !== 'Agendado') return false;
        const hj = hojeISO();
        return j.data_jogo <= hj;
    }

    function modalidadeSelecionadaId() {
        const el = document.getElementById('agenda-select-mod');
        const v = el && el.value ? el.value : '';
        const elM = document.getElementById('agenda-select-mod-mobile');
        const vM = elM && elM.value ? elM.value : '';
        return v || vM || '';
    }

    function syncSelectModalidade(fromDesk) {
        const desk = document.getElementById('agenda-select-mod');
        const mob = document.getElementById('agenda-select-mod-mobile');
        if (!desk || !mob) return;
        if (fromDesk) mob.value = desk.value;
        else desk.value = mob.value;
    }

    function syncSelectStatus(fromDesk) {
        const desk = document.getElementById('agenda-select-status');
        const mob = document.getElementById('agenda-select-status-mobile');
        if (!desk || !mob) return;
        if (fromDesk) mob.value = desk.value;
        else desk.value = mob.value;
    }

    function setBusca(v) {
        buscaAtual = v;
        const d = document.getElementById('agenda-busca');
        const m = document.getElementById('agenda-busca-mobile');
        if (d && d.value !== v) d.value = v;
        if (m && m.value !== v) m.value = v;
    }

    async function getInterclasseParaAgenda() {
        if (idInterclasseAgenda) {
            const item = await window.SGIInterclasse.getInterclasseById(idInterclasseAgenda);
            if (item) return item;
        }
        return window.SGIInterclasse.getActiveInterclasse();
    }

    function nomeEquipeLocal(item, mapaEquipes, mapaTurmas) {
        const equipe = mapaEquipes.get(String(item.id_equipe ?? item.equipes_id_equipe)) || {};
        const idTurma = item.id_turma ?? item.turmas_id_turma ?? equipe.turmas_id_turma;
        const turma = mapaTurmas.get(String(idTurma)) || {};
        return String(item.nome_equipe || equipe.nome_equipe || item.nome_fantasia ||
            item.nome_fantasia_turma || turma.nome_fantasia_turma ||
            item.nome_turma || turma.nome_turma || '').trim();
    }

    async function enriquecerNomesEquipesLocais(jogos) {
        if (!window.SGIDataLayer || typeof window.SGIDataLayer.read !== 'function') return jogos;
        try {
            const [partidas, equipes, turmas] = await Promise.all([
                window.SGIDataLayer.read('partidas'),
                window.SGIDataLayer.read('equipes'),
                window.SGIDataLayer.read('turmas')
            ]);
            const mapaEquipes = new Map((equipes || []).map((item) => [String(item.id_equipe), item]));
            const mapaTurmas = new Map((turmas || []).map((item) => [String(item.id_turma), item]));
            return jogos.map((jogo) => {
                if (String(jogo.equipes_nomes || '').trim()) return jogo;
                let fontes = Array.isArray(jogo.equipes) ? jogo.equipes : [];
                if (!fontes.length) {
                    fontes = (partidas || []).filter((item) =>
                        String(item.jogos_id_jogo) === String(jogo.id_jogo)
                    );
                }
                const nomes = fontes.map((item) => nomeEquipeLocal(item, mapaEquipes, mapaTurmas)).filter(Boolean);
                return nomes.length ? { ...jogo, equipes_nomes: nomes.join(' vs ') } : jogo;
            });
        } catch (_) {
            return jogos;
        }
    }

    async function carregarJogosDoInterclasse() {
        jogosCache = [];
        if (!interclasseAtual) return;
        const resMod = await fetch(`${API}modalidades`);
        if (!resMod.ok) throw new Error('Falha ao carregar modalidades');
        const todasMods = await resMod.json();
        modalidadesLista = (Array.isArray(todasMods) ? todasMods : []).filter(
            (m) => String(m.interclasses_id_interclasse) === String(interclasseAtual.id_interclasse)
        );
        const ids = [...new Set(modalidadesLista.map((m) => m.id_modalidade).filter(Boolean))];
        if (ids.length === 0) return;
        const batches = await Promise.all(
            ids.map((id) =>
                fetch(`${API}jogos?id_modalidade=${encodeURIComponent(id)}`).then(async (r) => {
                    const arr = await r.json();
                    return (Array.isArray(arr) ? arr : []).map((j) => ({
                        ...j,
                        modalidades_id_modalidade: Number(id)
                    }));
                })
            )
        );
        const map = new Map();
        batches.flat().forEach((j) => {
            if (j && j.id_jogo != null) map.set(String(j.id_jogo), j);
        });
        jogosCache = await enriquecerNomesEquipesLocais(Array.from(map.values()));
    }

    function jogosDoMesVisivel() {
        const y = dataNavegacao.getFullYear();
        const m = dataNavegacao.getMonth();
        const modF = modalidadeSelecionadaId();
        const stF = filtroStatus;
        const q = buscaAtual.toLowerCase();
        return jogosCache
            .filter((j) => {
                if (!j.data_jogo) return false;
                if (filtroData && j.data_jogo !== filtroData) return false;
                const [jy, jm] = j.data_jogo.split('-').map(Number);
                if (jy !== y || jm - 1 !== m) return false;
                if (modF && String(j.modalidades_id_modalidade) !== String(modF)) return false;
                if (stF === 'andamento' && j.status_jogo !== 'Iniciado' && j.status_jogo !== 'Pausado') return false;
                if (stF === 'Concluido' && j.status_jogo !== 'Concluido' && j.status_jogo !== 'Finalizado') return false;
                if (stF && stF !== 'andamento' && stF !== 'Concluido' && j.status_jogo !== stF) return false;
                if (q) {
                    const alvo = `${j.nome_modalidade || ''} ${j.nome_categoria || ''} ${j.nome_local || ''} ${j.equipes_nomes || ''} ${formatNomeJogo(j.nome_jogo)}`.toLowerCase();
                    if (!alvo.includes(q)) return false;
                }
                return true;
            })
            .sort((a, b) => {
                const da = `${a.data_jogo} ${a.inicio_jogo || ''}`;
                const db = `${b.data_jogo} ${b.inicio_jogo || ''}`;
                return da.localeCompare(db);
            });
    }

    function temJogoNoDia(ano, mesZeroBased, dia) {
        const m = String(mesZeroBased + 1).padStart(2, '0');
        const d = String(dia).padStart(2, '0');
        const key = `${ano}-${m}-${d}`;
        const modF = modalidadeSelecionadaId();
        return jogosCache.some((j) => {
            if (j.data_jogo !== key) return false;
            if (modF && String(j.modalidades_id_modalidade) !== String(modF)) return false;
            return true;
        });
    }

    function montarCardJogo(j) {
        const dataObj = new Date(j.data_jogo + 'T12:00:00');
        const diaNum = dataObj.toLocaleDateString('pt-BR', { day: '2-digit' });
        const mesCurto = dataObj.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
        const diaSem = dataObj.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '');
        const hi = formatarHora(j.inicio_jogo);
        const hf = formatarHora(j.termino_jogo || j.terminno_jogo);
        const horario = hi && hf ? `${hi} – ${hf}` : hi || 'Horário a definir';
        const placarHref = `/jogos/placar?id_jogo=${encodeURIComponent(j.id_jogo)}&origem=agenda_edit`;
        const statusClass = (j.status_jogo || '').toLowerCase().replace('ã','a').replace('õ','o');
        const statusMap = { agendado: 'agendado', iniciado: 'andamento', pausado: 'pausado', concluido: 'concluido', finalizado: 'concluido' };
        const cardClass = statusMap[statusClass] || 'agendado';
        const statusTxt = labelStatus(j.status_jogo);
        const podeAjustar = NIVEL_USUARIO <= 1;

        const modalidadeTxt = [j.nome_modalidade, j.nome_categoria].filter(Boolean).join(' – ');
        const localTxt = j.nome_local ? `<i class="bi bi-geo-alt"></i> ${escapeHtml(j.nome_local)}` : '';
        const equipes = j.equipes_nomes ? String(j.equipes_nomes).split(' vs ') : [];
        const teamsHtml = equipes.length >= 2
            ? `<div class="ag-event-card__teams"><span>${escapeHtml(equipes[0])}</span><span class="ag-vs">VS</span><span>${escapeHtml(equipes[1])}</span></div>`
            : (equipes.length === 1
                ? `<div class="ag-event-card__teams"><i class="bi bi-person-fill"></i> ${escapeHtml(equipes[0])}</div>`
                : '');

        const iniciarBtn = podeIniciar(j)
            ? `<button type="button" class="btn btn-danger iniciar-jogo-btn" data-id-jogo="${j.id_jogo}"><i class="bi bi-play-fill"></i> Iniciar jogo</button>`
            : '';
        const placarBtn =
            j.status_jogo === 'Iniciado' || j.status_jogo === 'Pausado'
                ? `<a class="btn btn-dark" href="${placarHref}"><i class="bi bi-clock-history"></i> Placar</a>`
                : '';
        const verBtn =
            j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado'
                ? `<a class="btn btn-danger" href="${placarHref}"><i class="bi bi-trophy"></i> Ver resultado</a>`
                : '';
        const ajusteBtn =
            podeAjustar && j.status_jogo === 'Agendado'
                ? `<button type="button" class="ag-icon-btn btn-ajuste-jogo" data-id-jogo="${j.id_jogo}" title="Ajustar data e local" aria-label="Ajustar data e local"><i class="bi bi-pencil"></i></button>`
                : '';

        return `
            <div class="ag-event-card ag-event-card--${cardClass}">
                <div class="ag-event-card__top">
                    <div class="ag-event-card__chips">
                        <span class="ag-meta-chip"><i class="bi bi-calendar3"></i> ${diaSem}, ${diaNum}/${mesCurto}</span>
                        <span class="ag-meta-chip"><i class="bi bi-clock"></i> ${horario}</span>
                    </div>
                    <span class="ag-status-chip ag-status-chip--${cardClass}">${escapeHtml(statusTxt)}</span>
                </div>
                <h3 class="ag-event-card__title">${escapeHtml(formatNomeJogo(j.nome_jogo))}</h3>
                <p class="ag-event-card__subtitle">
                    ${modalidadeTxt ? '<i class="bi bi-trophy-fill"></i> ' + escapeHtml(modalidadeTxt) : ''}
                    ${localTxt ? `<span class="sgi-inline-d7556a02">•</span> ${localTxt}` : ''}
                </p>
                ${teamsHtml}
                <div class="ag-event-card__actions">
                    ${iniciarBtn}${placarBtn}${verBtn}${ajusteBtn}
                </div>
            </div>`;
    }

    function renderListaEventos() {
        const containerDesk = document.getElementById('lista-eventos');
        const containerMob = document.getElementById('lista-eventos-mobile');
        if (!containerDesk || !containerMob || !containerDesk.isConnected || !containerMob.isConnected) return;
        const lista = jogosDoMesVisivel();
        const badge = document.getElementById('agenda-count-badge');

        containerDesk.innerHTML = '';
        containerMob.innerHTML = '';

        if (!interclasseAtual) {
            const msg = '<div class="ag-empty"><i class="bi bi-calendar-x"></i><p>Nenhum interclasse selecionado ou ativo.</p></div>';
            containerDesk.innerHTML = msg;
            containerMob.innerHTML = msg;
            if (badge) badge.style.display = 'none';
            return;
        }

        if (lista.length === 0) {
            const msg = filtroData
                ? '<div class="ag-empty"><i class="bi bi-calendar-x"></i><p>Nenhum jogo nesta data.</p></div>'
                : '<div class="ag-empty"><i class="bi bi-calendar-x"></i><p>Nenhum jogo neste mês.</p></div>';
            containerDesk.innerHTML = msg;
            containerMob.innerHTML = msg;
            const mostrarTodos = document.getElementById('container-mostrar-todos');
            const mostrarTodosMobile = document.getElementById('container-mostrar-todos-mobile');
            if (mostrarTodos) mostrarTodos.style.display = filtroData ? 'block' : 'none';
            if (mostrarTodosMobile) mostrarTodosMobile.style.display = filtroData ? 'block' : 'none';
            if (badge) badge.style.display = 'none';
            return;
        }

        if (badge) {
            const txt = document.getElementById('agenda-count-text');
            if (txt) txt.textContent = lista.length + (lista.length === 1 ? ' jogo' : ' jogos');
            badge.style.display = 'inline-flex';
        }

        lista.forEach((j) => {
            const html = montarCardJogo(j);
            containerDesk.innerHTML += html;
            containerMob.innerHTML += html;
        });

        document.querySelectorAll('.iniciar-jogo-btn').forEach((btn) => {
            pageScope.listen(btn, 'click', async (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                const id = btn.getAttribute('data-id-jogo');
                try {
                    const jogoAtual = jogosCache.find((item) => String(item.id_jogo) === String(id));
                    const duracao = Number(jogoAtual && jogoAtual.duracao_jogo) > 0
                        ? Number(jogoAtual.duracao_jogo)
                        : 20 * 60;
                    const restante = Number(jogoAtual && (jogoAtual.tempo_restante_jogo ?? jogoAtual.tempo_restante_calculado)) > 0
                        ? Number(jogoAtual.tempo_restante_jogo ?? jogoAtual.tempo_restante_calculado)
                        : duracao;
                    const r = await fetch(`${API}jogos`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_jogo: Number(id),
                            // Contexto usado somente pela projeção IndexedDB.
                            // Fica aninhado para não tentar alterar no MySQL os
                            // campos que o perfil mesário não pode editar.
                            _contexto_offline: jogoAtual ? {
                                id_jogo: Number(id),
                                nome_jogo: jogoAtual.nome_jogo || null,
                                data_jogo: jogoAtual.data_jogo || null,
                                inicio_jogo: jogoAtual.inicio_jogo || null,
                                termino_jogo: jogoAtual.termino_jogo || jogoAtual.terminno_jogo || null,
                                modalidades_id_modalidade: jogoAtual.modalidades_id_modalidade || null,
                                id_interclasse: jogoAtual.id_interclasse || jogoAtual.interclasses_id_interclasse || null,
                                locais_id_local: jogoAtual.locais_id_local || null,
                                nome_modalidade: jogoAtual.nome_modalidade || null,
                                nome_categoria: jogoAtual.nome_categoria || null,
                                nome_local: jogoAtual.nome_local || null,
                                equipes_nomes: jogoAtual.equipes_nomes || null,
                                tipos_modalidades_id_tipo_modalidade: jogoAtual.tipos_modalidades_id_tipo_modalidade || null
                            } : null,
                            status_jogo: 'Iniciado',
                            duracao_jogo: duracao,
                            tempo_restante_jogo: restante,
                            tempo_extra_jogo: Number(jogoAtual && jogoAtual.tempo_extra_jogo) || 0
                        })
                    });
                    const js = await r.json();
                    if (!r.ok || js.success === false) throw new Error(js.message || 'Falha ao iniciar');
                    if (js.offline && js.queued) {
                        if (jogoAtual) {
                            jogoAtual.status_jogo = 'Iniciado';
                            jogoAtual.duracao_jogo = duracao;
                            jogoAtual.tempo_restante_jogo = restante;
                            jogoAtual.tempo_restante_calculado = restante;
                            jogoAtual.tempo_extra_jogo = Number(jogoAtual.tempo_extra_jogo) || 0;
                            jogoAtual._pendente = true;
                        }
                        atualizarTelas();
                        return;
                    }
                    await carregarJogosDoInterclasse();
                    atualizarTelas();
                } catch (e) {
                    alert(e.message || 'Erro ao iniciar o jogo.');
                }
            });
        });

        const mostrarTodosInicial = document.getElementById('container-mostrar-todos');
        const mostrarTodosMobileInicial = document.getElementById('container-mostrar-todos-mobile');
        if (mostrarTodosInicial) mostrarTodosInicial.style.display = filtroData ? 'block' : 'none';
        if (mostrarTodosMobileInicial) mostrarTodosMobileInicial.style.display = filtroData ? 'block' : 'none';

        document.querySelectorAll('.btn-ajuste-jogo').forEach((btn) => {
            pageScope.listen(btn, 'click', () => {
                const id = Number(btn.getAttribute('data-id-jogo'), 10);
                const j = jogosCache.find((x) => Number(x.id_jogo) === id);
                if (!j) return;
                jogoEmEdicao = j;
                document.getElementById('edit-jogo-titulo').textContent = formatNomeJogo(j.nome_jogo) || 'Jogo';
                document.getElementById('edit-jogo-data').value = j.data_jogo || '';
                document.getElementById('edit-jogo-data').min = hojeISO();
                document.getElementById('edit-jogo-inicio').value = formatarHora(j.inicio_jogo) || '08:00';
                document.getElementById('edit-jogo-fim').value = formatarHora(j.termino_jogo || j.terminno_jogo) || '09:00';
                const sel = document.getElementById('edit-jogo-local');
                sel.value = locaisLista.some(l => String(l.id_local) === String(j.locais_id_local))
                    ? String(j.locais_id_local)
                    : (sel.options[0]?.value ?? '');
                const modal = new bootstrap.Modal(document.getElementById('modalEditarJogoAgenda'));
                modal.show();
            });
        });
    }

    function atualizarTelas() {
        gerarCalendarioVisual();
        gerarCalendarioMobile();
        atualizarSelects();
        renderListaEventos();
    }

    function inicializarAnos() {
        const selectAno = document.getElementById('select-ano');
        if (!selectAno || !selectAno.isConnected) return;
        const anoAtual = new Date().getFullYear();
        selectAno.innerHTML = '';
        for (let i = anoAtual - 2; i <= anoAtual + 3; i++) {
            selectAno.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }

    function atualizarSelects() {
        const selectMes = document.getElementById('select-mes');
        const selectAno = document.getElementById('select-ano');
        if (selectMes && selectMes.isConnected) selectMes.value = dataNavegacao.getMonth();
        if (selectAno && selectAno.isConnected) selectAno.value = dataNavegacao.getFullYear();
    }

    function gerarCalendarioVisual() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date();
        const nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const tituloMes = document.getElementById('calendario-mes');
        const grade = document.getElementById('calendario-grade');
        if (!tituloMes || !grade || !tituloMes.isConnected || !grade.isConnected) return;
        tituloMes.innerText = nomesMeses[mesNavegacao] + ' ' + anoNavegacao;
        grade.innerHTML = '';
        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();
        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div class="ag-cal-day ag-cal-day--empty"></div>`;
        }
        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear();
            const temEvt = temJogoNoDia(anoNavegacao, mesNavegacao, dia);
            const dataStr = `${anoNavegacao}-${String(mesNavegacao + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
            const isSelecionado = filtroData === dataStr;
            let classes = 'ag-cal-day';
            if (isHoje) classes += ' ag-cal-day--today';
            if (temEvt) classes += ' ag-cal-day--has-game';
            if (isSelecionado) classes += ' ag-cal-day--selected';
            grade.innerHTML += `<div class="${classes}" data-date="${dataStr}">${dia}</div>`;
        }
    }

    function gerarCalendarioMobile() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date();
        const grade = document.getElementById('calendario-grade-mobile');
        if (!grade || !grade.isConnected) return;
        grade.innerHTML = '';
        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();
        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div class="ag-cal-day ag-cal-day--empty"></div>`;
        }
        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear();
            const temEvt = temJogoNoDia(anoNavegacao, mesNavegacao, dia);
            const dataStr = `${anoNavegacao}-${String(mesNavegacao + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
            const isSelecionado = filtroData === dataStr;
            let classes = 'ag-cal-day';
            if (isHoje) classes += ' ag-cal-day--today';
            if (temEvt) classes += ' ag-cal-day--has-game';
            if (isSelecionado) classes += ' ag-cal-day--selected';
            grade.innerHTML += `<div class="${classes}" data-date="${dataStr}">${dia}</div>`;
        }
    }

    function preencherSelectModalidades() {
        const desk = document.getElementById('agenda-select-mod');
        const mob = document.getElementById('agenda-select-mod-mobile');
        const autoSel = document.getElementById('auto-modalidade');

        if (desk && mob) {
            const cur = desk.value;
            desk.innerHTML = '';
            mob.innerHTML = '';
            const o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = 'Todas as modalidades';
            desk.appendChild(o0);
            const o0m = document.createElement('option');
            o0m.value = '';
            o0m.textContent = 'Todas';
            mob.appendChild(o0m);

            modalidadesLista.forEach((m) => {
                const t = `${m.nome_modalidade || ''} (${m.nome_categoria || ''})`;
                const o1 = document.createElement('option');
                o1.value = String(m.id_modalidade);
                o1.textContent = t;
                desk.appendChild(o1);
                const o2 = document.createElement('option');
                o2.value = String(m.id_modalidade);
                o2.textContent = t;
                mob.appendChild(o2);
            });
            if (cur && [...desk.options].some((op) => op.value === cur)) {
                desk.value = cur;
                mob.value = cur;
            }
        }

        if (autoSel) {
            autoSel.innerHTML = '';
            modalidadesLista.forEach((m) => {
                const t = `${m.nome_modalidade || ''} (${m.nome_categoria || ''})`;
                const opt = document.createElement('option');
                opt.value = String(m.id_modalidade);
                opt.textContent = t;
                autoSel.appendChild(opt);
            });
        }
    }

    async function carregarLocais() {
        if (!interclasseAtual || !interclasseAtual.id_interclasse) return;

        const res = await fetch(`${API}locais?id_interclasse=${encodeURIComponent(interclasseAtual.id_interclasse)}`);
        const data = await res.json();
        
        let todosLocais = data && Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : [];
        
        locaisLista = todosLocais.filter((loc) => String(loc.disponivel_local) === '1');

        const sel = document.getElementById('edit-jogo-local');
        const autoLoc = document.getElementById('auto-local');

        if (sel) sel.innerHTML = '';
        if (autoLoc) autoLoc.innerHTML = '';

        if (locaisLista.length === 0) {
            if (sel) sel.innerHTML = '<option value="">Nenhum local disponível</option>';
            if (autoLoc) autoLoc.innerHTML = '<option value="">Nenhum local disponível</option>';
            return;
        }

        locaisLista.forEach((loc) => {
            const optionHtml = `<option value="${loc.id_local}">${escapeHtml(loc.nome_local || 'Local')}</option>`;
            if (sel) sel.innerHTML += optionHtml;
            if (autoLoc) autoLoc.innerHTML += optionHtml;
        });
    }

    /* Helper para cálculo e ordenação dos jogos pelo chaveamento */
    function ordenarJogosChaveamento(jogos) {
        return jogos.sort((a, b) => {
            const mmA = (a.nome_jogo || '').match(/^MM:(\d+):(\d+):([NB])$/);
            const mmB = (b.nome_jogo || '').match(/^MM:(\d+):(\d+):([NB])$/);

            if (mmA && mmB) {
                const largA = parseInt(mmA[1], 10);
                const largB = parseInt(mmB[1], 10);
                const slotA = parseInt(mmA[2], 10);
                const slotB = parseInt(mmB[2], 10);

                if (largA !== largB) return largB - largA; // Maior largura primeiro (ex: 16 -> 8 -> 4 -> 2)
                return slotA - slotB;
            }
            return (a.id_jogo || 0) - (b.id_jogo || 0);
        });
    }

    window.SGIPage.ready( async function () {
        try {
            interclasseAtual = await getInterclasseParaAgenda();
            if (interclasseAtual) {
                const nomeInterclasse = document.getElementById('nomeInterclasseAgenda');
                const btnVoltar = document.getElementById('btnVoltarAgendaDesk');
                if (nomeInterclasse && nomeInterclasse.isConnected) {
                    nomeInterclasse.innerText = interclasseAtual.nome_interclasse;
                }
                if (btnVoltar && btnVoltar.isConnected) {
                    btnVoltar.href = `/painel?id=${interclasseAtual.id_interclasse}`;
                }
            }
        } catch (e) {
            console.error(e);
        }

        inicializarAnos();
        
        try {
            await carregarLocais();
            await carregarJogosDoInterclasse();
            preencherSelectModalidades();
        } catch (e) {
            console.error(e);
        }
        
        atualizarTelas();

        function navegarMes(delta) {
            dataNavegacao.setMonth(dataNavegacao.getMonth() + delta);
            filtroData = null;
            atualizarTelas();
        }

        const el1 = document.getElementById('btn-prev');
        if (el1) pageScope.listen(el1, 'click', () => navegarMes(-1));
        const el2 = document.getElementById('btn-next');
        if (el2) pageScope.listen(el2, 'click', () => navegarMes(1));
        const el3 = document.getElementById('btn-prev-mobile');
        if (el3) pageScope.listen(el3, 'click', () => navegarMes(-1));
        const el4 = document.getElementById('btn-next-mobile');
        if (el4) pageScope.listen(el4, 'click', () => navegarMes(1));
        const el5 = document.getElementById('select-mes');
        if (el5) pageScope.listen(el5, 'change', (e) => {
            dataNavegacao.setMonth(parseInt(e.target.value, 10));
            filtroData = null;
            atualizarTelas();
        });
        const el6 = document.getElementById('select-ano');
        if (el6) pageScope.listen(el6, 'change', (e) => {
            dataNavegacao.setFullYear(parseInt(e.target.value, 10));
            filtroData = null;
            atualizarTelas();
        });

        function aplicarFiltroData(dataStr) {
            filtroData = filtroData === dataStr ? null : dataStr;
            atualizarTelas();
        }

        const gradeDesk = document.getElementById('calendario-grade');
        if (gradeDesk) pageScope.listen(gradeDesk, 'click', (e) => {
            const target = e.target.closest('[data-date]');
            if (target) aplicarFiltroData(target.dataset.date);
        });
        const gradeMob = document.getElementById('calendario-grade-mobile');
        if (gradeMob) pageScope.listen(gradeMob, 'click', (e) => {
            const target = e.target.closest('[data-date]');
            if (target) aplicarFiltroData(target.dataset.date);
        });

        const limparFiltro = () => {
            if (filtroData) {
                filtroData = null;
                atualizarTelas();
            }
        };
        const el7 = document.getElementById('btn-mostrar-todos');
        if (el7) pageScope.listen(el7, 'click', limparFiltro);
        const el8 = document.getElementById('btn-mostrar-todos-mobile');
        if (el8) pageScope.listen(el8, 'click', limparFiltro);

        const elMod = document.getElementById('agenda-select-mod');
        if (elMod) pageScope.listen(elMod, 'change', () => {
            syncSelectModalidade(true);
            filtroData = null;
            atualizarTelas();
        });
        const elModMob = document.getElementById('agenda-select-mod-mobile');
        if (elModMob) pageScope.listen(elModMob, 'change', () => {
            syncSelectModalidade(false);
            filtroData = null;
            atualizarTelas();
        });

        const elStatus = document.getElementById('agenda-select-status');
        if (elStatus) pageScope.listen(elStatus, 'change', () => {
            syncSelectStatus(true);
            filtroStatus = elStatus.value;
            filtroData = null;
            atualizarTelas();
        });
        const elStatusMob = document.getElementById('agenda-select-status-mobile');
        if (elStatusMob) pageScope.listen(elStatusMob, 'change', () => {
            syncSelectStatus(false);
            filtroStatus = elStatusMob.value;
            filtroData = null;
            atualizarTelas();
        });

        const elBusca = document.getElementById('agenda-busca');
        if (elBusca) pageScope.listen(elBusca, 'input', (e) => {
            setBusca(e.target.value.trim());
            filtroData = null;
            atualizarTelas();
        });
        const elBuscaMob = document.getElementById('agenda-busca-mobile');
        if (elBuscaMob) pageScope.listen(elBuscaMob, 'input', (e) => {
            setBusca(e.target.value.trim());
            filtroData = null;
            atualizarTelas();
        });

        /* ── SALVAR EDIÇÃO INDIVIDUAL ── */
        const elSalvar = document.getElementById('edit-jogo-salvar');
        if (elSalvar) pageScope.listen(elSalvar, 'click', async () => {
            if (!jogoEmEdicao) return;
            const data = document.getElementById('edit-jogo-data').value;
            if (data < hojeISO()) {
                alert('Não é permitido agendar um jogo para uma data passada.');
                return;
            }
            const ini = document.getElementById('edit-jogo-inicio').value;
            const fim = document.getElementById('edit-jogo-fim').value;
            const idLocal = parseInt(document.getElementById('edit-jogo-local').value, 10);
            const body = {
                id_jogo: Number(jogoEmEdicao.id_jogo, 10),
                data_jogo: data,
                inicio_jogo: ini ? (ini.length === 5 ? `${ini}:00` : ini) : '00:00:00',
                termino_jogo: fim ? (fim.length === 5 ? `${fim}:00` : fim) : '00:00:00',
                locais_id_local: idLocal
            };
            try {
                const r = await fetch(`${API}jogos`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const js = await r.json();
                if (!r.ok || js.success === false) throw new Error(js.message || 'Erro ao salvar');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarJogoAgenda')).hide();
                await carregarJogosDoInterclasse();
                atualizarTelas();
            } catch (e) {
                alert(e.message || 'Erro ao salvar.');
            }
        });

        /* ── ABRIR MODAL DATAS AUTOMÁTICAS ── */
        document.querySelectorAll('.btn-trigger-datas-auto').forEach((btn) => {
            pageScope.listen(btn, 'click', () => {
                const autoData = document.getElementById('auto-data');
                if (autoData) {
                    autoData.value = hojeISO();
                    autoData.min = hojeISO();
                }
                const modalMod = document.getElementById('auto-modalidade');
                const selModGlobal = modalidadeSelecionadaId();
                if (modalMod && selModGlobal) {
                    modalMod.value = selModGlobal;
                }
                const modal = new bootstrap.Modal(document.getElementById('modalDatasAutomaticas'));
                modal.show();
            });
        });

       /* ── GERAR E APLICAR DATAS AUTOMÁTICAS (EM LOTE) ── */
const btnAutoSalvar = document.getElementById('auto-salvar-btn');
if (btnAutoSalvar) {
    pageScope.listen(btnAutoSalvar, 'click', async () => {
        const idMod = document.getElementById('auto-modalidade').value;
        const dataSel = document.getElementById('auto-data').value;
        const horaInicio = document.getElementById('auto-inicio').value;
        const duracaoMin = parseInt(document.getElementById('auto-duracao').value, 10);
        const idLocal = parseInt(document.getElementById('auto-local').value, 10);

        if (!idMod) {
            alert('Por favor, selecione uma modalidade.');
            return;
        }
        if (!dataSel || dataSel < hojeISO()) {
            alert('Por favor, escolha uma data válida (de hoje em diante).');
            return;
        }
        if (!horaInicio) {
            alert('Por favor, informe o horário inicial.');
            return;
        }
        if (isNaN(duracaoMin) || duracaoMin <= 0) {
            alert('Por favor, informe uma duração válida em minutos.');
            return;
        }

        // Filtrar jogos agendados da modalidade
        let jogosMod = jogosCache.filter(j =>
            String(j.modalidades_id_modalidade) === String(idMod) &&
            j.status_jogo === 'Agendado'
        );

        if (jogosMod.length === 0) {
            alert('Nenhum jogo agendado encontrado para esta modalidade.');
            return;
        }

        jogosMod = ordenarJogosChaveamento(jogosMod);

        btnAutoSalvar.disabled = true;
        btnAutoSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processando...';

        try {
            let [h, m] = horaInicio.split(':').map(Number);
            let dataAtual = new Date(`${dataSel}T${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:00`);
            
            let alteradosComSucesso = 0;
            let houveErroTurno = false;
            let errosOutros = [];

            // Executa requisições em sequência
            for (const jogo of jogosMod) {
                const inicioStr = `${String(dataAtual.getHours()).padStart(2, '0')}:${String(dataAtual.getMinutes()).padStart(2, '0')}:00`;
                
                dataAtual.setMinutes(dataAtual.getMinutes() + duracaoMin);
                const terminoStr = `${String(dataAtual.getHours()).padStart(2, '0')}:${String(dataAtual.getMinutes()).padStart(2, '0')}:00`;

                const body = {
                    id_jogo: Number(jogo.id_jogo),
                    data_jogo: dataSel,
                    inicio_jogo: inicioStr,
                    termino_jogo: terminoStr,
                    locais_id_local: idLocal
                };

                const resp = await fetch(`${API}jogos`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                
                const resJson = await resp.json();
                if (resp.ok && resJson.success !== false) {
                    alteradosComSucesso++;
                } else {
                    // Trata mensagens vindas da validação de turno da API
                    const msgErro = resJson.message || '';
                    if (resp.status === 422 && (msgErro.includes('excede o turno') || msgErro.includes('turno'))) {
                        houveErroTurno = true;
                    } else if (msgErro) {
                        errosOutros.push(msgErro);
                    }
                }
            }

            bootstrap.Modal.getInstance(document.getElementById('modalDatasAutomaticas')).hide();
            await carregarJogosDoInterclasse();
            
            // Navega para o mês selecionado e remove filtro por dia
            const [anoA, mesA] = dataSel.split('-').map(Number);
            dataNavegacao.setFullYear(anoA);
            dataNavegacao.setMonth(mesA - 1);
            filtroData = null; 
  
            atualizarTelas();

            // Montagem da mensagem personalizada
            if (houveErroTurno) {
                alert(`⚠️ Não foi possível agendar todas as partidas dessa forma porque o horário total ultrapassa o período de aula/turno das turmas envolvidas.\n\n${alteradosComSucesso} de ${jogosMod.length} jogo(s) puderam ser agendados. Tente reduzir o tempo de partida ou iniciar mais cedo.`);
            } else if (errosOutros.length > 0) {
                alert(`Aviso: ${alteradosComSucesso} de ${jogosMod.length} jogo(s) foram reagendados.\nMotivo: ${errosOutros[0]}`);
            } else {
                alert(`Sucesso! Todos os ${alteradosComSucesso} jogos foram reagendados para ${dataSel}!`);
            }

        } catch (e) {
            alert('Ocorreu um erro ao atualizar os jogos: ' + (e.message || e));
        } finally {
            btnAutoSalvar.disabled = false;
            btnAutoSalvar.innerHTML = '<i class="bi bi-check-lg me-1"></i>Gerar e Aplicar Datas';
        }
    });
}
    });
})();

return {};
});
