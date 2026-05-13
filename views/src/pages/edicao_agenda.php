<?php
$titulo = "Agenda";
$textTop = "Agenda";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .fab-novo-jogo {
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: 1040;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    }
    .cal-dia-marcado {
        box-shadow: inset 0 -3px 0 var(--inter-red, #ed1c24);
        font-weight: 600;
    }
    .cal-dia-hoje.cal-dia-marcado {
        border-radius: 50%;
    }
</style>

<main class="bg-light d-md-none p-3" style="padding-top: 5rem; padding-bottom: 5.5rem;">
    <div class="card border-0 shadow-sm rounded-4 mx-auto mb-4" style="max-width: 450px;">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <button type="button" id="btn-prev-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-left" style="font-size: 1.2rem;"></i>
                </button>

                <div class="d-flex gap-2">
                    <select id="select-mes" class="form-select form-select-sm border-0 bg-light fw-bold text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
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

                    <select id="select-ano" class="form-select form-select-sm border-0 bg-light fw-bold text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
                    </select>
                </div>

                <button type="button" id="btn-next-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-right" style="font-size: 1.2rem;"></i>
                </button>
            </div>

            <div class="d-flex justify-content-between text-muted mb-2 text-center fw-bold" style="font-size: 0.85rem;">
                <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                <span style="width: 14%;">S</span>
            </div>

            <div id="calendario-grade-mobile" class="d-flex flex-wrap text-center">
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-center mb-3">
        <a
            href="https://calendar.google.com"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-outline-danger btn-sm"
        >
            Abrir no Google Calendar
        </a>
    </div>
    <div id="lista-eventos-mobile" class="d-flex flex-column gap-3 mx-auto" style="max-width: 450px;"></div>
    <div id="barraContinuarAgendaMobile" class="d-none position-fixed start-0 end-0 bottom-0 p-3 bg-light border-top shadow-sm" style="z-index: 1030; padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px)) !important;">
        <a href="#" id="btnContinuarAgendaMobile" class="btn btn-danger w-100 fw-semibold rounded-3 py-2 shadow-sm text-decoration-none d-flex align-items-center justify-content-center">Continuar</a>
    </div>
</main>


<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout" style="padding-bottom: 5rem;">

    <a href="./home.php" data-back-link="true" class="btn btn-danger d-inline-flex align-items-center mb-4 border-0 shadow-sm text-decoration-none" style="border-radius: 4px; padding: 8px 15px;" id="btnVoltarAgendaDesk">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>
        <span class="fw-bold" style="font-size: 0.9rem;" id="nomeInterclasseAgenda">Interclasse</span>
    </a>

    <div class="row">
        <div class="col-lg-6 pe-lg-5">
            <h2 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-calendar3"></i> Agenda
            </h2>

            <div id="lista-eventos" class="d-flex flex-column gap-3">
            </div>
        </div>

        <div class="col-lg-6 d-flex justify-content-center align-items-start mt-5 mt-lg-0">
            <div class="bg-white shadow-sm rounded-3 overflow-hidden" style="width: 100%; max-width: 380px;">
                <div class="bg-dark text-white d-flex justify-content-between align-items-center py-3 px-4 fw-bold text-uppercase" style="letter-spacing: 3px;">
                    <i class="bi bi-chevron-left" id="btn-prev" style="cursor: pointer; font-size: 1.2rem;"></i>
                    <span id="calendario-mes"></span>
                    <i class="bi bi-chevron-right" id="btn-next" style="cursor: pointer; font-size: 1.2rem;"></i>
                </div>

                <div class="p-4">
                    <div class="d-flex justify-content-between text-dark fw-bold mb-3 text-center">
                        <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                        <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                        <span style="width: 14%;">S</span>
                    </div>

                    <div id="calendario-grade" class="d-flex flex-wrap text-center">
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="barraContinuarAgendaDesktop" class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, #f8f9fa 70%, rgba(248, 249, 250, 0) 100%); padding: 24px 0; z-index: 1020;">
    <div class="container-fluid d-flex justify-content-end align-items-center" style="max-width: 80%; margin-left: auto; margin-right: 0;">
        <a href="#" id="btnContinuarAgendaDesktop" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm text-decoration-none d-flex align-items-center justify-content-center">Continuar</a>
    </div>
</div>

<button type="button" class="btn btn-danger fab-novo-jogo d-flex align-items-center justify-content-center border-0" data-bs-toggle="modal" data-bs-target="#modalNovoJogo" title="Novo jogo">
    <i class="bi bi-plus-lg fs-3"></i>
</button>

<div class="modal fade" id="modalNovoJogo" tabindex="-1" aria-labelledby="modalNovoJogoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalNovoJogoTitulo">Novo jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-3">Obrigatório: modalidade, equipes, data e local. Horários opcionais. Cadastre locais em <a href="#" id="nj_linkLocais" class="fw-semibold text-danger">Locais do interclasse</a> antes de criar jogos.</p>
                <form id="formNovoJogo" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_modalidade">Modalidade</label>
                        <select class="form-select" id="nj_modalidade" required>
                            <option value="">Carregando…</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_equipe_a">Equipe A</label>
                        <select class="form-select" id="nj_equipe_a" required disabled>
                            <option value="">Selecione uma modalidade primeiro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_equipe_b">Equipe B</label>
                        <select class="form-select" id="nj_equipe_b" required disabled>
                            <option value="">Selecione uma modalidade primeiro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_data">Data</label>
                        <input type="date" class="form-control" id="nj_data" name="data_jogo" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_inicio">Início</label>
                        <input type="time" class="form-control" id="nj_inicio" name="inicio_jogo" value="08:00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_fim">Término</label>
                        <input type="time" class="form-control" id="nj_fim" name="terminno_jogo" value="09:00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold" for="nj_local">Local</label>
                        <select class="form-select" id="nj_local" required>
                            <option value="">Carregando…</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="nj_submit">Salvar jogo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    let dataNavegacao = new Date();
    const params = new URLSearchParams(window.location.search);
    const idInterclasseAgenda = params.get('id');
    const modoAgenda = params.get('modo');

    let interclasseAtual = null;
    let jogosCache = [];

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

    function labelStatus(status) {
        const map = {
            'Agendado': 'Agendado',
            'Iniciado': 'Em andamento',
            'Pausado': 'Pausado',
            'Concluido': 'Concluído',
            'Finalizado': 'Concluído'
        };
        return map[status] || status || '—';
    }

    function podeIniciar(j) {
        if (!j || j.status_jogo !== 'Agendado') return false;
        const hj = hojeISO();
        return j.data_jogo <= hj;
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
        const mods = (Array.isArray(todasMods) ? todasMods : []).filter(
            (m) => String(m.interclasses_id_interclasse) === String(interclasseAtual.id_interclasse)
        );
        const ids = [...new Set(mods.map((m) => m.id_modalidade).filter(Boolean))];
        if (ids.length === 0) return;
        const batches = await Promise.all(ids.map((id) =>
            fetch(`${API}jogos.php?id_modalidade=${encodeURIComponent(id)}`).then((r) => r.json())
        ));
        const map = new Map();
        batches.flat().forEach((j) => {
            if (j && j.id_jogo != null) map.set(String(j.id_jogo), j);
        });
        jogosCache = Array.from(map.values());
    }

    function jogosDoMesVisivel() {
        const y = dataNavegacao.getFullYear();
        const m = dataNavegacao.getMonth();
        return jogosCache.filter((j) => {
            if (!j.data_jogo) return false;
            const [jy, jm] = j.data_jogo.split('-').map(Number);
            return jy === y && (jm - 1) === m;
        }).sort((a, b) => {
            const da = `${a.data_jogo} ${a.inicio_jogo || ''}`;
            const db = `${b.data_jogo} ${b.inicio_jogo || ''}`;
            return da.localeCompare(db);
        });
    }

    function temJogoNoDia(ano, mesZeroBased, dia) {
        const m = String(mesZeroBased + 1).padStart(2, '0');
        const d = String(dia).padStart(2, '0');
        const key = `${ano}-${m}-${d}`;
        return jogosCache.some((j) => j.data_jogo === key);
    }

    function montarCardJogo(j) {
        const dataObj = new Date(j.data_jogo + 'T12:00:00');
        const diaNum = dataObj.toLocaleDateString('pt-BR', { day: '2-digit' });
        const diaSem = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });
        const hi = formatarHora(j.inicio_jogo);
        const hf = formatarHora(j.terminno_jogo);
        const horario = hi && hf ? `${hi} – ${hf}` : (hi || 'Horário a definir');
        const placarHref = `./jogos.php?id_jogo=${encodeURIComponent(j.id_jogo)}`;
        const statusTxt = labelStatus(j.status_jogo);
        const iniciarBtn = podeIniciar(j)
            ? `<button type="button" class="btn btn-sm btn-danger iniciar-jogo-btn" data-id-jogo="${j.id_jogo}">Iniciar jogo</button>`
            : '';
        const placarBtn = (j.status_jogo === 'Iniciado' || j.status_jogo === 'Pausado')
            ? `<a class="btn btn-sm btn-outline-danger" href="${placarHref}">Placar</a>`
            : '';
        const verBtn = (j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado')
            ? `<a class="btn btn-sm btn-outline-secondary" href="${placarHref}">Ver resultado</a>`
            : '';

        return `
            <div class="card bg-white border-0 shadow-sm rounded-3 p-3 position-relative w-100 mb-0">
                <i class="bi bi-record-circle text-danger position-absolute top-0 end-0 m-3 fs-5"></i>
                <div class="card-body p-0">
                    <h5 class="fw-bold text-dark mb-1">${escapeHtml(j.nome_jogo || 'Jogo')}</h5>
                    <p class="text-muted mb-1 small">${escapeHtml(j.nome_modalidade || '')} · ${escapeHtml(j.nome_local || '')}</p>
                    <p class="text-muted mb-1" style="font-size: 0.8rem;">${diaNum}, ${diaSem}</p>
                    <p class="text-muted mb-2" style="font-size: 0.8rem;">${horario}</p>
                    <p class="mb-2"><span class="badge bg-light text-dark border">${escapeHtml(statusTxt)}</span></p>
                    <div class="d-flex flex-wrap gap-2">
                        ${iniciarBtn}
                        ${placarBtn}
                        ${verBtn}
                    </div>
                </div>
                <i class="bi bi-alarm text-muted position-absolute bottom-0 end-0 m-3" style="font-size: 1rem;"></i>
            </div>`;
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
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

        const nomesMeses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        document.getElementById('calendario-mes').innerText = nomesMeses[mesNavegacao] + ' ' + anoNavegacao;

        const grade = document.getElementById('calendario-grade');
        grade.innerHTML = '';

        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();

        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div style="width: 14%; height: 40px;"></div>`;
        }

        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = (dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear());
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
            const isHoje = (dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear());
            const temEvt = temJogoNoDia(anoNavegacao, mesNavegacao, dia);
            const baseCirc = isHoje ? 'bg-dark text-white rounded-circle' : 'text-dark';
            const marcaClass = temEvt ? ' cal-dia-marcado' : '';

            grade.innerHTML += `
            <div class="d-flex align-items-center justify-content-center" style="width: 14%; height: 40px; margin-bottom: 5px;">
                <span class="${baseCirc}${marcaClass} d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 0.95rem;">
                    ${dia}
                </span>
            </div>`;
        }
    }

    async function preencherSelectModalidades() {
        const sel = document.getElementById('nj_modalidade');
        if (!interclasseAtual) {
            sel.innerHTML = '<option value="">Sem interclasse</option>';
            return;
        }
        const res = await fetch(`${API}modalidades.php`);
        const data = await res.json();
        const lista = (Array.isArray(data) ? data : []).filter(
            (m) => String(m.interclasses_id_interclasse) === String(interclasseAtual.id_interclasse)
        );
        sel.innerHTML = '<option value="">Selecione…</option>';
        lista.forEach((m) => {
            sel.innerHTML += `<option value="${m.id_modalidade}">${escapeHtml(m.nome_modalidade)} (${escapeHtml(m.nome_categoria || '')})</option>`;
        });
    }

    async function preencherSelectLocais() {
        const sel = document.getElementById('nj_local');
        const res = await fetch(`${API}locais.php`);
        const data = await res.json();
        const lista = (data && Array.isArray(data.data)) ? data.data : (Array.isArray(data) ? data : []);
        sel.innerHTML = '<option value="">Selecione…</option>';
        lista.forEach((loc) => {
            const id = loc.id_local;
            sel.innerHTML += `<option value="${id}">${escapeHtml(loc.nome_local || 'Local')}</option>`;
        });
    }

    async function preencherSelectEquipes(modalidadeId) {
        const selA = document.getElementById('nj_equipe_a');
        const selB = document.getElementById('nj_equipe_b');
        if (!modalidadeId) {
            selA.innerHTML = '<option value="">Selecione uma modalidade primeiro</option>';
            selB.innerHTML = '<option value="">Selecione uma modalidade primeiro</option>';
            selA.disabled = true;
            selB.disabled = true;
            return;
        }
        const res = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(modalidadeId)}`);
        const data = await res.json();
        const lista = Array.isArray(data) ? data : [];
        selA.innerHTML = '<option value="">Selecione…</option>';
        selB.innerHTML = '<option value="">Selecione…</option>';
        lista.forEach((eq) => {
            const nome = eq.nome_turma || `Equipe ${eq.id_equipe}`;
            selA.innerHTML += `<option value="${eq.id_equipe}">${escapeHtml(nome)}</option>`;
            selB.innerHTML += `<option value="${eq.id_equipe}">${escapeHtml(nome)}</option>`;
        });
        selA.disabled = false;
        selB.disabled = false;
    }

    document.addEventListener('DOMContentLoaded', async function() {
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

        if (modoAgenda === 'view' && idInterclasseAgenda) {
            const hrefDash = `./dashboard.php?id=${idInterclasseAgenda}`;
            const btnMob = document.getElementById('btnContinuarAgendaMobile');
            btnMob.href = hrefDash;
            document.getElementById('barraContinuarAgendaMobile').classList.remove('d-none');
            const btnDesk = document.getElementById('btnContinuarAgendaDesktop');
            btnDesk.href = hrefDash;
            btnDesk.classList.remove('d-none');
            document.getElementById('barraContinuarAgendaDesktop').classList.remove('d-none');
        }

        inicializarAnos();
        try {
            await carregarJogosDoInterclasse();
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

        if (interclasseAtual) {
            const elLink = document.getElementById('nj_linkLocais');
            if (elLink) elLink.href = `./edicao_locais.php?id=${interclasseAtual.id_interclasse}`;
        }

        const modalEl = document.getElementById('modalNovoJogo');
        modalEl.addEventListener('show.bs.modal', () => {
            preencherSelectModalidades().catch(console.error);
            preencherSelectLocais().catch(console.error);
            preencherSelectEquipes('').catch(console.error);
            const hoje = hojeISO();
            document.getElementById('nj_data').value = hoje;
        });

        document.getElementById('nj_modalidade').addEventListener('change', (e) => {
            const modalidadeId = e.target.value;
            preencherSelectEquipes(modalidadeId).catch(console.error);
        });

        document.getElementById('formNovoJogo').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!interclasseAtual) {
                alert('Não há interclasse ativo para vincular o jogo.');
                return;
            }
            const equipeA = document.getElementById('nj_equipe_a').value;
            const equipeB = document.getElementById('nj_equipe_b').value;
            if (equipeA === equipeB) {
                alert('Selecione equipes diferentes.');
                return;
            }
            const inicio = document.getElementById('nj_inicio').value;
            const fim = document.getElementById('nj_fim').value;
            const body = {
                data_jogo: document.getElementById('nj_data').value,
                inicio_jogo: inicio ? (inicio.length === 5 ? `${inicio}:00` : inicio) : '00:00:00',
                terminno_jogo: fim ? (fim.length === 5 ? `${fim}:00` : fim) : '00:00:00',
                modalidades_id_modalidade: parseInt(document.getElementById('nj_modalidade').value, 10),
                locais_id_local: parseInt(document.getElementById('nj_local').value, 10),
                equipe_a: parseInt(equipeA, 10),
                equipe_b: parseInt(equipeB, 10)
            };
            const btn = document.getElementById('nj_submit');
            btn.disabled = true;
            try {
                const r = await fetch(`${API}jogos.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const js = await r.json();
                if (!r.ok || js.success === false) {
                    throw new Error(js.message || js.detalhes || 'Erro ao criar jogo');
                }
                const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                inst.hide();
                document.getElementById('formNovoJogo').reset();
                await carregarJogosDoInterclasse();
                atualizarTelas();
            } catch (err) {
                alert(err.message || 'Erro ao salvar.');
            } finally {
                btn.disabled = false;
            }
        });
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
