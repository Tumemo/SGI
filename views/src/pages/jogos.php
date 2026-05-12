<?php
$titulo = "Placar";
$textTop = "Placar";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    @media (max-width: 767px) {
        .placar-container {
            grid-template-columns: 1fr !important;
        }
        .score-number {
            font-size: 3.5rem !important;
        }
        .timer-display {
            font-size: 2.5rem !important;
        }
    }
</style>

<main class="container py-4 main-desktop-layout">
    <div class="d-flex flex-column align-items-start gap-3 mb-4">
        <a href="./edicao_agenda.php" class="badge-interclasse text-decoration-none" data-back-link="true">
            <i class="bi bi-arrow-left-circle-fill"></i>
            Voltar à agenda
        </a>
        <div class="d-flex align-items-center gap-2 text-secondary fw-bold">
            <i class="bi bi-record-circle"></i>
            <span id="placar-titulo-jogo">Placar</span>
        </div>
    </div>

    <div id="placar-erro" class="alert alert-danger d-none" role="alert"></div>
    <div id="placar-loading" class="text-muted py-5">Carregando…</div>
    <div id="placar-conteudo" class="d-none">
        <p class="text-muted small mb-3" id="placar-meta"></p>
        <div class="d-flex flex-wrap gap-2 mb-4" id="placar-acoes"></div>
        <div class="placar-container" id="placar-grid"></div>
    </div>
