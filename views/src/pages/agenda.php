<?php
$tituloPagina = 'SGI - Mesário - Agenda';
$titulo = 'Agenda';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'agenda';
?>


<main class="bg-light d-md-none p-3" style="padding-top: 5rem; padding-bottom: 5.5rem;">
    <div class="card border-0 shadow-sm rounded-4 mx-auto mb-4" style="max-width: 450px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button type="button" id="btn-prev-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-left" style="font-size: 1.2rem;"></i>
                </button>
                <div class="d-flex gap-2">
                    <select id="select-mes" class="form-select form-select-sm border-0 bg-light text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
                        <option value="0">Jan</option>
                        <option value="1">Fev</option>
                        <option value="2">Mar</option>
                        <option value="3">Abr</option>
                        <option value="4">Mai</option>
                        <option value="5">Jun</option>
                        <option value="6">Jul</option>
                        <option value="7">Ago</option>
                        <option value="8">Set</option>
                        <option value="9">Out</option>
                        <option value="10">Nov</option>
                        <option value="11">Dez</option>
                    </select>
                    <select id="select-ano" class="form-select form-select-sm border-0 bg-light text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
                    </select>
                </div>
                <button type="button" id="btn-next-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-right" style="font-size: 1.2rem;"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between text-muted mb-2 text-center" style="font-size: 0.85rem;">
                <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                <span style="width: 14%;">S</span>
            </div>
            <div id="calendario-grade-mobile" class="d-flex flex-wrap text-center"></div>
        </div>
    </div>

    <div id="lista-eventos-mobile" class="d-flex flex-column gap-3 mx-auto px-1" style="max-width: 450px;"></div>

    <div class="d-flex justify-content-center mt-3 mb-3">
        <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">Abrir no Google Calendar</a>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout" style="padding-bottom: 5rem;">
    <a href="./dashboard.php" class="btn btn-danger d-inline-flex align-items-center mb-3 border-0 shadow-sm text-decoration-none" style="border-radius: 4px; padding: 8px 15px;" id="btnVoltarAgendaDesk">
        <i class="bi bi-arrow-left-circle me-2"></i>
        <span style="font-size: 0.9rem; font-weight: 400;" id="nomeInterclasseAgenda">Interclasse</span>
    </a>

    <div class="row">
        <div class="col-lg-6 pe-lg-5">
            <h2 class="text-dark mb-4 d-flex align-items-center gap-2" style="font-weight: 400;">
                <i class="bi bi-calendar3"></i> Agenda
            </h2>
            <div id="lista-eventos" class="d-flex flex-column gap-3"></div>
        </div>
        <div class="col-lg-6 d-flex justify-content-center align-items-start mt-5 mt-lg-0">
            <div class="bg-white shadow-sm rounded-3 overflow-hidden" style="width: 100%; max-width: 380px;">
                <div class="bg-dark text-white d-flex justify-content-between align-items-center py-3 px-4 text-uppercase" style="letter-spacing: 3px; font-weight: 400;">
                    <i class="bi bi-chevron-left" id="btn-prev" style="cursor: pointer; font-size: 1.2rem;"></i>
                    <span id="calendario-mes"></span>
                    <i class="bi bi-chevron-right" id="btn-next" style="cursor: pointer; font-size: 1.2rem;"></i>
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between text-dark mb-3 text-center" style="font-weight: 400;">
                        <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                        <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                        <span style="width: 14%;">S</span>
                    </div>
                    <div id="calendario-grade" class="d-flex flex-wrap text-center"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    const API = '../../../api/';
    let dataNavegacao = new Date();
    const params = new URLSearchParams(window.location.search);
    const idInterclasseAgenda = params.get('id');

    let interclasseAtual = null;
    let jogosCache = [];
    let modalidadesLista = [];
    let locaisLista = [];

    function formatNomeJogo(nomeJogo) {
        const mm = (nomeJogo || '').match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 8: 'Oitavas de final', 4: 'Quartas de final', 2: 'Final', 1: 'Campeão' };
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

    function podeIniciar(j) {
        if (!j || j.status_jogo !== 'Agendado') return false;
        const hj = hojeISO();
        return j.data_jogo <= hj;
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

    async function getInterclasseParaAgenda() {
        if (idInterclasseAgenda) {
            const item = await window.SGIInterclasse.getInterclasseById(idInterclasseAgenda);
            if (item) return item;
        }
        return window.SGIInterclasse.getActiveInterclasse();
    }

    async function carregarJogosDoInterclasse() {
        jogosCache = [];
        if (!interclasseAtual) return;
        const resMod = await fetch(`${API}modalidades.php`);
        if (!resMod.ok) throw new Error('Falha ao carregar modalidades');
        const todasMods = await resMod.json();
        modalidadesLista = (Array.isArray(todasMods) ? todasMods : []).filter(
            (m) => String(m.interclasses_id_interclasse) === String(interclasseAtual.id_interclasse)
        );
        const ids = [...new Set(modalidadesLista.map((m) => m.id_modalidade).filter(Boolean))];
        if (ids.length === 0) return;
        const batches = await Promise.all(
            ids.map((id) =>
                fetch(`${API}jogos.php?id_modalidade=${encodeURIComponent(id)}`).then(async (r) => {
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
        jogosCache = Array.from(map.values());
    }

    function isJogoCampeao(nomeJogo) {
        return /^MM:1:\d+:[NB]$/.test(nomeJogo || '');
    }

    function jogosDoMesVisivel() {
        const y = dataNavegacao.getFullYear();
        const m = dataNavegacao.getMonth();
        const modF = modalidadeSelecionadaId();
        return jogosCache
            .filter((j) => {
                if (!j.data_jogo) return false;
                if (isJogoCampeao(j.nome_jogo)) return false;
                const [jy, jm] = j.data_jogo.split('-').map(Number);
                if (jy !== y || jm - 1 !== m) return false;
                if (modF && String(j.modalidades_id_modalidade) !== String(modF)) return false;
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
            if (isJogoCampeao(j.nome_jogo)) return false;
            if (modF && String(j.modalidades_id_modalidade) !== String(modF)) return false;
            return true;
        });
    }

    function montarCardJogo(j) {
        const dataObj = new Date(j.data_jogo + 'T12:00:00');
        const diaNum = dataObj.toLocaleDateString('pt-BR', { day: '2-digit' });
        const diaSem = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });
        const hi = formatarHora(j.inicio_jogo);
        const hf = formatarHora(j.termino_jogo || j.terminno_jogo);
        const horario = hi && hf ? `${hi} – ${hf}` : hi || 'Horário a definir';
        const placarHref = `./jogos.php?id_jogo=${encodeURIComponent(j.id_jogo)}`;
        const statusTxt = labelStatus(j.status_jogo);

        let acoesHtml = '';
        if (podeIniciar(j)) {
            acoesHtml += `<button type="button" class="btn btn-sm btn-success iniciar-jogo-btn" data-id-jogo="${j.id_jogo}">Iniciar jogo</button>`;
        }
        if (j.status_jogo === 'Iniciado' || j.status_jogo === 'Pausado') {
            acoesHtml += `<a class="btn btn-sm btn-outline-danger" href="${placarHref}" onclick="event.stopPropagation();">Placar</a>`;
        }
        if (j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado') {
            acoesHtml += `<a class="btn btn-sm btn-outline-secondary" href="${placarHref}" onclick="event.stopPropagation();">Ver resultado</a>`;
        }

        return `
            <a href="${placarHref}" class="text-decoration-none text-dark" style="display: block;">
                <div class="card bg-white border-0 shadow-sm rounded-3 p-3 position-relative w-100 mb-0 jogo-card-hover">
                    <div class="card-body p-0">
                        <h5 class="text-dark mb-1" style="font-weight: 400;">${escapeHtml(formatNomeJogo(j.nome_jogo))}</h5>
                        <p class="text-muted mb-1 small">${escapeHtml(j.nome_modalidade || '')} · ${escapeHtml(j.nome_local || '')}</p>
                        <p class="text-muted mb-1" style="font-size: 0.8rem;">${diaNum}, ${diaSem}</p>
                        <p class="text-muted mb-2" style="font-size: 0.8rem;">${horario}</p>
                        <p class="mb-2"><span class="badge bg-light text-dark border">${escapeHtml(statusTxt)}</span></p>
                        <div class="d-flex flex-wrap gap-2">
                            ${acoesHtml}
                        </div>
                    </div>
                </div>
            </a>`;
    }

    function renderListaEventos() {
        const containerDesk = document.getElementById('lista-eventos');
        const containerMob = document.getElementById('lista-eventos-mobile');
        const lista = jogosDoMesVisivel();

        containerDesk.innerHTML = '';
        containerMob.innerHTML = '';

        if (!interclasseAtual) {
            const msg = '<p class="text-muted text-center w-100">Nenhum interclasse selecionado ou ativo.</p>';
            containerDesk.innerHTML = msg;
            containerMob.innerHTML = msg;
            return;
        }

        if (lista.length === 0) {
            const msgVazia = '<p class="text-muted text-center w-100">Nenhum jogo neste mês.</p>';
            containerDesk.innerHTML = msgVazia;
            containerMob.innerHTML = msgVazia;
            return;
        }

        lista.forEach((j) => {
            const html = montarCardJogo(j);
            containerDesk.innerHTML += html;
            containerMob.innerHTML += html;
        });

        document.querySelectorAll('.iniciar-jogo-btn').forEach((btn) => {
            btn.addEventListener('click', async (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                const id = btn.getAttribute('data-id-jogo');
                try {
                    const r = await fetch(`${API}jogos.php`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_jogo: Number(id), status_jogo: 'Iniciado' })
                    });
                    const js = await r.json();
                    if (!r.ok || js.success === false) throw new Error(js.message || 'Falha ao iniciar');
                    await carregarJogosDoInterclasse();
                    atualizarTelas();
                } catch (e) {
                    alert(e.message || 'Erro ao iniciar o jogo.');
                }
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
        const anoAtual = new Date().getFullYear();
        selectAno.innerHTML = '';
        for (let i = anoAtual - 2; i <= anoAtual + 3; i++) {
            selectAno.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }

    function atualizarSelects() {
        document.getElementById('select-mes').value = dataNavegacao.getMonth();
        document.getElementById('select-ano').value = dataNavegacao.getFullYear();
    }

    function gerarCalendarioVisual() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date();
        const nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        document.getElementById('calendario-mes').innerText = nomesMeses[mesNavegacao] + ' ' + anoNavegacao;
        const grade = document.getElementById('calendario-grade');
        grade.innerHTML = '';
        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();
        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div style="width: 14%; height: 40px;"></div>`;
        }
        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear();
            const temEvt = temJogoNoDia(anoNavegacao, mesNavegacao, dia);
            const hojeClass = isHoje ? 'fw-bold text-danger border border-danger rounded-circle' : '';
            const marcaClass = temEvt ? ' cal-dia-marcado' : '';
            grade.innerHTML += `
            <div class="d-flex align-items-center justify-content-center ${hojeClass}${marcaClass}"
                 style="width: 14%; height: 40px; cursor: default; font-size: 0.9rem;">
                ${dia}
            </div>`;
        }
    }

    function gerarCalendarioMobile() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date();
        const grade = document.getElementById('calendario-grade-mobile');
        grade.innerHTML = '';
        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();
        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div style="width: 14%; height: 40px;"></div>`;
        }
        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear();
            const temEvt = temJogoNoDia(anoNavegacao, mesNavegacao, dia);
            const baseCirc = isHoje ? 'bg-dark text-white rounded-circle' : 'text-dark';
            const marcaClass = temEvt ? ' cal-dia-marcado' : '';
            grade.innerHTML += `
            <div class="d-flex align-items-center justify-content-center" style="width: 14%; height: 40px; margin-bottom: 5px;">
                <span class="${baseCirc}${marcaClass} d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 0.95rem;">
                    ${dia}
                </span>
            </div>`;
        }
    }

    function preencherSelectModalidades() {
        const desk = document.getElementById('agenda-select-mod');
        const mob = document.getElementById('agenda-select-mod-mobile');
        if (!desk || !mob) return;
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

    async function carregarLocais() {
        const res = await fetch(`${API}locais.php`);
        const data = await res.json();
        locaisLista = data && Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : [];
    }

    document.addEventListener('DOMContentLoaded', async function () {
        try {
            interclasseAtual = await getInterclasseParaAgenda();
            if (interclasseAtual) {
                document.getElementById('nomeInterclasseAgenda').innerText = interclasseAtual.nome_interclasse;
                document.getElementById('btnVoltarAgendaDesk').href = `./dashboard.php?id=${interclasseAtual.id_interclasse}`;
                window.SGIInterclasse.updatePageTitle(interclasseAtual.nome_interclasse);
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

        document.getElementById('btn-prev').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() - 1);
            atualizarTelas();
        });
        document.getElementById('btn-next').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() + 1);
            atualizarTelas();
        });
        document.getElementById('btn-prev-mobile').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() - 1);
            atualizarTelas();
        });
        document.getElementById('btn-next-mobile').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() + 1);
            atualizarTelas();
        });
        document.getElementById('select-mes').addEventListener('change', (e) => {
            dataNavegacao.setMonth(parseInt(e.target.value, 10));
            atualizarTelas();
        });
        document.getElementById('select-ano').addEventListener('change', (e) => {
            dataNavegacao.setFullYear(parseInt(e.target.value, 10));
            atualizarTelas();
        });

        const modDesk = document.getElementById('agenda-select-mod');
        const modMob = document.getElementById('agenda-select-mod-mobile');
        if (modDesk) {
            modDesk.addEventListener('change', () => {
                syncSelectModalidade(true);
                atualizarTelas();
            });
        }
        if (modMob) {
            modMob.addEventListener('change', () => {
                syncSelectModalidade(false);
                atualizarTelas();
            });
        }
    });
})();
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
