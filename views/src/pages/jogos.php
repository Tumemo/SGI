<?php
$tituloPagina = 'SGI - Placar';
$titulo = 'Placar';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<style>
    :root {
        --placar-red: #E30613;
        --placar-green: #198754;
        --placar-gray: #6c757d;
        --placar-light: #f8f9fa;

        --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --shadow-card: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
        --shadow-card-hover: 0 4px 12px rgba(0,0,0,0.06), 0 2px 4px rgba(0,0,0,0.03);
        --shadow-elevated: 0 8px 25px rgba(0,0,0,0.07), 0 4px 8px rgba(0,0,0,0.03);
        --shadow-modal: 0 20px 60px rgba(0,0,0,0.12), 0 8px 20px rgba(0,0,0,0.05);
        --radius-card: 12px;
        --radius-pill: 9999px;
        --transition: 0.2s ease;
    }

    body {
        font-family: var(--font-sans);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .btn-danger {
        font-family: var(--font-sans);
        background-color: #E30613 !important;
        border-color: #E30613 !important;
    }
    .btn-danger:hover {
        background-color: #bb0812 !important;
        border-color: #bb0812 !important;
    }
    .btn-danger:active,
    .btn-danger:focus {
        background-color: #a00610 !important;
        border-color: #a00610 !important;
        box-shadow: 0 0 0 3px rgba(227,6,19,0.2) !important;
    }

    .placar-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        width: 100%;
    }

    .placar-grid {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    .time-card {
        background: #fff;
        border-radius: var(--radius-card);
        padding: 2rem 1.5rem;
        box-shadow: var(--shadow-card);
        border: 1px solid #e9ecef;
        text-align: center;
        transition: box-shadow var(--transition), transform var(--transition);
    }
    .time-card:hover {
        box-shadow: var(--shadow-card-hover);
    }

    .score-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .score-number {
        font-size: 5.5rem;
        font-weight: 800;
        line-height: 1;
        color: #111827;
        min-width: 5rem;
        text-align: center;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.03em;
        transition: color 0.2s;
    }

    .btn-score {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        font-size: 1.5rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s, transform 0.1s, opacity 0.15s, box-shadow 0.15s;
        line-height: 1;
        flex-shrink: 0;
    }
    .btn-score:active:not(:disabled) {
        transform: scale(0.88);
    }
    .btn-score:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .btn-score-minus {
        background: #f1f3f5;
        color: #495057;
    }
    .btn-score-minus:hover:not(:disabled) {
        background: #dee2e6;
    }
    .btn-score-plus {
        background: var(--placar-red);
        color: #fff;
        box-shadow: 0 2px 8px rgba(227,6,19,0.25);
    }
    .btn-score-plus:hover:not(:disabled) {
        background: #bb0812;
        box-shadow: 0 4px 12px rgba(227,6,19,0.35);
    }

    .timer-wrap {
        background: #fff;
        border-radius: var(--radius-card);
        padding: 1.5rem 1.25rem;
        box-shadow: var(--shadow-card);
        border: 1px solid #e9ecef;
        text-align: center;
        min-width: 180px;
    }
    .timer-display {
        font-size: 3.5rem;
        font-weight: 800;
        color: #111827;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.04em;
        transition: color 0.3s, text-shadow 0.3s;
        line-height: 1.15;
    }
    .timer-display.timer-expired {
        color: var(--placar-red);
        text-shadow: 0 0 24px rgba(227, 6, 19, 0.3);
        animation: timer-pulse 0.8s ease-in-out infinite alternate;
    }
    @keyframes timer-pulse {
        0%   { opacity: 1; }
        100% { opacity: 0.55; }
    }

    .timer-select:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .ocorrencias-card {
        background: #fff;
        border-radius: var(--radius-card);
        border: 1px solid #e9ecef;
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }
    .ocorrencia-item {
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid #f1f3f5;
        display: flex;
        align-items: center;
        gap: 0.875rem;
        transition: background var(--transition);
    }
    .ocorrencia-item:hover {
        background: #fafbfc;
    }
    .ocorrencia-item:last-child {
        border-bottom: none;
    }
    .ocorrencia-tipo {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .ocorrencia-tipo.amarelo {
        background: #ffc107;
        color: #000;
    }
    .ocorrencia-tipo.vermelho {
        background: var(--placar-red);
    }
    .ocorrencia-tipo.suspensao {
        background: #6f42c1;
    }

    .artilheiro-select-wrap select,
    .artilheiro-select-wrap input {
        border-radius: var(--radius-pill);
        font-size: 0.875rem;
    }

    .artilheiro-list .badge-artilheiro {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: #fff3cd;
        color: #856404;
        border-radius: var(--radius-pill);
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
    }

    select:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .score-blocked {
        position: relative;
    }
    .score-blocked::after {
        content: "Tempo esgotado";
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        border-radius: var(--radius-card);
        backdrop-filter: blur(2px);
        z-index: 5;
    }

    .container-principal {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .ocorrencia-tipo-option {
        transition: background 0.15s, color 0.15s, border-color 0.15s, box-shadow 0.15s;
    }
    .ocorrencia-tipo-option.active.btn-outline-warning {
        background: #ffc107 !important;
        color: #000 !important;
        border-color: #ffc107 !important;
        box-shadow: 0 0 0 2px rgba(255,193,7,0.3) !important;
    }
    .ocorrencia-tipo-option.active.btn-outline-danger {
        background: #dc3545 !important;
        color: #fff !important;
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 2px rgba(220,53,69,0.3) !important;
    }
    .btn-outline-suspensao {
        border: 1px solid #6f42c1;
        color: #6f42c1;
        background: transparent;
    }
    .btn-outline-suspensao:hover {
        background: #6f42c1;
        color: #fff;
    }
    .ocorrencia-tipo-option.active.btn-outline-suspensao {
        background: #6f42c1 !important;
        color: #fff !important;
        border-color: #6f42c1 !important;
        box-shadow: 0 0 0 2px rgba(111,66,193,0.3) !important;
    }

    .modal-content {
        border-radius: 16px !important;
        box-shadow: var(--shadow-modal) !important;
    }
    .modal-header {
        padding: 1.25rem 1.5rem 0.5rem !important;
    }
    .modal-body {
        padding: 1rem 1.5rem !important;
    }
    .modal-footer {
        padding: 0.75rem 1.5rem 1.25rem !important;
    }
    .modal-title {
        font-size: 1.1rem;
        letter-spacing: -0.01em;
    }
    .modal .modal-header .btn-close {
        background-size: 0.7em;
        opacity: 0.5;
        transition: opacity var(--transition);
    }
    .modal .modal-header .btn-close:hover {
        opacity: 0.85;
    }
    .modal .d-flex.gap-2 {
        gap: 0.5rem !important;
    }
    .modal .ocorrencia-tipo-option {
        font-size: 0.825rem;
        padding: 0.4rem 0.85rem !important;
    }
    .modal .form-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }
    .modal .form-select-sm,
    .modal .form-control-sm {
        border-radius: 8px;
        font-size: 0.875rem;
        padding: 0.45rem 0.75rem;
        border: 1.5px solid #e5e7eb;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .modal .form-select-sm:focus,
    .modal .form-control-sm:focus {
        border-color: var(--placar-red);
        box-shadow: 0 0 0 3px rgba(227,6,19,0.1);
    }

    .page-header {
        width: 100%;
    }
    .page-title {
        font-size: 1.35rem;
        letter-spacing: -0.02em;
    }

    .section-header {
        width: 100%;
    }
    .section-title {
        font-size: 0.95rem;
        letter-spacing: -0.01em;
        color: #374151;
    }
    .section-title i {
        color: var(--placar-red);
        font-size: 1rem;
    }

    .section-meta {
        font-size: 0.85rem;
        color: #6b7280;
        letter-spacing: 0.01em;
        line-height: 1.5;
    }

    #placar-meta {
        font-size: 0.85rem;
        color: #6b7280;
        letter-spacing: 0.01em;
        line-height: 1.5;
    }

    #placar-acoes {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    #artilheiro-section .row.g-3 > div {
        margin-bottom: 0;
    }

    #alerta-segundo-amarelo .alert {
        animation: alert-slide-in 0.35s ease-out;
        border-left: 4px solid var(--placar-red) !important;
    }
    @keyframes alert-slide-in {
        0%   { opacity: 0; transform: translateY(-8px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 767px) {
        .placar-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }
        .score-number {
            font-size: 3.75rem !important;
            min-width: 3.5rem;
        }
        .timer-display {
            font-size: 2.75rem !important;
        }
        .timer-wrap {
            order: -1;
        }
        .btn-score {
            width: 44px;
            height: 44px;
            font-size: 1.3rem;
        }
        .time-card {
            padding: 1.5rem 1rem !important;
        }
        .timer-wrap {
            padding: 1.25rem 1rem !important;
            min-width: 140px;
        }
        .modal-dialog {
            margin: 0.5rem !important;
        }
        .modal-body {
            padding: 0.75rem 1rem !important;
        }
        .ocorrencia-item {
            padding: 0.75rem 1rem !important;
        }
        .container-principal {
            padding: 0 0.5rem;
        }
    }
</style>

<main class="container py-4 main-desktop-layout">
    <div class="container-principal">
    <div class="page-header d-flex flex-column align-items-start gap-3 mb-4">
        <a href="./edicao_agenda.php"
           class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3"
           data-back-link="true">
            <i class="bi bi-arrow-left-circle"></i>
            Voltar à agenda
        </a>
        <div class="d-flex align-items-center gap-2 text-secondary fw-bold">
            <i class="bi bi-record-circle"></i>
            <span id="placar-titulo-jogo" class="page-title">Placar</span>
        </div>
    </div>

    <div class="placar-wrapper">
        <div id="placar-erro" class="alert alert-danger d-none" role="alert"></div>
        <div id="placar-loading" class="text-muted py-5">Carregando…</div>
        <div id="placar-conteudo" class="d-none">
            <p class="text-muted small mb-4 section-meta" id="placar-meta"></p>

            <div class="d-flex flex-wrap gap-2 mb-4 align-items-center" id="placar-acoes"></div>

            <div class="placar-grid" id="placar-grid"></div>

            <div id="artilheiro-section" class="d-none mt-5">
                <div class="section-header d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0 section-title"><i class="bi bi-person-fill me-1"></i>Artilharia / Destaques</h6>
                </div>
                <div class="row g-3" id="artilheiro-cards"></div>
            </div>

            <div id="ocorrencias-section" class="d-none mt-5">
                <div class="section-header d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0 section-title"><i class="bi bi-exclamation-triangle me-1"></i>Ocorrências da Partida</h6>
                    <button class="btn btn-sm btn-outline-danger rounded-pill" id="btnNovaOcorrencia" onclick="abrirModalOcorrencia()">
                        <i class="bi bi-plus-lg"></i> Nova
                    </button>
                </div>
                <div class="ocorrencias-card" id="lista-ocorrencias">
                    <div class="text-center text-muted small py-3">Nenhuma ocorrência registrada.</div>
                </div>
            </div>
        </div>
        <div id="alerta-segundo-amarelo"></div>
    </div>
    </div>
</main>

<div class="modal fade" id="modalOcorrencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">Nova Ocorrência</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formOcorrencia" onsubmit="return salvarOcorrencia(event)">
                <div class="modal-body px-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tipo</label>
                        <div class="d-flex gap-2">
                            <label class="btn btn-outline-warning rounded-pill px-3 d-flex align-items-center gap-1 ocorrencia-tipo-option" data-tipo="Amarelo">
                                <span class="ocorrencia-tipo amarelo" style="width:20px;height:20px;font-size:0.6rem;">A</span>
                                Amarelo
                                <input type="radio" name="tipo_ocorrencia" value="Amarelo" class="d-none">
                            </label>
                            <label class="btn btn-outline-danger rounded-pill px-3 d-flex align-items-center gap-1 ocorrencia-tipo-option" data-tipo="Vermelho">
                                <span class="ocorrencia-tipo vermelho" style="width:20px;height:20px;font-size:0.6rem;">V</span>
                                Vermelho
                                <input type="radio" name="tipo_ocorrencia" value="Vermelho" class="d-none">
                            </label>
                            <label class="btn btn-outline-suspensao rounded-pill px-3 d-flex align-items-center gap-1 ocorrencia-tipo-option" data-tipo="Suspensao">
                                <span class="ocorrencia-tipo suspensao" style="width:20px;height:20px;font-size:0.6rem;">S</span>
                                Suspensão
                                <input type="radio" name="tipo_ocorrencia" value="Suspensao" class="d-none">
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Turma</label>
                        <select class="form-select form-select-sm" id="filtroTurmaOcorrencia" onchange="carregarAlunosOcorrencia()">
                            <option value="">Selecione a turma</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Aluno</label>
                        <select class="form-select form-select-sm" id="selectAlunoOcorrencia" required disabled>
                            <option value="">Selecione uma turma primeiro</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Penalidade (1–30)</label>
                        <select class="form-select form-select-sm" id="penalidadeOcorrencia">
                            <option value="0">Sem penalidade</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                            <option value="17">17</option>
                            <option value="18">18</option>
                            <option value="19">19</option>
                            <option value="20">20</option>
                            <option value="21">21</option>
                            <option value="22">22</option>
                            <option value="23">23</option>
                            <option value="24">24</option>
                            <option value="25">25</option>
                            <option value="26">26</option>
                            <option value="27">27</option>
                            <option value="28">28</option>
                            <option value="29">29</option>
                            <option value="30">30</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Descrição</label>
                        <textarea class="form-control form-control-sm" id="descricaoOcorrencia" rows="2" required placeholder="Motivo da ocorrência..."></textarea>
                    </div>
                    <div id="msgOcorrencia" class="small"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4" id="btnSalvarOcorrencia">
                        <i class="bi bi-check-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalArtilheiro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-person-fill me-1"></i>Registrar Gol</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formArtilheiro" onsubmit="return salvarArtilheiro(event)">
                <div class="modal-body px-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Equipe</label>
                        <select class="form-select form-select-sm" id="selectEquipeArtilheiro" onchange="carregarAlunosArtilheiro()">
                            <option value="">Selecione a equipe</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jogador(a)</label>
                        <select class="form-select form-select-sm" id="selectAlunoArtilheiro" required>
                            <option value="">Selecione uma equipe primeiro</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Quantidade de gols neste lance</label>
                        <input type="number" class="form-control form-control-sm" id="numGolsArtilheiro" value="1" min="1" max="99" required>
                    </div>
                    <div id="msgArtilheiro" class="small"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" id="btnSalvarArtilheiro">
                        <i class="bi bi-check-lg me-1"></i>Registrar Gol
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idJogo = params.get('id_jogo') ? parseInt(params.get('id_jogo'), 10) : null;

    let estadoJogo = null;
    let partidasLista = [];
    let timerId = null;
    let tempoRestante = 0;
    let duracaoJogo = 20 * 60;
    let pausado = false;
    let saveTimers = {};
    let tempoEsgotado = false;
    let equipesCache = {};

    function formatNomeJogo(nomeJogo) {
        const mm = (nomeJogo || '').match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 8: 'Oitavas de final', 4: 'Quartas de final', 2: 'Final', 1: 'Campeão' };
            const fase = fases[largura] || 'Fase';
            if (largura === 1) return fase;
            return fase + ' — Confronto ' + (slot + 1) + (kind === 'B' ? ' (bye)' : '');
        }
        return nomeJogo || 'Jogo';
    }

    function nomeEquipe(p) {
        return p.nome_fantasia_turma || p.nome_turma || 'Equipe ' + p.equipes_id_equipe;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function fetchJson(url, opts) {
        var r = await fetch(url, opts);
        var t = await r.text();
        var j;
        try { j = t ? JSON.parse(t) : {}; } catch (e) { j = {}; }
        if (!r.ok) throw new Error(j.message || 'Erro HTTP ' + r.status);
        return j;
    }

    function pararTimer() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    function atualizarDisplayTimer() {
        var el = document.getElementById('timer-placar');
        if (!el) return;
        var m = String(Math.floor(Math.max(0, tempoRestante) / 60)).padStart(2, '0');
        var s = String(Math.max(0, tempoRestante) % 60).padStart(2, '0');
        el.textContent = m + ':' + s;
        el.classList.toggle('timer-expired', tempoRestante <= 0 && tempoEsgotado);
    }

    function tocarAlertaSonoro() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.4, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 1.2);
        } catch (e) {}
    }

    function bloquearPontuacao() {
        tempoEsgotado = true;
        pararTimer();
        atualizarDisplayTimer();
        tocarAlertaSonoro();
        document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
            b.disabled = true;
        });
        var grid = document.getElementById('placar-grid');
        if (grid) grid.classList.add('score-blocked');
    }

    function iniciarTimerDisplay() {
        var el = document.getElementById('timer-placar');
        if (!el) return;
        pararTimer();

        if (tempoRestante <= 0 && estadoJogo.status_jogo === 'Agendado') {
            tempoRestante = duracaoJogo;
        }

        if (tempoRestante <= 0) {
            tempoEsgotado = true;
            atualizarDisplayTimer();
            return;
        }

        tempoEsgotado = false;
        pausado = estadoJogo.status_jogo === 'Pausado';
        atualizarDisplayTimer();
        var btnPause = document.getElementById('btn-pausar');
        if (btnPause) btnPause.textContent = pausado ? 'Retomar' : 'Pausar';
        if (!pausado) {
            timerId = setInterval(function() {
                if (tempoRestante > 0) {
                    tempoRestante--;
                    atualizarDisplayTimer();
                    if (tempoRestante <= 0) {
                        bloquearPontuacao();
                    }
                }
            }, 1000);
        }
    }

    function togglePause() {
        pausado = !pausado;
        var btn = document.getElementById('btn-pausar');
        if (btn) btn.textContent = pausado ? 'Retomar' : 'Pausar';
        if (pausado) {
            pararTimer();
        } else if (!timerId) {
            timerId = setInterval(function() {
                if (tempoRestante > 0) {
                    tempoRestante--;
                    atualizarDisplayTimer();
                    if (tempoRestante <= 0) {
                        bloquearPontuacao();
                    }
                }
            }, 1000);
        }
        fetchJson(API + 'jogos.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_jogo: idJogo, tempo_restante_jogo: tempoRestante })
        }).catch(function() {});
    }

    function mudarDuracao(segundos) {
        var st = estadoJogo.status_jogo;
        var diff = segundos - duracaoJogo;
        duracaoJogo = segundos;
        if (st === 'Iniciado' || st === 'Pausado') {
            tempoRestante = Math.max(1, tempoRestante + diff);
        } else {
            tempoRestante = segundos;
        }
        tempoEsgotado = false;
        atualizarDisplayTimer();
    }

    function agendarSalvarPartida(idPartida, gols) {
        if (saveTimers[idPartida]) clearTimeout(saveTimers[idPartida]);
        saveTimers[idPartida] = setTimeout(function() { salvarPartida(idPartida, gols); }, 400);
    }

    async function salvarPartida(idPartida, gols) {
        try {
            await fetchJson(API + 'partidas.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_partida: idPartida, resultado_partida: gols })
            });
        } catch (e) {
            console.error(e);
            alert('Não foi possível salvar o placar. Verifique a conexão.');
        }
    }

    async function iniciarJogoServidor() {
        duracaoJogo = parseInt(document.getElementById('select-duracao').value, 10) * 60;
        tempoRestante = duracaoJogo;
        await fetchJson(API + 'jogos.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_jogo: idJogo, status_jogo: 'Iniciado', tempo_restante_jogo: duracaoJogo })
        });
        estadoJogo.status_jogo = 'Iniciado';
        renderTudo();
        iniciarTimerDisplay();
    }

    async function finalizarJogo() {
        if (!confirm('Encerrar o jogo e gravar o placar final no sistema?')) return;
        var resultados = partidasLista.map(function(p) {
            return {
                id_equipe: parseInt(p.equipes_id_equipe, 10),
                gols: Math.max(0, parseInt(p.resultado_partida, 10) || 0)
            };
        });
        try {
            var res = await fetch(API + 'lancar_resultado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_jogo: idJogo, resultados: resultados })
            });
            var js = await res.json();
            if (!res.ok || js.success === false) throw new Error(js.message || 'Falha ao finalizar');
            estadoJogo.status_jogo = 'Concluido';
            pararTimer();
            await carregarDados();
        } catch (e) {
            alert(e.message || 'Erro ao finalizar.');
        }
    }

    function renderTudo() {
        pararTimer();
        tempoEsgotado = false;

        var meta = document.getElementById('placar-meta');
        var acoes = document.getElementById('placar-acoes');
        var grid = document.getElementById('placar-grid');
        var titulo = document.getElementById('placar-titulo-jogo');

        titulo.textContent = formatNomeJogo(estadoJogo.nome_jogo) || 'Placar';
        meta.textContent = [
            estadoJogo.nome_modalidade,
            estadoJogo.nome_local,
            estadoJogo.data_jogo,
            estadoJogo.inicio_jogo ? estadoJogo.inicio_jogo.slice(0, 5) : ''
        ].filter(Boolean).join(' · ');

        acoes.innerHTML = '';
        grid.innerHTML = '';
        grid.classList.remove('score-blocked');

        var st = estadoJogo.status_jogo;
        var emAndamento = st === 'Iniciado' || st === 'Pausado';
        var encerrado = st === 'Concluido' || st === 'Finalizado';

        if (st === 'Agendado') {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 shadow-sm rounded-3';
            b.innerHTML = '<i class="bi bi-play-fill"></i> Iniciar jogo';
            b.addEventListener('click', function() {
                iniciarJogoServidor().catch(function(e) { alert(e.message); });
            });
            acoes.appendChild(b);
        }

        if (emAndamento && partidasLista.length >= 2) {
            var b2 = document.createElement('button');
            b2.type = 'button';
            b2.className = 'btn btn-outline-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 shadow-sm rounded-3';
            b2.innerHTML = '<i class="bi bi-stop-fill"></i> Finalizar jogo e salvar resultado';
            b2.addEventListener('click', function() { finalizarJogo(); });
            acoes.appendChild(b2);
        }

        if (encerrado) {
            var span = document.createElement('span');
            span.className = 'badge bg-secondary rounded-pill px-3 py-2';
            span.innerHTML = '<i class="bi bi-check-circle me-1"></i> Jogo encerrado';
            acoes.appendChild(span);
        }

        if (partidasLista.length === 0) {
            grid.innerHTML = '<p class="text-muted">Não há equipes vinculadas a este jogo. Cadastre as partidas no sistema.</p>';
            return;
        }

        var podeTimer = emAndamento || st === 'Agendado';

        var meioTimer = podeTimer
            ? '<div class="timer-wrap">' +
                '<div class="d-flex align-items-center justify-content-center gap-2 mb-2">' +
                    '<label class="small text-muted mb-0">Tempo:</label>' +
                    '<select id="select-duracao" class="form-select form-select-sm d-inline-block w-auto timer-select rounded-pill"' +
                        'style="max-width:105px;" ' + (emAndamento ? 'disabled' : '') + '>' +
                        [5,10,15,20,25,30,40,45].map(function(v) {
                            return '<option value="' + v + '" ' + (duracaoJogo === v*60 ? 'selected' : '') + '>' + v + ' min</option>';
                        }).join('') +
                    '</select>' +
                '</div>' +
                '<div class="timer-display" id="timer-placar">' +
                    String(Math.floor(duracaoJogo / 60)).padStart(2, '0') + ':' +
                    String(duracaoJogo % 60).padStart(2, '0') +
                '</div>' +
                (emAndamento ? '<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="btn-pausar">' +
                    (pausado ? 'Retomar' : 'Pausar') +
                '</button></div>' : '') +
              '</div>'
            : '<div class="timer-wrap"><div class="text-center text-muted small">Controle do placar</div></div>';

        var cards = partidasLista.map(function(p, idx) {
            var gols = Math.max(0, parseInt(p.resultado_partida, 10) || 0);
            var readonly = encerrado || st === 'Agendado' || tempoEsgotado;
            var minus = readonly
                ? '<button type="button" class="btn-score btn-score-minus" disabled><i class="bi bi-dash"></i></button>'
                : '<button type="button" class="btn-score btn-score-minus" data-idx="' + idx + '"><i class="bi bi-dash"></i></button>';
            var plus = readonly
                ? '<button type="button" class="btn-score btn-score-plus" disabled><i class="bi bi-plus"></i></button>'
                : '<button type="button" class="btn-score btn-score-plus" data-idx="' + idx + '"><i class="bi bi-plus"></i></button>';
            return '<div class="time-card" data-partida-idx="' + idx + '">' +
                    '<h3 class="fw-bold h6 mb-0">' + esc(nomeEquipe(p)) + '</h3>' +
                    '<div class="score-display">' +
                        minus +
                        '<span class="score-number" data-gols="' + idx + '">' + String(gols).padStart(2, '0') + '</span>' +
                        plus +
                    '</div>' +
                   '</div>';
        });

        if (partidasLista.length === 1) {
            grid.innerHTML = cards[0] + meioTimer;
        } else {
            grid.innerHTML = cards[0] + meioTimer + cards[1];
        }

        document.querySelectorAll('.btn-score-minus').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!tempoEsgotado) ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), -1);
            });
        });
        document.querySelectorAll('.btn-score-plus').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!tempoEsgotado) ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), 1);
            });
        });

        var selDuracao = document.getElementById('select-duracao');
        if (selDuracao) {
            selDuracao.addEventListener('change', function() {
                mudarDuracao(parseInt(selDuracao.value, 10) * 60);
            });
        }

        var btnPause = document.getElementById('btn-pausar');
        if (btnPause) {
            btnPause.addEventListener('click', function() { togglePause(); });
        }

        if (emAndamento) {
            iniciarTimerDisplay();
        }
    }

    function ajustarGols(idx, delta) {
        var p = partidasLista[idx];
        var st = estadoJogo.status_jogo;
        if (!p || (st !== 'Iniciado' && st !== 'Pausado') || tempoEsgotado) return;
        var g = Math.max(0, (parseInt(p.resultado_partida, 10) || 0) + delta);
        p.resultado_partida = g;
        var el = document.querySelector('[data-gols="' + idx + '"]');
        if (el) el.textContent = String(g).padStart(2, '0');
        agendarSalvarPartida(parseInt(p.id_partida, 10), g);

        if (delta > 0 && ehFutsal()) {
            var idEquipe = parseInt(p.equipes_id_equipe, 10);
            abrirModalArtilheiro(idEquipe);
        }
    }

    function ehFutsal() {
        return (estadoJogo.nome_modalidade || '').toLowerCase() === 'futsal';
    }

    async function carregarDados() {
        var err = document.getElementById('placar-erro');
        var load = document.getElementById('placar-loading');
        var cont = document.getElementById('placar-conteudo');
        err.classList.add('d-none');
        load.classList.remove('d-none');
        cont.classList.add('d-none');

        if (!idJogo) {
            load.classList.add('d-none');
            err.textContent = 'Informe o jogo na URL (?id_jogo=…).';
            err.classList.remove('d-none');
            return;
        }

        try {
            var lista = await fetchJson(API + 'jogos.php?id_jogo=' + idJogo);
            if (!Array.isArray(lista) || lista.length === 0) throw new Error('Jogo não encontrado.');
            estadoJogo = lista[0];
            if (!estadoJogo.nome_modalidade) estadoJogo.nome_modalidade = '';
            if (estadoJogo.status_jogo === 'Concluido' || estadoJogo.status_jogo === 'Finalizado') {
                tempoEsgotado = true;
            }

            partidasLista = await fetchJson(API + 'partidas.php?id_jogo=' + idJogo);
            if (!Array.isArray(partidasLista)) partidasLista = [];

            if (estadoJogo.tempo_restante_jogo != null) {
                var v = parseInt(estadoJogo.tempo_restante_jogo, 10);
                if (!isNaN(v) && v > 0) tempoRestante = v;
            }

            load.classList.add('d-none');
            cont.classList.remove('d-none');
            renderTudo();

            if (ehFutsal()) {
                iniciarArtilheiro();
            }
            iniciarOcorrencias();
        } catch (e) {
            load.classList.add('d-none');
            err.textContent = e.message || 'Erro ao carregar.';
            err.classList.remove('d-none');
        }
    }

    function iniciarOcorrencias() {
        var section = document.getElementById('ocorrencias-section');
        section.classList.remove('d-none');
        carregarOcorrencias();
        carregarTurmasOcorrencia();
    }

    function carregarTurmasOcorrencia() {
        var select = document.getElementById('filtroTurmaOcorrencia');
        select.innerHTML = '<option value="">Selecione a turma</option>';
        var vistas = {};
        partidasLista.forEach(function(p) {
            var idTurma = parseInt(p.id_turma, 10);
            if (!idTurma) return;
            var nome = esc(nomeEquipe(p));
            if (!vistas[idTurma]) {
                vistas[idTurma] = true;
                select.innerHTML += '<option value="' + idTurma + '">' + nome + '</option>';
            }
        });
    }

    async function carregarAlunosOcorrencia() {
        var select = document.getElementById('selectAlunoOcorrencia');
        var idTurma = document.getElementById('filtroTurmaOcorrencia').value;
        if (!idTurma) {
            select.value = '';
            select.disabled = true;
            select.innerHTML = '<option value="">Selecione uma turma primeiro</option>';
            return;
        }
        select.disabled = true;
        select.innerHTML = '<option value="">Carregando...</option>';
        try {
            var data = await fetchJson(API + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + idJogo + '&id_turma=' + idTurma);
            var alunos = data.success && Array.isArray(data.atletas) ? data.atletas : [];
            select.innerHTML = '<option value="">Selecione o(a) aluno(a)</option>';
            alunos.forEach(function(a) {
                select.innerHTML += '<option value="' + a.id_usuario + '">' + esc(a.nome_usuario) + '</option>';
            });
            select.disabled = false;
        } catch (e) {
            select.innerHTML = '<option value="">Erro ao carregar alunos</option>';
            select.disabled = false;
        }
    }

    function limparDescricaoOcorrencia(desc) {
        return (desc || '').replace(/\[JOGO:\d+\]/g, '').replace(/\[TURMA:\d+\]/g, '').trim();
    }

    async function carregarOcorrencias() {
        var container = document.getElementById('lista-ocorrencias');
        try {
            var data = await fetchJson(API + 'ocorrencias.php?id_jogo=' + idJogo + '&data=' + (estadoJogo.data_jogo || ''));
            var lista = Array.isArray(data) ? data : [];
            if (!lista.length) {
                container.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma ocorrência registrada.</div>';
                return;
            }
            container.innerHTML = lista.map(function(o) {
                var tipo = (o.titulo_ocorrencia || '').toLowerCase();
                var cls = 'amarelo';
                if (tipo.indexOf('vermelho') !== -1) cls = 'vermelho';
                else if (tipo.indexOf('suspensao') !== -1 || tipo.indexOf('suspensão') !== -1) cls = 'suspensao';
                var label = tipo === 'amarelo' ? 'A' : (tipo.indexOf('vermelho') !== -1 ? 'V' : 'S');
                var pts = parseInt(o.penalidade, 10);
                var ptsHtml = pts > 0 ? '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill" style="font-size:0.7rem;">-' + pts + ' pts</span>' : '';
                return '<div class="ocorrencia-item">' +
                    '<span class="ocorrencia-tipo ' + cls + '">' + label + '</span>' +
                    '<div class="flex-grow-1">' +
                        '<div class="fw-semibold small">' + esc(o.nome_usuario) + ' ' + ptsHtml + '</div>' +
                        '<div class="text-muted" style="font-size:0.8rem;">' + esc(limparDescricaoOcorrencia(o.descricao_ocorrencia)) + '</div>' +
                    '</div>' +
                    '<div class="d-flex gap-1 flex-shrink-0">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2" onclick="editarOcorrencia(' + o.id_ocorrencia + ')" title="Editar">' +
                            '<i class="bi bi-pencil"></i>' +
                        '</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="excluirOcorrencia(' + o.id_ocorrencia + ')" title="Excluir">' +
                            '<i class="bi bi-trash"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>';
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="text-center text-danger small py-3">Erro ao carregar ocorrências.</div>';
        }
    }

    var _editandoOcorrenciaId = null;

    function abrirModalOcorrencia() {
        _editandoOcorrenciaId = null;
        document.getElementById('formOcorrencia').reset();
        document.getElementById('msgOcorrencia').innerHTML = '';
        document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
            el.classList.remove('active');
        });
        var selAluno = document.getElementById('selectAlunoOcorrencia');
        selAluno.disabled = true;
        selAluno.innerHTML = '<option value="">Selecione uma turma primeiro</option>';
        document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar';
        var modal = new bootstrap.Modal(document.getElementById('modalOcorrencia'));
        modal.show();
    }

    async function editarOcorrencia(id) {
        _editandoOcorrenciaId = id;
        document.getElementById('formOcorrencia').reset();
        document.getElementById('msgOcorrencia').innerHTML = '';
        document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
            el.classList.remove('active');
        });
        var selAluno = document.getElementById('selectAlunoOcorrencia');
        selAluno.disabled = true;
        selAluno.innerHTML = '<option value="">Carregando...</option>';
        document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Atualizar';

        try {
            var lista = await fetchJson(API + 'ocorrencias.php?id_ocorrencia=' + id);
            var o = Array.isArray(lista) ? lista[0] : null;
            if (!o) return;
            document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
                if (el.getAttribute('data-tipo') === o.titulo_ocorrencia) {
                    el.classList.add('active');
                    var radio = el.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                }
            });
            document.getElementById('descricaoOcorrencia').value = limparDescricaoOcorrencia(o.descricao_ocorrencia);
            document.getElementById('penalidadeOcorrencia').value = o.penalidade || 0;
            var idTurma = o.turmas_id_turma || 0;
            if (idTurma) {
                document.getElementById('filtroTurmaOcorrencia').value = idTurma;
                try {
                    await carregarAlunosOcorrencia();
                } catch (e) {}
                var sel = document.getElementById('selectAlunoOcorrencia');
                sel.value = o.id_usuario;
                if (sel.value !== String(o.id_usuario)) {
                    var opt = document.createElement('option');
                    opt.value = o.id_usuario;
                    opt.textContent = esc(o.nome_usuario);
                    sel.appendChild(opt);
                    sel.value = o.id_usuario;
                }
            }
            var modal = new bootstrap.Modal(document.getElementById('modalOcorrencia'));
            modal.show();
        } catch (e) {
            _editandoOcorrenciaId = null;
            document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar';
        }
    }

    async function excluirOcorrencia(id) {
        if (!confirm('Tem certeza que deseja excluir esta ocorrência?')) return;
        try {
            await fetchJson(API + 'ocorrencias.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_ocorrencia: id, status_ocorrencia: '0' })
            });
            carregarOcorrencias();
        } catch (e) {
            alert('Erro ao excluir ocorrência.');
        }
    }

    async function salvarOcorrencia(e) {
        e.preventDefault();
        var btn = document.getElementById('btnSalvarOcorrencia');
        var msg = document.getElementById('msgOcorrencia');
        msg.innerHTML = '';

        var editando = _editandoOcorrenciaId;

        var tipoEl = document.querySelector('.ocorrencia-tipo-option.active');
        if (!tipoEl) {
            msg.innerHTML = '<span class="text-danger">Selecione o tipo de ocorrência.</span>';
            return;
        }
        var tipo = tipoEl.getAttribute('data-tipo');

        var idTurma = document.getElementById('filtroTurmaOcorrencia').value;
        if (!idTurma) {
            msg.innerHTML = '<span class="text-danger">Selecione a turma.</span>';
            return;
        }

        var idAluno = document.getElementById('selectAlunoOcorrencia').value;
        if (!idAluno) {
            msg.innerHTML = '<span class="text-danger">Selecione o(a) aluno(a).</span>';
            return;
        }
        var descricao = document.getElementById('descricaoOcorrencia').value.trim();
        if (!descricao) {
            msg.innerHTML = '<span class="text-danger">Informe a descrição.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        var isUpdate = !!editando;
        var payload = {
            titulo_ocorrencia: tipo,
            descricao_ocorrencia: descricao,
            data_ocorrencia: estadoJogo.data_jogo || new Date().toISOString().slice(0, 10),
            usuarios_id_usuario: parseInt(idAluno, 10),
            penalidade: parseInt(document.getElementById('penalidadeOcorrencia').value, 10)
        };

        if (isUpdate) {
            payload.id_ocorrencia = editando;
        } else {
            payload.id_jogo = idJogo;
            payload.id_turma = parseInt(idTurma, 10);
        }

        try {
            var resp = await fetch(API + 'ocorrencias.php', {
                method: isUpdate ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var result = await resp.json();
            if (result.success) {
                msg.innerHTML = '<span class="text-success">Ocorrência ' + (isUpdate ? 'atualizada' : 'registrada') + '!</span>';
                if (!isUpdate && result.evento === 'segundo_amarelo') {
                    var selAluno = document.getElementById('selectAlunoOcorrencia');
                    var nomeAluno = selAluno.options[selAluno.selectedIndex] ? selAluno.options[selAluno.selectedIndex].text : '';
                    carregarAlunosOcorrencia();
                    mostrarAlertaSegundoAmarelo(nomeAluno);
                } else if (!isUpdate && (tipo === 'Vermelho' || tipo === 'Suspensao')) {
                    carregarAlunosOcorrencia();
                }
                setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalOcorrencia'));
                    if (m) m.hide();
                    _editandoOcorrenciaId = null;
                    carregarOcorrencias();
                }, 600);
            } else {
                msg.innerHTML = '<span class="text-danger">' + (result.message || 'Erro ao salvar.') + '</span>';
                btn.disabled = false;
                btn.innerHTML = isUpdate ? '<i class="bi bi-check-lg me-1"></i>Atualizar' : '<i class="bi bi-check-lg me-1"></i>Registrar';
            }
        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
            btn.disabled = false;
            btn.innerHTML = isUpdate ? '<i class="bi bi-check-lg me-1"></i>Atualizar' : '<i class="bi bi-check-lg me-1"></i>Registrar';
        }
    }

    function iniciarArtilheiro() {
        document.getElementById('artilheiro-section').classList.remove('d-none');
        carregarEquipesArtilheiro();
    }

    async function carregarEquipesArtilheiro() {
        var select = document.getElementById('selectEquipeArtilheiro');
        select.innerHTML = '<option value="">Selecione a equipe</option>';
        partidasLista.forEach(function(p) {
            select.innerHTML += '<option value="' + p.equipes_id_equipe + '">' + esc(nomeEquipe(p)) + '</option>';
        });
    }

    async function carregarAlunosArtilheiro() {
        var select = document.getElementById('selectAlunoArtilheiro');
        var idEquipe = document.getElementById('selectEquipeArtilheiro').value;
        if (!idEquipe) {
            select.innerHTML = '<option value="">Selecione uma equipe primeiro</option>';
            return;
        }
        select.innerHTML = '<option value="">Carregando...</option>';
        try {
            var p = partidasLista.find(function(p) { return p.equipes_id_equipe == idEquipe; });
            if (!p) throw new Error('Equipe não encontrada');
            var idTurma = p.id_turma;
            var data = await fetchJson(API + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + idJogo + '&id_turma=' + idTurma);
            var alunos = data.success && Array.isArray(data.atletas) ? data.atletas : [];
            select.innerHTML = '<option value="">Selecione o(a) jogador(a)</option>';
            alunos.forEach(function(a) {
                select.innerHTML += '<option value="' + a.id_usuario + '">' + esc(a.nome_usuario) + '</option>';
            });
            if (!alunos.length) {
                select.innerHTML = '<option value="">Nenhum jogador disponível</option>';
            }
        } catch (e) {
            select.innerHTML = '<option value="">Erro ao carregar jogadores</option>';
        }
    }

    var _artilheiroEquipeAtual = null;

    function abrirModalArtilheiro(idEquipe) {
        _artilheiroEquipeAtual = idEquipe;
        document.getElementById('formArtilheiro').reset();
        document.getElementById('msgArtilheiro').innerHTML = '';
        document.getElementById('numGolsArtilheiro').value = 1;

        var selectEquipe = document.getElementById('selectEquipeArtilheiro');
        selectEquipe.value = idEquipe;
        carregarAlunosArtilheiro();

        var modal = new bootstrap.Modal(document.getElementById('modalArtilheiro'));
        modal.show();
    }

    async function salvarArtilheiro(e) {
        e.preventDefault();
        var btn = document.getElementById('btnSalvarArtilheiro');
        var msg = document.getElementById('msgArtilheiro');
        msg.innerHTML = '';

        var idAluno = document.getElementById('selectAlunoArtilheiro').value;
        if (!idAluno) {
            msg.innerHTML = '<span class="text-danger">Selecione o(a) jogador(a).</span>';
            return;
        }
        var numGols = parseInt(document.getElementById('numGolsArtilheiro').value, 10);
        if (!numGols || numGols < 1) {
            msg.innerHTML = '<span class="text-danger">Informe ao menos 1 gol.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        try {
            var resp = await fetch(API + 'artilheiro.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    usuarios_id_usuario: parseInt(idAluno, 10),
                    jogos_id_jogo: idJogo,
                    num_gol: numGols
                })
            });
            var result = await resp.json();
            if (result.success) {
                msg.innerHTML = '<span class="text-success">Gol(s) registrado(s)!</span>';
                setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalArtilheiro'));
                    if (m) m.hide();
                }, 600);
            } else {
                msg.innerHTML = '<span class="text-danger">' + (result.message || 'Erro ao registrar.') + '</span>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
            }
        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
        }
    }

    function mostrarAlertaSegundoAmarelo(nomeAluno) {
        var container = document.getElementById('alerta-segundo-amarelo');
        if (!container) return;
        var nome = nomeAluno || 'Jogador(a)';
        container.innerHTML =
            '<div class="alert alert-danger d-flex align-items-center gap-3 py-3 px-4 mb-0 rounded-3 shadow-sm border-0" role="alert" style="background:#fef2f2;color:#991b1b;">' +
                '<span class="ocorrencia-tipo vermelho" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">V</span>' +
                '<div class="flex-grow-1">' +
                    '<strong class="d-block mb-1" style="font-size:0.85rem;">SEGUNDO CARTÃO AMARELO</strong>' +
                    '<span style="font-size:0.82rem;">' + esc(nome) + ' recebeu o segundo amarelo e foi expulso(a) da partida (Cartão Vermelho automático).</span>' +
                '</div>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar" style="font-size:0.75rem;"></button>' +
            '</div>';
        setTimeout(function() {
            var alert = container.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';
                setTimeout(function() { if (alert.parentNode) alert.remove(); }, 350);
            }
        }, 8000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarDados();
    });

    document.addEventListener('click', function(e) {
        var opt = e.target.closest('.ocorrencia-tipo-option');
        if (opt) {
            document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
                el.classList.remove('active');
            });
            opt.classList.add('active');
            var radio = opt.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
