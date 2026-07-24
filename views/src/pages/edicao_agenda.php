<?php
$tituloPagina = 'SGI - Agenda';
$titulo = 'Agenda';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
/* ── Agenda modern layout ── */
.ag-page { padding-bottom: 5rem; }
.ag-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.75rem; }
.ag-header h2 { font-size: 1.6rem; font-weight: 700; color: #111827; margin: 0; letter-spacing: -0.02em; }
.ag-header h2 i { color: #E30613; font-size: 1.35rem; }
.ag-badge-count { background: #FEF2F2; color: #B91C1C; font-size: .75rem; font-weight: 700; border-radius: 50px; padding: .2rem .65rem; letter-spacing: .02em; }

/* ── Calendar card ── */
.ag-cal-card { background: #fff; border: 1px solid #F0F0F0; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.03); overflow: hidden; }
.ag-cal-header { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1.15rem; background: linear-gradient(135deg, #111827 0%, #1F2937 100%); color: #fff; }
.ag-cal-header span { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
.ag-cal-nav { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; transition: background .15s; color: rgba(255,255,255,.7); border: none; background: transparent; font-size: 1rem; }
.ag-cal-nav:hover { background: rgba(255,255,255,.12); color: #fff; }
.ag-cal-body { padding: .75rem 1rem 1rem; }
.ag-cal-weekdays { display: flex; text-align: center; margin-bottom: .4rem; }
.ag-cal-weekdays span { width: 14.28%; font-size: .7rem; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em; padding: .3rem 0; }
.ag-cal-grid { display: flex; flex-wrap: wrap; text-align: center; }
.ag-cal-day { width: 14.28%; height: 38px; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 500; color: #374151; cursor: pointer; border-radius: 10px; transition: all .15s; position: relative; }
.ag-cal-day:hover { background: #F3F4F6; }
.ag-cal-day--empty { cursor: default; }
.ag-cal-day--empty:hover { background: transparent; }
.ag-cal-day--today { color: #E30613; font-weight: 700; }
.ag-cal-day--today::after { content: ""; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: #E30613; }
.ag-cal-day--selected { background: #E30613 !important; color: #fff !important; font-weight: 700; border-radius: 10px; }
.ag-cal-day--selected::after { background: #fff !important; }
.ag-cal-day--has-game { font-weight: 700; }
.ag-cal-day--has-game::before { content: ""; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%); width: 16px; height: 3px; border-radius: 2px; background: linear-gradient(90deg, #FCA5A5, #E30613); }
.ag-cal-day--selected.ag-cal-day--has-game::before { background: rgba(255,255,255,.5); }

/* ── Filter bar ── */
.ag-filter-bar { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.25rem; }
.ag-filter-bar select { border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: .82rem; font-weight: 500; color: #374151; background: #fff; padding: .45rem .75rem; transition: border-color .15s, box-shadow .15s; cursor: pointer; }
.ag-filter-bar select:focus { border-color: #E30613; box-shadow: 0 0 0 3px rgba(227,6,19,.08); outline: none; }

/* ── Event cards ── */
.ag-event-list { display: flex; flex-direction: column; gap: .75rem; }
.ag-event-card { background: #fff; border: 1px solid #F0F0F0; border-radius: 16px; padding: 1.1rem 1.25rem; transition: transform .2s, box-shadow .2s; position: relative; overflow: hidden; }
.ag-event-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.07); }
.ag-event-card::before { content: ""; position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 0 2px 2px 0; background: #E5E7EB; }
.ag-event-card--agendado::before { background: #F59E0B; }
.ag-event-card--andamento::before { background: #3B82F6; }
.ag-event-card--pausado::before { background: #8B5CF6; }
.ag-event-card--concluido::before { background: #10B981; }
.ag-event-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .5rem; }
.ag-event-card__title { font-size: .95rem; font-weight: 600; color: #111827; line-height: 1.3; margin: 0; }
.ag-event-card__subtitle { font-size: .8rem; color: #6B7280; margin: 0; display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.ag-event-card__meta { display: flex; align-items: center; gap: .75rem; margin-top: .6rem; flex-wrap: wrap; }
.ag-meta-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .75rem; color: #6B7280; background: #F9FAFB; border: 1px solid #F0F0F0; border-radius: 8px; padding: .3rem .6rem; white-space: nowrap; }
.ag-meta-chip i { font-size: .7rem; color: #9CA3AF; }
.ag-status-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; border-radius: 50px; padding: .25rem .65rem; letter-spacing: .02em; text-transform: uppercase; }
.ag-status-chip--agendado { background: #FEF3C7; color: #92400E; }
.ag-status-chip--andamento { background: #DBEAFE; color: #1E40AF; }
.ag-status-chip--pausado { background: #EDE9FE; color: #6D28D9; }
.ag-status-chip--concluido { background: #D1FAE5; color: #065F46; }
.ag-event-card__actions { display: flex; gap: .5rem; margin-top: .75rem; flex-wrap: wrap; }
.ag-event-card__actions .btn { font-size: .78rem; font-weight: 600; border-radius: 8px; padding: .35rem .8rem; }

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
    .ag-desktop-grid { display: grid; grid-template-columns: 1fr 380px; gap: 2.5rem; align-items: start; }
    .ag-cal-sticky { position: sticky; top: 24px; }
}

/* ── Mobile refinements ── */
@media (max-width: 767.98px) {
    .ag-mobile { padding-top: 5.5rem; padding-bottom: 5rem; }
    .ag-cal-card { max-width: 420px; margin: 0 auto 1.25rem; }
    .ag-event-list { max-width: 420px; margin: 0 auto; }
    .ag-event-card { padding: 1rem; }
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
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none ag-mobile p-3">
    <div class="ag-cal-card">
        <div class="ag-cal-header">
            <button type="button" id="btn-prev-mobile" class="ag-cal-nav"><i class="bi bi-chevron-left"></i></button>
            <div class="d-flex gap-2 align-items-center">
                <select id="select-mes" class="form-select form-select-sm border-0 bg-transparent text-white text-center" style="width:auto; font-size:.82rem; font-weight:700; letter-spacing:.04em; cursor:pointer; box-shadow:none;">
                    <option value="0" class="text-dark">Jan</option>
                    <option value="1" class="text-dark">Fev</option>
                    <option value="2" class="text-dark">Mar</option>
                    <option value="3" class="text-dark">Abr</option>
                    <option value="4" class="text-dark">Mai</option>
                    <option value="5" class="text-dark">Jun</option>
                    <option value="6" class="text-dark">Jul</option>
                    <option value="7" class="text-dark">Ago</option>
                    <option value="8" class="text-dark">Set</option>
                    <option value="9" class="text-dark">Out</option>
                    <option value="10" class="text-dark">Nov</option>
                    <option value="11" class="text-dark">Dez</option>
                </select>
                <select id="select-ano" class="form-select form-select-sm border-0 bg-transparent text-white text-center" style="width:auto; font-size:.82rem; font-weight:700; letter-spacing:.04em; cursor:pointer; box-shadow:none;">
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
        <select id="agenda-select-mod-mobile" class="form-select form-select-sm" style="max-width: 260px;"></select>
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
    <div class="ag-desktop-layout" class="p-5">
        <a href="./dashboard.php" class="btn btn-danger d-inline-flex align-items-center gap-2 mb-4 border-0 text-decoration-none" style="border-radius: 10px; padding: .55rem 1.15rem; font-size: .85rem; font-weight: 600;" id="btnVoltarAgendaDesk">
            <i class="bi bi-arrow-left-circle"></i>
            <span id="nomeInterclasseAgenda">Interclasse</span>
        </a>

        <div class="ag-desktop-grid">
            <div>
                <div class="ag-header">
                    <span class="ag-badge-count" id="agenda-count-badge" style="display:none;">0 jogos</span>
                </div>

                <div class="ag-filter-bar">
                    <select id="agenda-select-mod" class="form-select form-select-sm" style="max-width: 280px;"></select>
                </div>

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
    let dataNavegacao = new Date();
    const params = new URLSearchParams(window.location.search);
    const idInterclasseAgenda = params.get('id');

    let interclasseAtual = null;
    let jogosCache = [];
    let modalidadesLista = [];
    let locaisLista = [];
    let jogoEmEdicao = null;
    let filtroData = null;

    function formatNomeJogo(nomeJogo) {
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
                if (filtroData && j.data_jogo !== filtroData) return false;
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
        const statusClass = (j.status_jogo || '').toLowerCase().replace('ã','a').replace('õ','o');
        const statusMap = { agendado: 'agendado', iniciado: 'andamento', pausado: 'pausado', concluido: 'concluido', finalizado: 'concluido' };
        const cardClass = statusMap[statusClass] || 'agendado';
        const chipClass = statusMap[statusClass] || 'agendado';
        const statusTxt = labelStatus(j.status_jogo);
        const iniciarBtn = podeIniciar(j)
            ? `<button type="button" class="btn btn-sm btn-danger iniciar-jogo-btn" data-id-jogo="${j.id_jogo}" style="border-radius:8px;font-weight:600;font-size:.78rem;">Iniciar jogo</button>`
            : '';
        const placarBtn =
            j.status_jogo === 'Iniciado' || j.status_jogo === 'Pausado'
                ? `<a class="btn btn-sm btn-outline-danger" href="${placarHref}" style="border-radius:8px;font-weight:600;font-size:.78rem;">Placar</a>`
                : '';
        const verBtn =
            j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado'
                ? `<a class="btn btn-sm btn-outline-secondary" href="${placarHref}" style="border-radius:8px;font-weight:600;font-size:.78rem;">Ver resultado</a>`
                : '';
        const ajusteBtn =
            j.status_jogo === 'Agendado'
                ? `<button type="button" class="btn btn-sm btn-outline-dark btn-ajuste-jogo" data-id-jogo="${j.id_jogo}" style="border-radius:8px;font-weight:600;font-size:.78rem;">Ajustar data e local</button>`
                : '';

        return `
            <div class="ag-event-card ag-event-card--${cardClass}">
                <div class="ag-event-card__top">
                    <p class="ag-event-card__title">${escapeHtml(formatNomeJogo(j.nome_jogo))}</p>
                    <span class="ag-status-chip ag-status-chip--${chipClass}">${escapeHtml(statusTxt)}</span>
                </div>
                <p class="ag-event-card__subtitle">
                    ${escapeHtml(j.nome_modalidade || '')}
                    ${j.nome_local ? '<span style="color:#D1D5DB;">·</span> ' + escapeHtml(j.nome_local) : ''}
                </p>
                <div class="ag-event-card__meta">
                    <span class="ag-meta-chip"><i class="bi bi-calendar3"></i> ${diaNum}, ${diaSem}</span>
                    <span class="ag-meta-chip"><i class="bi bi-clock"></i> ${horario}</span>
                </div>
                <div class="ag-event-card__actions">
                    ${ajusteBtn}${iniciarBtn}${placarBtn}${verBtn}
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
            badge.textContent = lista.length + (lista.length === 1 ? ' jogo' : ' jogos');
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
