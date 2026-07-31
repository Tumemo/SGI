<?php
$tituloPagina = 'SGI - Agenda';
$titulo = 'Agenda';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
/* ── Agenda modern layout ── */
.ag-page { padding-bottom: 5rem; }

.ag-btn-interclasse {
    display: inline-flex; align-items: center; gap: .5rem;
    background: #E30613; color: #fff; font-weight: 600; text-decoration: none;
    border-radius: 10px; padding: 9px 18px;
    box-shadow: 0 3px 10px rgba(227,6,19,.28);
    transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
    flex-shrink: 0;
}
.ag-btn-interclasse:hover { background: #B9050F; color: #fff; transform: translateY(-1px); box-shadow: 0 5px 16px rgba(227,6,19,.38); }

.ag-header-row { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.ag-header__text { min-width: 0; flex: 1 1 240px; }
.ag-header__text h2 { font-size: 1.6rem; font-weight: 700; color: #1F2937; margin: 0; letter-spacing: -0.02em; display: flex; align-items: center; gap: .55rem; }
.ag-header__text h2 i { color: #E30613; }
.ag-header__text p { font-size: .9rem; color: #6B7280; margin: .3rem 0 0; }
.ag-badge-count {
    display: inline-flex; align-items: center; gap: .4rem; margin-left: auto;
    background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA;
    font-size: .78rem; font-weight: 700; border-radius: 50px;
    padding: .4rem .9rem; letter-spacing: .02em; white-space: nowrap;
}
.ag-badge-count i { font-size: .85rem; }

/* ── Filter bar ── */
.ag-filter-bar { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem; }
.ag-search { position: relative; flex: 1 1 220px; min-width: 200px; }
.ag-search i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: .9rem; pointer-events: none; }
.ag-search input {
    width: 100%; border: 1.5px solid #E5E7EB; border-radius: 10px;
    font-size: .85rem; color: #374151; background: #fff; padding: .5rem .8rem .5rem 2.1rem;
    transition: border-color .15s, box-shadow .15s;
}
.ag-search input:focus { border-color: #E30613; box-shadow: 0 0 0 3px rgba(227,6,19,.08); outline: none; }
.ag-filter-bar select {
    border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: .82rem; font-weight: 500;
    color: #374151; background: #fff; padding: .5rem .75rem;
    transition: border-color .15s, box-shadow .15s; cursor: pointer;
}
.ag-filter-bar select:focus { border-color: #E30613; box-shadow: 0 0 0 3px rgba(227,6,19,.08); outline: none; }

/* ── Calendar card ── */
.ag-cal-card { background: #fff; border: 1px solid #ECEFF1; border-radius: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.03); overflow: hidden; }
.ag-cal-header { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1.15rem; background: linear-gradient(135deg, #111827 0%, #1F2937 100%); color: #fff; }
.ag-cal-header span { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
.ag-cal-header select { color: #fff; background: transparent; }
.ag-cal-header select option { color: #111827; }
.ag-cal-nav { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; transition: background .15s; color: rgba(255,255,255,.7); border: none; background: transparent; font-size: 1rem; }
.ag-cal-nav:hover { background: rgba(255,255,255,.12); color: #fff; }
.ag-cal-body { padding: .75rem 1rem 1rem; }
.ag-cal-weekdays { display: flex; text-align: center; margin-bottom: .4rem; }
.ag-cal-weekdays span { width: 14.28%; font-size: .7rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .06em; padding: .3rem 0; }
.ag-cal-grid { display: flex; flex-wrap: wrap; text-align: center; }
.ag-cal-day { width: 14.28%; height: 38px; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 500; color: #374151; cursor: pointer; border-radius: 10px; transition: all .15s; position: relative; }
.ag-cal-day:hover { background: #F3F4F6; }
.ag-cal-day--empty { cursor: default; }
.ag-cal-day--empty:hover { background: transparent; }
.ag-cal-day--today { color: #E30613; font-weight: 700; }
.ag-cal-day--today::after { content: ""; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: #E30613; }
.ag-cal-day--selected { background: #E30613 !important; color: #fff !important; font-weight: 700; }
.ag-cal-day--selected::after { background: #fff !important; }
.ag-cal-day--has-game { font-weight: 700; color: #B91C1C; }
.ag-cal-day--has-game::before { content: ""; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%); width: 5px; height: 5px; border-radius: 50%; background: #E30613; }
.ag-cal-day--selected.ag-cal-day--has-game { color: #fff; }
.ag-cal-day--selected.ag-cal-day--has-game::before { background: #fff; }

/* ── Event cards ── */
.ag-event-list { display: flex; flex-direction: column; gap: .85rem; }
.ag-event-card {
    background: #fff; border: 1px solid #ECEFF1; border-radius: 16px; padding: 1rem 1.15rem;
    transition: transform .2s, box-shadow .2s; position: relative; overflow: hidden;
}
.ag-event-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.07); }
.ag-event-card::before { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #9CA3AF; }
.ag-event-card--andamento::before { background: #F59E0B; }
.ag-event-card--pausado::before { background: #F97316; }
.ag-event-card--concluido::before { background: #10B981; }
.ag-event-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .6rem; }
.ag-event-card__chips { display: flex; gap: .45rem; flex-wrap: wrap; }
.ag-meta-chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 600; color: #4B5563; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: .3rem .6rem; white-space: nowrap; }
.ag-meta-chip i { font-size: .75rem; color: #E30613; }
.ag-status-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; border-radius: 50px; padding: .3rem .7rem; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
.ag-status-chip--agendado { background: #F3F4F6; color: #4B5563; }
.ag-status-chip--andamento { background: #FEF3C7; color: #92400E; }
.ag-status-chip--pausado { background: #FFEDD5; color: #C2410C; }
.ag-status-chip--concluido { background: #D1FAE5; color: #065F46; }

.ag-event-card__title { font-size: 1rem; font-weight: 700; color: #111827; line-height: 1.3; margin: 0; }
.ag-event-card__subtitle { font-size: .82rem; color: #4B5563; margin: .25rem 0 0; display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.ag-event-card__subtitle i { color: #E30613; }
.ag-event-card__teams {
    display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
    margin-top: .6rem; padding: .55rem .8rem; background: #F9FAFB; border-radius: 10px;
    font-size: .85rem; font-weight: 600; color: #1F2937; width: 100%;
}
.ag-event-card__teams .ag-vs { font-size: .65rem; font-weight: 800; color: #E30613; letter-spacing: .04em; }
.ag-event-card__teams i { color: #9CA3AF; font-size: .8rem; }
.ag-event-card__actions { display: flex; align-items: center; gap: .5rem; margin-top: .8rem; flex-wrap: wrap; }
.ag-event-card__actions .btn { font-size: .8rem; font-weight: 600; border-radius: 9px; padding: .42rem .9rem; display: inline-flex; align-items: center; gap: .4rem; }
.ag-icon-btn {
    width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 9px; border: 1px solid #E5E7EB; background: #fff; color: #6B7280; cursor: pointer;
    transition: all .15s; margin-left: auto;
}
.ag-icon-btn:hover { background: #FEF2F2; border-color: #FECACA; color: #E30613; }

/* ── Empty state ── */
.ag-empty { text-align: center; padding: 3rem 1.5rem; color: #9CA3AF; }
.ag-empty i { font-size: 2.5rem; display: block; margin-bottom: .75rem; color: #D1D5DB; }
.ag-empty p { margin: 0; font-size: .9rem; }

/* ── Show all button ── */
.ag-show-all { display: flex; justify-content: center; margin-top: 1rem; }
.ag-show-all .btn { border-radius: 10px; font-weight: 600; font-size: .82rem; padding: .45rem 1rem; }

/* ── Google Calendar link ── */
.ag-gcal { display: flex; justify-content: center; margin-top: 1.25rem; }
.ag-gcal .btn { border-radius: 10px; font-size: .82rem; font-weight: 500; }

/* ── Desktop layout ── */
.ag-desktop { display: none; }
@media (min-width: 768px) {
    .ag-desktop { display: block; }
    .ag-mobile { display: none !important; }
    .ag-desktop-layout { width: 100%; }
    .ag-desktop-grid { display: grid; grid-template-columns: minmax(0,1fr) 380px; gap: 2rem; align-items: start; }
    .ag-cal-sticky { position: sticky; top: 24px; }
}
@media (min-width: 1200px) {
    .ag-desktop-grid { gap: 2.75rem; }
}
@media (min-width: 992px) and (max-width: 1199.98px) {
    .ag-desktop-grid { grid-template-columns: minmax(0,1fr) 340px; gap: 1.75rem; }
}

/* ── Mobile refinements ── */
@media (max-width: 767.98px) {
    .ag-mobile { padding-top: 5.5rem; padding-bottom: 5rem; }
    .ag-cal-card { max-width: 420px; margin: 0 auto 1.25rem; }
    .ag-event-list { max-width: 420px; margin: 0 auto; }
    .ag-event-card { padding: .9rem 1rem; }
}

/* ── Modal improvements ── */
.ag-modal .modal-content { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.ag-modal .modal-header { border: none; padding: 1.25rem 1.5rem .5rem; }
.ag-modal .modal-title { font-size: 1.05rem; font-weight: 700; color: #111827; }
.ag-modal .modal-body { padding: .5rem 1.5rem 1.25rem; }
.ag-modal .modal-footer { border: none; padding: .5rem 1.5rem 1.25rem; }
.ag-modal .form-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #6B7280; font-weight: 600; margin-bottom: .3rem; }
.ag-modal .form-control, .ag-modal .form-select { border-radius: 10px; border: 1.5px solid #E5E7EB; font-size: .875rem; padding: .55rem .85rem; transition: border-color .15s, box-shadow .15s; }
.ag-modal .form-control:focus, .ag-modal .form-select:focus { border-color: #E30613; box-shadow: 0 0 0 3px rgba(227,6,19,.08); }
';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'agenda';
$nivelUsuarioAgenda = (int)($_SESSION['nivel'] ?? -1);
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none ag-mobile p-3">
    <div class="ag-cal-card">
        <div class="ag-cal-header">
            <button type="button" id="btn-prev-mobile" class="ag-cal-nav"><i class="bi bi-chevron-left"></i></button>
            <div class="d-flex gap-2 align-items-center">
                <select id="select-mes" class="form-select form-select-sm border-0 text-white text-center" style="width:auto; font-size:.82rem; font-weight:700; letter-spacing:.04em; cursor:pointer; box-shadow:none;">
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
                <select id="select-ano" class="form-select form-select-sm border-0 text-white text-center" style="width:auto; font-size:.82rem; font-weight:700; letter-spacing:.04em; cursor:pointer; box-shadow:none;">
                </select>
            </div>
            <button type="button" id="btn-next-mobile" class="ag-cal-nav"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="ag-cal-body">
            <div class="ag-cal-weekdays">
                <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
            </div>
            <div id="calendario-grade-mobile" class="ag-cal-grid"></div>
        </div>
    </div>

    <div class="ag-filter-bar justify-content-center">
        <div class="ag-search" style="max-width: 260px;">
            <i class="bi bi-search"></i>
            <input type="text" id="agenda-busca-mobile" placeholder="Buscar time ou modalidade...">
        </div>
        <select id="agenda-select-mod-mobile" class="form-select form-select-sm" style="max-width: 260px;"></select>
        <select id="agenda-select-status-mobile" class="form-select form-select-sm" style="max-width: 260px;">
            <option value="">Todos os status</option>
            <option value="Concluido">Concluídos</option>
            <option value="andamento">Em andamento</option>
            <option value="Agendado">Agendados</option>
        </select>
    </div>

    <div id="lista-eventos-mobile" class="ag-event-list"></div>
    <div class="ag-show-all" id="container-mostrar-todos-mobile" style="display:none;">
        <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos-mobile">
            <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
        </button>
    </div>
    <div class="ag-gcal">
        <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir no Google Calendar
        </a>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout ag-page">
    <div class="ag-desktop-layout">

        <div class="ag-header-row">
            <a href="./dashboard.php" id="btnVoltarAgendaDesk" class="ag-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseAgenda">Interclasse</span>
            </a>
            <div class="ag-header__text">
                <h2><i class="bi bi-calendar3"></i> Agenda de Jogos</h2>
                <p>Calendário de confrontos e partidas do Interclasse</p>
            </div>
            <span class="ag-badge-count" id="agenda-count-badge" style="display:none;">
                <i class="bi bi-fire"></i> <span id="agenda-count-text">0 jogos</span>
            </span>
        </div>

        <div class="ag-filter-bar">
            <div class="ag-search">
                <i class="bi bi-search"></i>
                <input type="text" id="agenda-busca" placeholder="Buscar time ou modalidade...">
            </div>
            <select id="agenda-select-mod" style="max-width: 280px;"></select>
            <select id="agenda-select-status">
                <option value="">Todos os status</option>
                <option value="Concluido">Concluídos</option>
                <option value="andamento">Em andamento</option>
                <option value="Agendado">Agendados</option>
            </select>
        </div>

        <div class="ag-desktop-grid">
            <div>
                <div id="lista-eventos" class="ag-event-list"></div>
                <div class="ag-show-all" id="container-mostrar-todos" style="display:none;">
                    <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos">
                        <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
                    </button>
                </div>
            </div>

            <div class="ag-cal-sticky">
                <div class="ag-cal-card">
                    <div class="ag-cal-header">
                        <button type="button" id="btn-prev" class="ag-cal-nav"><i class="bi bi-chevron-left"></i></button>
                        <span id="calendario-mes"></span>
                        <button type="button" id="btn-next" class="ag-cal-nav"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="ag-cal-body">
                        <div class="ag-cal-weekdays">
                            <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
                        </div>
                        <div id="calendario-grade" class="ag-cal-grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ═══ MODAL ═══ -->
<div class="modal fade ag-modal" id="modalEditarJogoAgenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar data, horário e local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="edit-jogo-titulo"></p>
                <div class="mb-3">
                    <label class="form-label">Data do jogo</label>
                    <input type="date" class="form-control" id="edit-jogo-data">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Início</label>
                        <input type="time" class="form-control" id="edit-jogo-inicio">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Término</label>
                        <input type="time" class="form-control" id="edit-jogo-fim">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Local</label>
                    <select class="form-select" id="edit-jogo-local"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600; font-size: .85rem;">Cancelar</button>
                <button type="button" class="btn btn-danger" id="edit-jogo-salvar" style="border-radius: 10px; font-weight: 600; font-size: .85rem;">Salvar</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const API = '../../../api/';
    const NIVEL_USUARIO = <?= $nivelUsuarioAgenda ?>;
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
        const stF = filtroStatus;
        const q = buscaAtual.toLowerCase();
        return jogosCache
            .filter((j) => {
                if (!j.data_jogo) return false;
                if (isJogoCampeao(j.nome_jogo)) return false;
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
            if (isJogoCampeao(j.nome_jogo)) return false;
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
        const placarHref = `./jogos.php?id_jogo=${encodeURIComponent(j.id_jogo)}`;
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
                    ${localTxt ? `<span style="color:#D1D5DB;">•</span> ${localTxt}` : ''}
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
            document.getElementById('container-mostrar-todos').style.display = filtroData ? 'block' : 'none';
            document.getElementById('container-mostrar-todos-mobile').style.display = filtroData ? 'block' : 'none';
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

        document.getElementById('container-mostrar-todos').style.display = filtroData ? 'block' : 'none';
        document.getElementById('container-mostrar-todos-mobile').style.display = filtroData ? 'block' : 'none';

        document.querySelectorAll('.btn-ajuste-jogo').forEach((btn) => {
            btn.addEventListener('click', () => {
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

    /* ── FUNÇÃO DE LOCAIS ATUALIZADA COM OS FILTROS ── */
    async function carregarLocais() {
        if (!interclasseAtual || !interclasseAtual.id_interclasse) return;

        const res = await fetch(`${API}locais.php?id_interclasse=${encodeURIComponent(interclasseAtual.id_interclasse)}`);
        const data = await res.json();
        
        let todosLocais = data && Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : [];
        
        // Filtra para manter apenas os do interclasse atual e que estejam 'Disponivel'
        locaisLista = todosLocais.filter((loc) => {
            const mesmoInterclasse = String(loc.interclasses_id_interclasse) === String(interclasseAtual.id_interclasse);
            
            const statusClean = (loc.status_local || loc.status || '')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase();
                
            const isDisponivel = statusClean === 'disponivel';
            
            return mesmoInterclasse && isDisponivel;
        });

        const sel = document.getElementById('edit-jogo-local');
        sel.innerHTML = '';

        if (locaisLista.length === 0) {
            sel.innerHTML = '<option value="">Nenhum local disponível</option>';
            return;
        }

        locaisLista.forEach((loc) => {
            sel.innerHTML += `<option value="${loc.id_local}">${escapeHtml(loc.nome_local || 'Local')}</option>`;
        });
    }

    /* ── ORDEM DE EXECUÇÃO AJUSTADA NO DOMCONTENTLOADED ── */
    document.addEventListener('DOMContentLoaded', async function () {
        try {
            // 1. Obtém primeiro o interclasse selecionado
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
            // 2. Carrega locais e jogos SOMENTE após o interclasseAtual ter sido definido
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
        if (el1) el1.addEventListener('click', () => navegarMes(-1));
        const el2 = document.getElementById('btn-next');
        if (el2) el2.addEventListener('click', () => navegarMes(1));
        const el3 = document.getElementById('btn-prev-mobile');
        if (el3) el3.addEventListener('click', () => navegarMes(-1));
        const el4 = document.getElementById('btn-next-mobile');
        if (el4) el4.addEventListener('click', () => navegarMes(1));
        const el5 = document.getElementById('select-mes');
        if (el5) el5.addEventListener('change', (e) => {
            dataNavegacao.setMonth(parseInt(e.target.value, 10));
            filtroData = null;
            atualizarTelas();
        });
        const el6 = document.getElementById('select-ano');
        if (el6) el6.addEventListener('change', (e) => {
            dataNavegacao.setFullYear(parseInt(e.target.value, 10));
            filtroData = null;
            atualizarTelas();
        });

        function aplicarFiltroData(dataStr) {
            filtroData = filtroData === dataStr ? null : dataStr;
            atualizarTelas();
        }

        const gradeDesk = document.getElementById('calendario-grade');
        if (gradeDesk) gradeDesk.addEventListener('click', (e) => {
            const target = e.target.closest('[data-date]');
            if (target) aplicarFiltroData(target.dataset.date);
        });
        const gradeMob = document.getElementById('calendario-grade-mobile');
        if (gradeMob) gradeMob.addEventListener('click', (e) => {
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
        if (el7) el7.addEventListener('click', limparFiltro);
        const el8 = document.getElementById('btn-mostrar-todos-mobile');
        if (el8) el8.addEventListener('click', limparFiltro);

        const elMod = document.getElementById('agenda-select-mod');
        if (elMod) elMod.addEventListener('change', () => {
            syncSelectModalidade(true);
            filtroData = null;
            atualizarTelas();
        });
        const elModMob = document.getElementById('agenda-select-mod-mobile');
        if (elModMob) elModMob.addEventListener('change', () => {
            syncSelectModalidade(false);
            filtroData = null;
            atualizarTelas();
        });

        const elStatus = document.getElementById('agenda-select-status');
        if (elStatus) elStatus.addEventListener('change', () => {
            syncSelectStatus(true);
            filtroStatus = elStatus.value;
            filtroData = null;
            atualizarTelas();
        });
        const elStatusMob = document.getElementById('agenda-select-status-mobile');
        if (elStatusMob) elStatusMob.addEventListener('change', () => {
            syncSelectStatus(false);
            filtroStatus = elStatusMob.value;
            filtroData = null;
            atualizarTelas();
        });

        const elBusca = document.getElementById('agenda-busca');
        if (elBusca) elBusca.addEventListener('input', (e) => {
            setBusca(e.target.value.trim());
            filtroData = null;
            atualizarTelas();
        });
        const elBuscaMob = document.getElementById('agenda-busca-mobile');
        if (elBuscaMob) elBuscaMob.addEventListener('input', (e) => {
            setBusca(e.target.value.trim());
            filtroData = null;
            atualizarTelas();
        });

        const elSalvar = document.getElementById('edit-jogo-salvar');
        if (elSalvar) elSalvar.addEventListener('click', async () => {
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
include 'componentes/nav.php';
require_once '../componentes/footer.php';