</main>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idJogo = params.get('id_jogo') ? parseInt(params.get('id_jogo'), 10) : null;

    let estadoJogo = null;
    let partidasLista = [];
    let timerId = null;
    let segundos = 0;
    let saveTimers = {};

    function nomeEquipe(p) {
        return p.nome_fantasia_turma || p.nome_turma || `Equipe ${p.equipes_id_equipe}`;
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function fetchJson(url, opts) {
        const r = await fetch(url, opts);
        const t = await r.text();
        let j;
        try {
            j = t ? JSON.parse(t) : {};
        } catch {
            j = {};
        }
        if (!r.ok) throw new Error(j.message || `Erro HTTP ${r.status}`);
        return j;
    }

    function pararTimer() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    function iniciarTimerDisplay() {
        const el = document.getElementById('timer-placar');
        if (!el) return;
        pararTimer();
        segundos = 0;
        const tick = () => {
            segundos += 1;
            const m = String(Math.floor(segundos / 60)).padStart(2, '0');
            const s = String(segundos % 60).padStart(2, '0');
            el.textContent = `${m}:${s}`;
        };
        tick();
        timerId = setInterval(tick, 1000);
    }

    function agendarSalvarPartida(idPartida, gols) {
        if (saveTimers[idPartida]) clearTimeout(saveTimers[idPartida]);
        saveTimers[idPartida] = setTimeout(() => salvarPartida(idPartida, gols), 400);
    }

    async function salvarPartida(idPartida, gols) {
        try {
            await fetchJson(`${API}partidas.php`, {
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
        await fetchJson(`${API}jogos.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_jogo: idJogo, status_jogo: 'Iniciado' })
        });
        estadoJogo.status_jogo = 'Iniciado';
        renderTudo();
        iniciarTimerDisplay();
    }

    async function finalizarJogo() {
        if (!confirm('Encerrar o jogo e gravar o placar final no sistema?')) return;
        const resultados = partidasLista.map((p) => ({
            id_equipe: parseInt(p.equipes_id_equipe, 10),
            gols: Math.max(0, parseInt(p.resultado_partida, 10) || 0)
        }));
        try {
            const res = await fetch(`${API}lancar_resultado.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_jogo: idJogo, resultados })
            });
            const js = await res.json();
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

        const meta = document.getElementById('placar-meta');
        const acoes = document.getElementById('placar-acoes');
        const grid = document.getElementById('placar-grid');
        const titulo = document.getElementById('placar-titulo-jogo');

        titulo.textContent = estadoJogo.nome_jogo || 'Placar';
        meta.textContent = [
            estadoJogo.nome_modalidade,
            estadoJogo.nome_local,
            estadoJogo.data_jogo,
            estadoJogo.inicio_jogo ? estadoJogo.inicio_jogo.slice(0, 5) : ''
        ].filter(Boolean).join(' · ');

        acoes.innerHTML = '';
        const st = estadoJogo.status_jogo;
        const emAndamento = st === 'Iniciado' || st === 'Pausado';
        const encerrado = st === 'Concluido' || st === 'Finalizado';

        if (st === 'Agendado') {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-danger btn-sm';
            b.textContent = 'Iniciar jogo';
            b.addEventListener('click', () => iniciarJogoServidor().catch((e) => alert(e.message)));
            acoes.appendChild(b);
        }

        if (emAndamento && partidasLista.length >= 2) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-outline-danger btn-sm';
            b.textContent = 'Finalizar jogo e salvar resultado';
            b.addEventListener('click', () => finalizarJogo());
            acoes.appendChild(b);
        }

        if (encerrado) {
            const span = document.createElement('span');
            span.className = 'badge bg-secondary';
            span.textContent = 'Jogo encerrado';
            acoes.appendChild(span);
        }

        if (partidasLista.length === 0) {
            grid.innerHTML = '<p class="text-muted">Não há equipes vinculadas a este jogo. Cadastre as partidas no sistema.</p>';
            return;
        }

        const meioTimer = emAndamento
            ? `<div class="text-center">
                <div class="timer-display" id="timer-placar">00:00</div>
                <p class="text-muted small mt-2">Cronômetro local (referência)</p>
               </div>`
            : '<div class="text-center text-muted small">Controle do placar</div>';

        const cards = partidasLista.map((p, idx) => {
            const gols = Math.max(0, parseInt(p.resultado_partida, 10) || 0);
            const readOnly = encerrado || st === 'Agendado';
            const minus = readOnly
                ? `<button type="button" class="btn-score" disabled>−</button>`
                : `<button type="button" class="btn-score btn-placar-menos" data-idx="${idx}">−</button>`;
            const plus = readOnly
                ? `<button type="button" class="btn-score" disabled>+</button>`
                : `<button type="button" class="btn-score btn-placar-mais" data-idx="${idx}" style="background: var(--inter-red); color: white;">+</button>`;
            return `
                <div class="time-card" data-partida-idx="${idx}">
                    <h3 class="fw-bold h5">${esc(nomeEquipe(p))}</h3>
                    <div class="score-display">
                        ${minus}
                        <span class="score-number" style="font-size: 6rem;">${String(gols).padStart(2, '0')}</span>
                        ${plus}
                    </div>
                </div>`;
        });

        if (partidasLista.length === 1) {
            grid.innerHTML = cards[0] + meioTimer;
        } else {
            grid.innerHTML = cards[0] + meioTimer + cards[1];
        }

        document.querySelectorAll('.btn-placar-menos').forEach((btn) => {
            btn.addEventListener('click', () => ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), -1));
        });
        document.querySelectorAll('.btn-placar-mais').forEach((btn) => {
            btn.addEventListener('click', () => ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), 1));
        });

        if (emAndamento) {
            iniciarTimerDisplay();
        }
    }

    function ajustarGols(idx, delta) {
        const p = partidasLista[idx];
        const st = estadoJogo.status_jogo;
        if (!p || (st !== 'Iniciado' && st !== 'Pausado')) return;
        let g = Math.max(0, (parseInt(p.resultado_partida, 10) || 0) + delta);
        p.resultado_partida = g;
        const card = document.querySelector(`[data-partida-idx="${idx}"] .score-number`);
        if (card) card.textContent = String(g).padStart(2, '0');
        agendarSalvarPartida(parseInt(p.id_partida, 10), g);
    }

    async function carregarDados() {
        const err = document.getElementById('placar-erro');
        const load = document.getElementById('placar-loading');
        const cont = document.getElementById('placar-conteudo');
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
            const lista = await fetchJson(`${API}jogos.php?id_jogo=${idJogo}`);
            if (!Array.isArray(lista) || lista.length === 0) throw new Error('Jogo não encontrado.');
            estadoJogo = lista[0];
            if (!estadoJogo.nome_modalidade) {
                estadoJogo.nome_modalidade = '';
            }

            partidasLista = await fetchJson(`${API}partidas.php?id_jogo=${idJogo}`);
            if (!Array.isArray(partidasLista)) partidasLista = [];

            load.classList.add('d-none');
            cont.classList.remove('d-none');
            renderTudo();
        } catch (e) {
            load.classList.add('d-none');
            err.textContent = e.message || 'Erro ao carregar.';
            err.classList.remove('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarDados();
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
