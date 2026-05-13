<?php
$titulo = "Agenda";
$textTop = "Agenda";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .cal-dia-marcado {
        box-shadow: inset 0 -3px 0 var(--inter-red, #ed1c24);
        font-weight: 500;
    }
    .cal-dia-hoje.cal-dia-marcado {
        border-radius: 50%;
    }
</style>

<main class="bg-light d-md-none p-3" style="padding-top: 5rem; padding-bottom: 5.5rem;">
    <div class="card border-0 shadow-sm rounded-4 mx-auto mb-3" style="max-width: 450px;">
        <div class="card-body p-3">
            <label class="form-label small text-muted mb-1">Modalidade (chaveamento)</label>
            <select id="agenda-select-mod-mobile" class="form-select form-select-sm rounded-3 mb-2">
                <option value="">Todas</option>
            </select>
            <p id="chave-status-mobile" class="small text-muted mb-2"></p>
            <button type="button" id="btn-gerar-chave-mobile" class="btn btn-danger btn-sm w-100 rounded-3 mb-2" disabled>Gerar chaveamento</button>
        </div>
    </div>

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

    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-white">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Modalidade</label>
                <select id="agenda-select-mod" class="form-select rounded-3">
                    <option value="">Todas as modalidades</option>
                </select>
            </div>
            <div class="col-md-4">
                <p id="chave-status-desk" class="small text-muted mb-0"></p>
            </div>
            <div class="col-md-3 text-md-end">
                <button type="button" id="btn-gerar-chave" class="btn btn-danger rounded-3 px-4 w-100 w-md-auto" disabled>Gerar chaveamento</button>
            </div>
        </div>
    </div>

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

<div class="modal fade" id="modalEditarJogoAgenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" style="font-weight: 400;">Ajustar data, horário e local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small text-muted mb-3" id="edit-jogo-titulo"></p>
                <div class="mb-3">
                    <label class="form-label small">Data do jogo</label>
                    <input type="date" class="form-control rounded-3" id="edit-jogo-data">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small">Início</label>
                        <input type="time" class="form-control rounded-3" id="edit-jogo-inicio">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Término</label>
                        <input type="time" class="form-control rounded-3" id="edit-jogo-fim">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Local</label>
                    <select class="form-select rounded-3" id="edit-jogo-local"></select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-3" id="edit-jogo-salvar">Salvar</button>
            </div>
        </div>
    </div>
</div>

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
    let jogoEmEdicao = null;

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

    function jogosDoMesVisivel() {
        const y = dataNavegacao.getFullYear();
        const m = dataNavegacao.getMonth();
        const modF = modalidadeSelecionadaId();
        return jogosCache
            .filter((j) => {
                if (!j.data_jogo) return false;
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
        const iniciarBtn = podeIniciar(j)
            ? `<button type="button" class="btn btn-sm btn-danger iniciar-jogo-btn" data-id-jogo="${j.id_jogo}">Iniciar jogo</button>`
            : '';
        const placarBtn =
            j.status_jogo === 'Iniciado' || j.status_jogo === 'Pausado'
                ? `<a class="btn btn-sm btn-outline-danger" href="${placarHref}">Placar</a>`
                : '';
        const verBtn =
            j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado'
                ? `<a class="btn btn-sm btn-outline-secondary" href="${placarHref}">Ver resultado</a>`
                : '';
        const ajusteBtn =
            j.status_jogo === 'Agendado'
                ? `<button type="button" class="btn btn-sm btn-outline-dark btn-ajuste-jogo" data-id-jogo="${j.id_jogo}">Ajustar data e local</button>`
                : '';

        return `
            <div class="card bg-white border-0 shadow-sm rounded-3 p-3 position-relative w-100 mb-0">
                <div class="card-body p-0">
                    <h5 class="text-dark mb-1" style="font-weight: 400;">${escapeHtml(j.nome_jogo || 'Jogo')}</h5>
                    <p class="text-muted mb-1 small">${escapeHtml(j.nome_modalidade || '')} · ${escapeHtml(j.nome_local || '')}</p>
                    <p class="text-muted mb-1" style="font-size: 0.8rem;">${diaNum}, ${diaSem}</p>
                    <p class="text-muted mb-2" style="font-size: 0.8rem;">${horario}</p>
                    <p class="mb-2"><span class="badge bg-light text-dark border">${escapeHtml(statusTxt)}</span></p>
                    <div class="d-flex flex-wrap gap-2">
                        ${ajusteBtn}
                        ${iniciarBtn}
                        ${placarBtn}
                        ${verBtn}
                    </div>
                </div>
            </div>`;
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

        document.querySelectorAll('.btn-ajuste-jogo').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-id-jogo'), 10);
                const j = jogosCache.find((x) => Number(x.id_jogo) === id);
                if (!j) return;
                jogoEmEdicao = j;
                document.getElementById('edit-jogo-titulo').textContent = j.nome_jogo || 'Jogo';
                document.getElementById('edit-jogo-data').value = j.data_jogo || '';
                document.getElementById('edit-jogo-inicio').value = formatarHora(j.inicio_jogo) || '08:00';
                document.getElementById('edit-jogo-fim').value = formatarHora(j.termino_jogo || j.terminno_jogo) || '09:00';
                const sel = document.getElementById('edit-jogo-local');
                let match = locaisLista.find((l) => (l.nome_local || '') === (j.nome_local || ''));
                sel.value = match ? String(match.id_local) : (sel.options[0] && sel.options[0].value) || '';
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
        const sel = document.getElementById('edit-jogo-local');
        sel.innerHTML = '';
        locaisLista.forEach((loc) => {
            sel.innerHTML += `<option value="${loc.id_local}">${escapeHtml(loc.nome_local || 'Local')}</option>`;
        });
    }

    function mmJaExisteNaModalidade(idMod) {
        return jogosCache.some(
            (j) =>
                String(j.modalidades_id_modalidade) === String(idMod) &&
                String(j.nome_jogo || '').startsWith('MM:')
        );
    }

    async function contarEquipesValidadas(idModalidade) {
        const res = await fetch(`${API}equipes.php?id_modalidade=${encodeURIComponent(idModalidade)}`);
        const equipes = await res.json();
        const arr = Array.isArray(equipes) ? equipes : [];
        if (arr.length < 2) return { ok: false, n: arr.length, motivo: 'Menos de duas equipes nesta modalidade.' };
        const contagens = await Promise.all(
            arr.map((eq) =>
                fetch(`${API}equipes.php?id_equipe=${encodeURIComponent(eq.id_equipe)}`)
                    .then((r) => r.json())
                    .then((u) => (Array.isArray(u) ? u.length : 0))
                    .catch(() => 0)
            )
        );
        const comElenco = contagens.filter((c) => c >= 1).length;
        if (comElenco < 2) {
            return {
                ok: false,
                n: comElenco,
                motivo: 'É necessário ao menos duas equipes com competidores no elenco (regra do chaveamento).'
            };
        }
        return { ok: true, n: comElenco };
    }

    async function atualizarPainelChaveamento() {
        const idMod = modalidadeSelecionadaId();
        const desk = document.getElementById('chave-status-desk');
        const mob = document.getElementById('chave-status-mobile');
        const btn = document.getElementById('btn-gerar-chave');
        const btnM = document.getElementById('btn-gerar-chave-mobile');
        const setTxt = (t) => {
            desk.textContent = t;
            mob.textContent = t;
        };
        const setDisabled = (d) => {
            btn.disabled = d;
            btnM.disabled = d;
        };
        if (!idMod) {
            setTxt('Selecione uma modalidade para verificar ou gerar o chaveamento.');
            setDisabled(true);
            return;
        }
        if (mmJaExisteNaModalidade(idMod)) {
            setTxt('Chaveamento já gerado para esta modalidade. Ajuste data, horário e local nos jogos abaixo.');
            setDisabled(true);
            return;
        }
        const v = await contarEquipesValidadas(idMod);
        if (!v.ok) {
            setTxt(v.motivo);
            setDisabled(true);
            return;
        }
        setTxt('Pronto para gerar o chaveamento mata-mata com as equipes atuais.');
        setDisabled(false);
    }

    async function gerarChaveamentoClick() {
        const idMod = modalidadeSelecionadaId();
        if (!idMod) return;
        const btn = document.getElementById('btn-gerar-chave');
        const btnM = document.getElementById('btn-gerar-chave-mobile');
        btn.disabled = btnM.disabled = true;
        try {
            const r = await fetch(`${API}chaveamento.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idMod, 10) })
            });
            const js = await r.json();
            if (!r.ok || js.success === false) throw new Error(js.message || 'Não foi possível gerar o chaveamento.');
            alert(js.message || 'Chaveamento gerado.');
            await carregarJogosDoInterclasse();
            atualizarTelas();
            await atualizarPainelChaveamento();
        } catch (e) {
            alert(e.message || 'Erro.');
        } finally {
            await atualizarPainelChaveamento();
        }
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
        await atualizarPainelChaveamento();

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

        document.getElementById('agenda-select-mod').addEventListener('change', () => {
            syncSelectModalidade(true);
            atualizarTelas();
            atualizarPainelChaveamento();
        });
        document.getElementById('agenda-select-mod-mobile').addEventListener('change', () => {
            syncSelectModalidade(false);
            atualizarTelas();
            atualizarPainelChaveamento();
        });

        document.getElementById('btn-gerar-chave').addEventListener('click', gerarChaveamentoClick);
        document.getElementById('btn-gerar-chave-mobile').addEventListener('click', gerarChaveamentoClick);

        document.getElementById('edit-jogo-salvar').addEventListener('click', async () => {
            if (!jogoEmEdicao) return;
            const data = document.getElementById('edit-jogo-data').value;
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
                const r = await fetch(`${API}jogos.php`, {
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
    });
})();
</script>

<?php
require_once '../componentes/footer.php';
