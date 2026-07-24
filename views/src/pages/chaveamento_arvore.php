<?php
$tituloPagina = 'SGI - Chaveamento';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';

$cssExtra = '
/* ── Chaveamento Page ── */
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

.kv-page {
    font-family: "Inter", sans-serif;
    font-weight: 400;
    color: #1a1a2e;
    background: #f8f9fc;
    min-height: 100vh;
    padding: 40px;
}
.kv-page * { box-sizing: border-box; }

/* ── Header ── */
.kv-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}
.kv-header__left { display: flex; align-items: center; gap: 16px; }
.kv-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.kv-back:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
    transform: translateX(-2px);
}
.kv-title { font-size: 1.75rem; font-weight: 800; color: #111827; line-height: 1.2; }
.kv-subtitle { font-size: 0.9rem; color: #6b7280; margin-top: 2px; }
.kv-header__right { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.kv-btn-generate {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: #e30613;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 4px 14px rgba(227,6,19,0.3);
}
.kv-btn-generate:hover {
    background: #c00510;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(227,6,19,0.4);
}
.kv-btn-generate:disabled {
    background: #9ca3af;
    box-shadow: none;
    transform: none;
    cursor: not-allowed;
}

/* ── Stats Cards ── */
.kv-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
.kv-stat {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #f0f0f5;
    transition: all 0.25s;
}
.kv-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }
.kv-stat__icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.kv-stat__icon--modalidades { background: #fee2e2; color: #dc2626; }
.kv-stat__icon--jogos { background: #dbeafe; color: #2563eb; }
.kv-stat__icon--campeoes { background: #fef3c7; color: #d97706; }
.kv-stat__icon--pendentes { background: #e0e7ff; color: #4f46e5; }
.kv-stat__info { flex: 1; }
.kv-stat__number { font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1; }
.kv-stat__label { font-size: 0.78rem; color: #6b7280; margin-top: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }

/* ── Generation Area ── */
.kv-gen-card {
    background: #fff;
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 32px;
    border: 1px solid #f0f0f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.kv-gen-card__header { margin-bottom: 20px; }
.kv-gen-card__title { font-size: 1.1rem; font-weight: 700; color: #111827; }
.kv-gen-card__desc { font-size: 0.85rem; color: #6b7280; margin-top: 4px; }
.kv-gen-card__row {
    display: flex;
    gap: 12px;
    align-items: stretch;
}
.kv-gen-card__select {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.9rem;
    font-family: "Inter", sans-serif;
    color: #374151;
    background: #fff;
    transition: border-color 0.2s;
    appearance: auto;
}
.kv-gen-card__select:focus { outline: none; border-color: #e30613; box-shadow: 0 0 0 3px rgba(227,6,19,0.1); }
.kv-gen-card__note { font-size: 0.78rem; color: #9ca3af; margin-top: 12px; font-style: italic; }

/* ── Phase Timeline ── */
.kv-phase-timeline {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 28px;
    padding: 20px 24px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f0f0f5;
    overflow-x: auto;
    flex-wrap: nowrap;
}
.kv-phase {
    display: flex;
    align-items: center;
    gap: 0;
    white-space: nowrap;
}
.kv-phase__item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #9ca3af;
    background: #f3f4f6;
    transition: all 0.2s;
}
.kv-phase__item--active {
    background: #e30613;
    color: #fff;
}
.kv-phase__item--done {
    background: #dcfce7;
    color: #166534;
}
.kv-phase__arrow {
    color: #d1d5db;
    font-size: 0.85rem;
    padding: 0 6px;
    flex-shrink: 0;
}

/* ── Bracket Overrides ── */
#bracketArea .bracket-container { margin: 0; padding: 20px 0; }
#bracketArea .bracket-wrapper { gap: 32px; }
#bracketArea .bracket-round { min-width: 220px; max-width: 260px; }
#bracketArea .bracket-game { border-radius: 12px; border: 1px solid #e5e7eb; transition: all 0.25s; }
#bracketArea .bracket-game:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); border-color: #d1d5db; }
#bracketArea .bracket-game.concluido { border-color: #10b981; border-width: 2px; }
#bracketArea .bracket-game.bye { opacity: 0.6; border-style: dashed; }
#bracketArea .bracket-game.disputa-posicao { border-color: #e30613; border-width: 2px; background: linear-gradient(135deg, #fff5f5, #fff); }
#bracketArea .bracket-round-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #e30613;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-align: center;
    padding-bottom: 12px;
    border-bottom: 2px solid #e30613;
    margin-bottom: 12px;
}
#bracketArea .team-name { font-size: 0.83rem; font-weight: 500; }
#bracketArea .team-score { font-size: 1rem; font-weight: 700; background: #f3f4f6; border-radius: 6px; padding: 2px 8px; min-width: 30px; text-align: center; }
#bracketArea .game-status-badge { font-size: 0.62rem; padding: 2px 8px; border-radius: 999px; font-weight: 600; margin: 4px 14px 8px; }

/* ── Modern Bracket Tree ── */
.bracket-tree {
    display: flex;
    gap: 0;
    overflow-x: auto;
    padding: 24px 0 40px;
    align-items: flex-start;
    scrollbar-width: thin;
    scrollbar-color: #d1d5db transparent;
}
.bracket-tree::-webkit-scrollbar { height: 8px; }
.bracket-tree::-webkit-scrollbar-track { background: transparent; }
.bracket-tree::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

.bracket-round-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 260px;
    max-width: 300px;
    flex: 1 0 260px;
    position: relative;
    padding: 0 20px;
}
.bracket-round-header {
    font-size: 0.72rem;
    font-weight: 700;
    color: #e30613;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 10px 20px;
    background: linear-gradient(135deg, #fef2f2, #fff);
    border: 1px solid #fecaca;
    border-radius: 999px;
    margin-bottom: 24px;
    white-space: nowrap;
}

/* Champion card */
.bracket-champion-col {
    min-width: 320px;
    flex: 0 0 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 16px;
}
.bracket-champion-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 50%, #fef9c3 100%);
    border: 2px solid #f59e0b;
    border-radius: 18px;
    padding: 32px 24px;
    text-align: center;
    width: 100%;
    box-shadow: 0 8px 32px rgba(245,158,11,0.2);
    animation: championPulse 2s ease-in-out infinite;
}
.bracket-champion-card__icon { font-size: 3rem; margin-bottom: 12px; }
.bracket-champion-card__label { font-size: 0.72rem; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
.bracket-champion-card__name { font-size: 1.2rem; font-weight: 800; color: #78350f; line-height: 1.3; }
.bracket-champion-card__mod { font-size: 0.8rem; color: #b45309; margin-top: 8px; font-weight: 500; }
@keyframes championPulse {
    0%, 100% { box-shadow: 0 8px 32px rgba(245,158,11,0.2); }
    50% { box-shadow: 0 8px 40px rgba(245,158,11,0.35); }
}

/* Bracket match cards */
.bkt-match {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
    transition: all 0.25s ease;
    position: relative;
    margin-bottom: 16px;
}
.bkt-match:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: #d1d5db;
}
.bkt-match--concluido { border-color: #10b981; border-width: 2px; }
.bkt-match--concluido:hover { box-shadow: 0 8px 24px rgba(16,185,129,0.12); }
.bkt-match--bye { opacity: 0.55; border-style: dashed; }
.bkt-match--bye:hover { opacity: 0.7; }
.bkt-match--posicao { border-color: #e30613; border-width: 2px; background: linear-gradient(135deg, #fff5f5, #fff); }

.bkt-team {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    gap: 8px;
    transition: background 0.15s;
}
.bkt-team + .bkt-team { border-top: 1px solid #f3f4f6; }
.bkt-team:hover { background: #f9fafb; }
.bkt-team--winner { background: #f0fdf4; }
.bkt-team--loser { opacity: 0.55; }
.bkt-team__name {
    flex: 1;
    font-size: 0.82rem;
    font-weight: 500;
    color: #1f2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bkt-team--winner .bkt-team__name { font-weight: 700; color: #166534; }
.bkt-team__score {
    font-size: 0.95rem;
    font-weight: 800;
    color: #111827;
    min-width: 28px;
    text-align: center;
    background: #f3f4f6;
    border-radius: 6px;
    padding: 2px 8px;
}
.bkt-team--winner .bkt-team__score { background: #dcfce7; color: #166534; }
.bkt-team__trophy {
    font-size: 0.7rem;
    color: #f59e0b;
    margin-left: 2px;
}

.bkt-match__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 14px;
    background: #f9fafb;
    border-top: 1px solid #f3f4f6;
    gap: 6px;
}
.bkt-match__info {
    font-size: 0.68rem;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    overflow: hidden;
}
.bkt-match__info span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bkt-match__status {
    font-size: 0.6rem;
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.bkt-match__status--agendado { background: #fef3c7; color: #92400e; }
.bkt-match__status--andamento { background: #dbeafe; color: #1e40af; animation: statusPulse 1.5s ease-in-out infinite; }
.bkt-match__status--concluido { background: #dcfce7; color: #166534; }
.bkt-match__status--iniciado { background: #dbeafe; color: #1e40af; animation: statusPulse 1.5s ease-in-out infinite; }
.bkt-match__status--bye { background: #e0e7ff; color: #4338ca; }
@keyframes statusPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

/* Bracket connectors */
.bkt-connector {
    position: relative;
    width: 20px;
    min-width: 20px;
    flex: 0 0 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.bkt-connector svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

/* ── Bracket Game Actions ── */
.bkt-match__actions {
    display: flex;
    gap: 4px;
    padding: 4px 10px 8px;
    justify-content: flex-end;
    opacity: 0;
    transition: opacity 0.2s;
}
.bkt-match:hover .bkt-match__actions { opacity: 1; }
.game-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    font-size: 0.68rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    color: #6b7280;
    white-space: nowrap;
}
.game-action-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.game-action-btn--start { color: #16a34a; border-color: #bbf7d0; }
.game-action-btn--start:hover { background: #dcfce7; color: #15803d; border-color: #86efac; }
.game-action-btn--view { color: #2563eb; border-color: #bfdbfe; }
.game-action-btn--view:hover { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
.game-action-btn--edit { color: #6b7280; }
.game-action-btn--edit:hover { background: #f3f4f6; color: #374151; }

/* ── Modal Team/Score Row ── */
.kv-modal .team-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f9fafb;
    border-radius: 10px;
    border: 1px solid #f0f0f5;
    margin-bottom: 10px;
}
.kv-modal .team-row__name {
    flex: 1;
    font-weight: 600;
    font-size: 0.9rem;
    color: #111827;
}
.kv-modal .team-row__score {
    width: 70px;
    text-align: center;
}
.kv-modal .team-row__score input {
    width: 100%;
    text-align: center;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 6px 8px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}
.kv-modal .team-row__score input:focus {
    border-color: #e30613;
    box-shadow: 0 0 0 3px rgba(227,6,19,0.08);
    outline: none;
}
.kv-modal .winner-radio {
    display: flex;
    align-items: center;
    gap: 6px;
}
.kv-modal .winner-radio input[type="radio"] {
    accent-color: #e30613;
    width: 16px;
    height: 16px;
}
.kv-modal .winner-radio label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    cursor: pointer;
}

/* ── Empty State ── */
.kv-empty {
    text-align: center;
    padding: 80px 24px;
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f5;
}
.kv-empty__icon { font-size: 3.5rem; margin-bottom: 20px; color: #d1d5db; }
.kv-empty__title { font-size: 1.25rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
.kv-empty__desc { font-size: 0.9rem; color: #9ca3af; max-width: 400px; margin: 0 auto 24px; line-height: 1.6; }
.kv-empty__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: #e30613;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s;
}
.kv-empty__btn:hover { background: #c00510; }

/* ── Loading ── */
.kv-loading {
    text-align: center;
    padding: 60px 24px;
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f5;
}
.kv-loading__spinner { width: 40px; height: 40px; border: 4px solid #f3f4f6; border-top-color: #e30613; border-radius: 50%; animation: kv-spin 0.8s linear infinite; margin: 0 auto 16px; }
@keyframes kv-spin { to { transform: rotate(360deg); } }
.kv-loading__text { font-size: 0.9rem; color: #9ca3af; }

/* ── Alert Messages ── */
.kv-alert {
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 8px;
}
.kv-alert--success { background: #dcfce7; color: #166534; }
.kv-alert--error { background: #fee2e2; color: #991b1b; }
.kv-alert--info { background: #dbeafe; color: #1e40af; }

/* ── Link Button ── */
.kv-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    color: #374151;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.kv-link-btn:hover { border-color: #e30613; color: #e30613; background: #fff5f5; }

/* ── Games Table Card ── */
.kv-table-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.kv-table-card__header {
    padding: 24px 28px 0;
}
.kv-table-card__title { font-size: 1.1rem; font-weight: 700; color: #111827; }
.kv-table-card__desc { font-size: 0.83rem; color: #9ca3af; margin-top: 2px; }

/* ── Filter Bar ── */
.kv-filters {
    display: flex;
    gap: 10px;
    padding: 20px 28px;
    align-items: center;
    flex-wrap: wrap;
}
.kv-filter-input {
    padding: 10px 14px 10px 38px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.85rem;
    font-family: "Inter", sans-serif;
    color: #374151;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 fill=%22%239ca3af%22 viewBox=%220 0 16 16%22%3E%3Cpath d=%22M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242.156a5 5 0 1 1 0-10 5 5 0 0 1 0 10z%22/%3E%3C/svg%3E") no-repeat 12px center;
    transition: border-color 0.2s;
    min-width: 200px;
}
.kv-filter-input:focus { outline: none; border-color: #e30613; box-shadow: 0 0 0 3px rgba(227,6,19,0.08); }
.kv-filter-select {
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.85rem;
    font-family: "Inter", sans-serif;
    color: #374151;
    background: #fff;
    transition: border-color 0.2s;
    min-width: 150px;
    appearance: auto;
}
.kv-filter-select:focus { outline: none; border-color: #e30613; box-shadow: 0 0 0 3px rgba(227,6,19,0.08); }

/* ── Table ── */
.kv-table-card .table { margin-bottom: 0; }
.kv-table-card .table thead th {
    background: #f9fafb;
    border-bottom: 1px solid #f0f0f5;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 14px 16px;
}
.kv-table-card .table tbody td {
    padding: 14px 16px;
    font-size: 0.88rem;
    color: #374151;
    border-bottom: 1px solid #f8f9fa;
    vertical-align: middle;
}
.kv-table-card .table tbody tr { transition: background 0.15s; }
.kv-table-card .table tbody tr:hover { background: #f9fafb; }
.kv-table-card .table tbody tr:last-child td { border-bottom: none; }
.kv-table-card .tr-filtro-oculto { display: none !important; }
.kv-table-card .td-partida { font-weight: 600; color: #111827; max-width: 340px; }
.kv-table-card .td-partida small { display: block; font-weight: 400; color: #9ca3af; font-size: 0.75rem; margin-top: 2px; }
.kv-table-card .td-modalidade { color: #6b7280; font-weight: 500; }
.kv-table-card .td-data { color: #6b7280; font-size: 0.83rem; white-space: nowrap; }

/* ── Status Badges ── */
.kv-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.73rem;
    font-weight: 600;
    white-space: nowrap;
}
.kv-badge--agendado { background: #fef3c7; color: #92400e; }
.kv-badge--agendado::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; }
.kv-badge--andamento { background: #dbeafe; color: #1e40af; }
.kv-badge--andamento::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #3b82f6; }
.kv-badge--concluido, .kv-badge--finalizado { background: #dcfce7; color: #166534; }
.kv-badge--concluido::before, .kv-badge--finalizado::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }
.kv-badge--cancelado { background: #f3f4f6; color: #6b7280; }
.kv-badge--cancelado::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #9ca3af; }

/* ── Action Buttons (Table) ── */
.kv-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.85rem;
}
.kv-action:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
.kv-action--play { color: #16a34a; border-color: #bbf7d0; }
.kv-action--play:hover { background: #dcfce7; color: #15803d; }
.kv-action--edit { color: #6b7280; }
.kv-action--edit:hover { background: #f3f4f6; color: #374151; }
.kv-table-actions { display: flex; gap: 6px; justify-content: flex-end; }

/* ── History Section ── */
.kv-history-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 24px;
}
.kv-history-card__header {
    padding: 24px 28px;
    border-bottom: 1px solid #f0f0f5;
}
.kv-history-card__title { font-size: 1.1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 10px; }
.kv-history-card__title i { color: #e30613; }
.kv-history-card__body { padding: 24px 28px; }

/* ── Podium ── */
.kv-podium { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
.kv-podium-item {
    text-align: center;
    padding: 24px 16px;
    border-radius: 14px;
    border: 1px solid #f0f0f5;
    transition: all 0.25s;
}
.kv-podium-item:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
.kv-podium-item--first { background: linear-gradient(135deg, #fffbeb, #fff); border-color: #fbbf24; }
.kv-podium-item--second { background: linear-gradient(135deg, #f9fafb, #fff); border-color: #d1d5db; }
.kv-podium-item--third { background: linear-gradient(135deg, #fef3c7, #fff); border-color: #d97706; }
.kv-podium-item__icon { font-size: 2rem; margin-bottom: 8px; }
.kv-podium-item__label { font-size: 0.73rem; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
.kv-podium-item__name { font-size: 1rem; font-weight: 700; color: #111827; }

/* ── Confrontos Table ── */
.kv-confronto-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
    gap: 12px;
}
.kv-confronto-row:last-child { border-bottom: none; }
.kv-confronto-row__fase { font-weight: 700; font-size: 0.8rem; color: #6b7280; min-width: 120px; }
.kv-confronto-row__winner { font-weight: 600; color: #166534; flex: 1; }
.kv-confronto-row__score { font-weight: 800; color: #111827; font-size: 0.95rem; min-width: 50px; text-align: center; }
.kv-confronto-row__loser { color: #9ca3af; flex: 1; text-decoration: line-through; }
.kv-confronto-row__time { width: 100%; font-size: 0.78rem; color: #6b7280; margin-top: 4px; padding-top: 4px; border-top: 1px dashed #e5e7eb; }

/* ── Modal ── */
.kv-modal .modal-content { border-radius: 18px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
.kv-modal .modal-header { padding: 24px 28px 0; border: none; }
.kv-modal .modal-title { font-size: 1.15rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 10px; }
.kv-modal .modal-title i { color: #e30613; }
.kv-modal .modal-body { padding: 20px 28px; }
.kv-modal .modal-footer { padding: 0 28px 24px; border: none; }
.kv-modal .form-label { font-size: 0.78rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 6px; }
.kv-modal .form-control, .kv-modal .form-select { border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 0.9rem; transition: border-color 0.2s; }
.kv-modal .form-control:focus, .kv-modal .form-select:focus { border-color: #e30613; box-shadow: 0 0 0 3px rgba(227,6,19,0.08); }
.kv-modal .modal-summary {
    background: #f9fafb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    border: 1px solid #f0f0f5;
}
.kv-modal .modal-summary__label { font-size: 0.73rem; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.kv-modal .modal-summary__value { font-weight: 700; color: #111827; }
.kv-modal .modal-summary__sub { font-size: 0.8rem; color: #6b7280; margin-top: 2px; }
.kv-modal .btn-cancel { border: 1px solid #e5e7eb; border-radius: 10px; font-weight: 600; color: #6b7280; padding: 10px 20px; }
.kv-modal .btn-cancel:hover { background: #f3f4f6; }
.kv-modal .btn-save {
    background: #e30613;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    padding: 10px 24px;
    color: #fff;
    transition: all 0.2s;
}
.kv-modal .btn-save:hover { background: #c00510; }
.kv-modal .btn-save:disabled { background: #9ca3af; }

.edit-concluido-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    color: #92400e;
    font-size: 0.85rem;
}
.edit-concluido-banner i { color: #d97706; font-size: 1.1rem; flex-shrink: 0; }

.edit-modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 16px;
}
.edit-modal-grid__col {
    min-width: 0;
}
.edit-modal-grid .team-row { margin-bottom: 8px; }
.edit-modal-grid .form-label { margin-bottom: 6px; }

/* ── Responsive ── */
@media (max-width: 991px) {
    .kv-page { padding: 20px; }
    .kv-stats { grid-template-columns: repeat(2, 1fr); }
    .kv-gen-card__row { flex-direction: column; }
    .kv-filters { flex-direction: column; align-items: stretch; }
    .kv-filter-input, .kv-filter-select { min-width: auto; width: 100%; }
    .kv-table-card .table { font-size: 0.82rem; }
    .bracket-tree { padding-bottom: 20px; }
    .bracket-round-col { min-width: 220px; padding: 0 12px; }
}
@media (max-width: 575px) {
    .kv-stats { grid-template-columns: 1fr; }
    .kv-header { flex-direction: column; align-items: flex-start; }
    .kv-header__right { width: 100%; }
    .kv-btn-generate { width: 100%; justify-content: center; }
    .kv-podium { grid-template-columns: 1fr 1fr; }
    .kv-phase-timeline { padding: 16px; }
    .kv-table-card__header, .kv-filters, .kv-table-card .table-responsive { padding-left: 16px; padding-right: 16px; }
    .bracket-tree { flex-direction: column; align-items: stretch; overflow-x: hidden; }
    .bracket-round-col { min-width: 100%; max-width: 100%; flex: none; padding: 0; margin-bottom: 8px; }
    .bracket-round-col .bkt-match { margin-bottom: 12px; }
    .bkt-connector { display: none; }
    .bracket-champion-col { min-width: 100%; flex: none; padding: 0; margin-top: 16px; }
    .edit-modal-grid { grid-template-columns: 1fr; gap: 0; }
}

/* ── Animations ── */
@keyframes kv-fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.kv-animate { animation: kv-fadeIn 0.35s ease-out; }
.kv-table-card, .kv-history-card, .kv-gen-card, .kv-empty, .kv-loading { animation: kv-fadeIn 0.3s ease-out; }
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'chaveamento';
?>

<main class="d-md-none kv-page" style="padding:20px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="./dashboard.php" class="kv-back"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="kv-title" style="font-size:1.2rem;">Chaveamento</h4>
            <p class="kv-subtitle">Gerencie os chaveamentos do interclasse.</p>
        </div>
    </div>

    <div class="kv-stats" style="grid-template-columns:repeat(2,1fr);margin-bottom:20px;">
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--modalidades"><i class="bi bi-trophy"></i></div>
            <div class="kv-stat__info"><div class="kv-stat__number" id="statModalidadesMob">0</div><div class="kv-stat__label">Modalidades</div></div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--jogos"><i class="bi bi-game-controller"></i></div>
            <div class="kv-stat__info"><div class="kv-stat__number" id="statJogosMob">0</div><div class="kv-stat__label">Jogos</div></div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--campeoes"><i class="bi bi-award"></i></div>
            <div class="kv-stat__info"><div class="kv-stat__number" id="statCampeoesMob">0</div><div class="kv-stat__label">Campeões</div></div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--pendentes"><i class="bi bi-hourglass-split"></i></div>
            <div class="kv-stat__info"><div class="kv-stat__number" id="statPendentesMob">0</div><div class="kv-stat__label">Pendentes</div></div>
        </div>
    </div>

    <div class="kv-gen-card">
        <div class="kv-gen-card__header">
            <div class="kv-gen-card__title"><i class="bi bi-diagram-3 me-2" style="color:#e30613;"></i>Gerar novo chaveamento</div>
            <div class="kv-gen-card__desc">Selecione uma modalidade para gerar automaticamente o chaveamento.</div>
        </div>
        <div class="kv-gen-card__row">
            <select class="kv-gen-card__select" id="selectModalidadeMob">
                <option value="">Selecione uma modalidade</option>
            </select>
            <button class="kv-btn-generate" id="btnGerarChaveamentoMob">
                <i class="bi bi-diagram-3-fill"></i> Gerar
            </button>
        </div>
        <div id="msgChaveamentoMob" class="kv-alert" style="display:none;"></div>
    </div>

    <div id="bracketAreaMob" class="kv-empty">
        <div class="kv-empty__icon"><i class="bi bi-diagram-3"></i></div>
        <div class="kv-empty__title">Nenhum chaveamento disponível</div>
        <div class="kv-empty__desc">Selecione uma modalidade acima para gerar ou visualizar um chaveamento.</div>
    </div>

    <div id="historicoAreaMob" class="d-none" style="margin-top:20px;">
        <div class="kv-history-card">
            <div class="kv-history-card__header"><div class="kv-history-card__title"><i class="bi bi-trophy-fill"></i> Classificação Final</div></div>
            <div class="kv-history-card__body"><div id="podioContentMob"></div></div>
        </div>
        <div class="kv-history-card">
            <div class="kv-history-card__header"><div class="kv-history-card__title"><i class="bi bi-list-ul"></i> Confrontos Realizados</div></div>
            <div class="kv-history-card__body"><div id="confrontosContentMob"></div></div>
        </div>
    </div>

    <div id="secaoJogosMob" style="margin-top:24px;">
        <div class="kv-table-card">
            <div class="kv-table-card__header">
                <div class="kv-table-card__title">Jogos Realizados</div>
                <div class="kv-table-card__desc">Histórico de partidas concluídas.</div>
            </div>
            <div class="kv-filters" style="flex-direction:column;">
                <select class="kv-filter-select" id="filtroModalidadeJogosMob" style="width:100%;">
                    <option value="">Todas modalidades</option>
                </select>
                <select class="kv-filter-select" id="filtroCategoriaJogosMob" style="width:100%;">
                    <option value="">Todas categorias</option>
                </select>
                <input type="text" class="kv-filter-input" placeholder="Buscar partida..." id="inputBuscaJogoMob" style="width:100%;">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Partida</th><th>Modalidade</th><th>Data</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
                    <tbody id="tbodyJogosMob">
                        <tr><td colspan="5" class="text-center text-muted py-4">Carregando jogos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<main class="d-none d-md-block kv-page main-desktop-layout">

    <div class="container-fluid" style="max-width:96%;">

        <div class="kv-header">
            <div class="kv-header__left">
                <a href="./dashboard.php" id="btnVoltar" class="kv-back">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h1 class="kv-title">🏆 Chaveamentos</h1>
                    <p class="kv-subtitle">Gerencie os chaveamentos de todas as modalidades do interclasse. <span id="nomeInterclasse" class="d-none"></span></p>
                </div>
            </div>
            <div class="kv-header__right">
                <button class="kv-btn-generate" id="btnGerarChaveamento">
                    <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                </button>
            </div>
        </div>

        <div class="kv-stats">
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--modalidades"><i class="bi bi-trophy"></i></div>
                <div class="kv-stat__info"><div class="kv-stat__number" id="statModalidades">0</div><div class="kv-stat__label">Modalidades</div></div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--jogos"><i class="fa-solid fa-volleyball"></i></div>
                <div class="kv-stat__info"><div class="kv-stat__number" id="statJogos">0</div><div class="kv-stat__label">Jogos</div></div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--campeoes"><i class="bi bi-award"></i></div>
                <div class="kv-stat__info"><div class="kv-stat__number" id="statCampeoes">0</div><div class="kv-stat__label">Campeões definidos</div></div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--pendentes"><i class="bi bi-hourglass-split"></i></div>
                <div class="kv-stat__info"><div class="kv-stat__number" id="statPendentes">0</div><div class="kv-stat__label">Jogos pendentes</div></div>
            </div>
        </div>

        <div class="kv-gen-card">
            <div class="kv-gen-card__header">
                <div class="kv-gen-card__title"><i class="bi bi-diagram-3 me-2" style="color:#e30613;"></i>Gerar novo chaveamento</div>
                <div class="kv-gen-card__desc">Selecione uma modalidade para gerar automaticamente o chaveamento.</div>
            </div>
            <div class="kv-gen-card__row">
                <select class="kv-gen-card__select" id="selectModalidade">
                    <option value="">Selecione uma modalidade</option>
                </select>
            </div>
            <div class="kv-gen-card__note">⚠ Um novo chaveamento substituirá o existente para a modalidade selecionada.</div>
            <div id="msgChaveamento"></div>
            <div id="linkVerArvore" class="d-none" style="margin-top:12px;">
                <a href="#" id="btnVerArvore" class="kv-link-btn">
                    <i class="bi bi-diagram-3-fill"></i> Ver árvore do chaveamento
                </a>
            </div>
        </div>

        <div id="faseTimeline" class="kv-phase-timeline d-none"></div>

        <div id="bracketArea">
            <div class="kv-empty">
                <div class="kv-empty__icon"><i class="bi bi-diagram-3"></i></div>
                <div class="kv-empty__title">Nenhum chaveamento disponível</div>
                <div class="kv-empty__desc">Selecione uma modalidade acima para gerar ou visualizar um chaveamento.</div>
                <button class="kv-empty__btn" onclick="document.getElementById('selectModalidade').focus();">
                    <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                </button>
            </div>
        </div>

        <div id="historicoArea" class="d-none">
            <div class="kv-history-card">
                <div class="kv-history-card__header">
                    <div class="kv-history-card__title"><i class="bi bi-trophy-fill"></i> Classificação Final</div>
                </div>
                <div class="kv-history-card__body">
                    <div id="podioContent"></div>
                </div>
            </div>
            <div class="kv-history-card">
                <div class="kv-history-card__header">
                    <div class="kv-history-card__title"><i class="bi bi-list-ul"></i> Confrontos Realizados</div>
                </div>
                <div class="kv-history-card__body">
                    <div id="confrontosContent"></div>
                </div>
            </div>
        </div>

        <div id="secaoJogos" style="margin-top:24px;">
            <div class="kv-table-card">
                <div class="kv-table-card__header">
                    <div class="kv-table-card__title">Jogos Realizados</div>
                    <div class="kv-table-card__desc">Histórico de partidas concluídas.</div>
                </div>
                <div class="kv-filters">
                    <input type="text" class="kv-filter-input" placeholder="Buscar partida..." id="inputBuscaJogo">
                    <select class="kv-filter-select" id="filtroModalidadeJogos">
                        <option value="">Todas modalidades</option>
                    </select>
                    <select class="kv-filter-select" id="filtroCategoriaJogos">
                        <option value="">Todas categorias</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Partida</th>
                                <th>Modalidade</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyJogos">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Carregando jogos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade kv-modal" id="modalEditarJogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarJogo" onsubmit="return salvarEdicaoJogo(event)">
                <div class="modal-body">
                    <input type="hidden" id="editIdJogo">
                    <div class="modal-summary" id="editResumoPartida">
                        <div class="modal-summary__label">Partida</div>
                        <div class="modal-summary__value" id="editNomePartida">---</div>
                        <div class="modal-summary__sub" id="editModalidadePartida"></div>
                    </div>
                    <div id="editConcluidoBanner" class="edit-concluido-banner" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Este jogo já foi <strong>finalizado</strong>. Alterar o resultado pode afetar o chaveamento.</span>
                    </div>
                    <div class="edit-modal-grid">
                        <div class="edit-modal-grid__col">
                            <div id="editTeamsSection" style="display:none;">
                                <label class="form-label">Equipes e Placar</label>
                                <div id="editTeamsList"></div>
                                <div id="editWinnerSection" class="mt-3 grid gap-2" style="display:none;">
                                    <label class="form-label">Vencedor</label>
                                    <div id="editWinnerOptions"></div>
                                </div>
                            </div>
                        </div>
                        <div class="edit-modal-grid__col">
                            <div class="mb-3">
                                <label class="form-label">Data</label>
                                <input type="date" class="form-control" id="editDataJogo" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Início</label>
                                    <input type="time" class="form-control" id="editInicioJogo">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Término</label>
                                    <input type="time" class="form-control" id="editTerminoJogo">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Local</label>
                                <select class="form-select" id="editLocalJogo">
                                    <option value="">Selecione um local</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="editStatusJogo">
                                    <option value="Agendado">Agendado</option>
                                    <option value="Iniciado">Em Andamento</option>
                                    <option value="Concluido">Concluído</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="msgEditarJogo" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-save" id="btnSalvarJogo">
                        <i class="bi bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    let modalidadesCache = [];
    let jogosCache = [];

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclasse').innerText = dados?.nome_interclasse || 'Interclasse';

        const btnVoltar = document.getElementById('btnVoltar');
        if (btnVoltar) {
            btnVoltar.href = `./dashboard.php?id=${idInterclasse}`;
        }
        return idInterclasse;
    }

    function atualizarStats(jogos) {
        const total = modalidadesCache.length;
        const totalJogos = Array.isArray(jogos) ? jogos.length : 0;
        const concluidos = Array.isArray(jogos) ? jogos.filter(j => j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado').length : 0;
        const pendentes = totalJogos - concluidos;

        ['statModalidades', 'statModalidadesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = total;
        });
        ['statJogos', 'statJogosMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = totalJogos;
        });
        ['statCampeoes', 'statCampeoesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = concluidos;
        });
        ['statPendentes', 'statPendentesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = Math.max(0, pendentes);
        });
    }

    function atualizarTimeline(niveis) {
        const timeline = document.getElementById('faseTimeline');
        if (!niveis || niveis.length === 0) {
            timeline.classList.add('d-none');
            return;
        }

        const fases = niveis.map((n, i) => ({ nivel: n, label: (computarLabelsFases(niveis)[n]) || formatFase(n) }));
        const nivelAtual = fases[0]?.nivel || 1;

        let html = '<div class="kv-phase">';
        fases.forEach((f, i) => {
            const isUltimo = i === 0;
            const isPenultimo = i === fases.length - 1;
            let cls = 'kv-phase__item';
            if (isUltimo) cls += ' kv-phase__item--active';
            else if (i > 0) cls += ' kv-phase__item--done';

            html += `<span class="${cls}">${f.label}</span>`;
            if (i < fases.length - 1) {
                html += '<span class="kv-phase__arrow"><i class="bi bi-arrow-right"></i></span>';
            }
        });
        html += '</div>';
        timeline.innerHTML = html;
        timeline.classList.remove('d-none');
    }

    async function carregarModalidades() {
        try {
            const resp = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            modalidadesCache = Array.isArray(data) ? data : [];
            const select = document.getElementById('selectModalidade');
            select.innerHTML = '<option value="">Selecione uma modalidade</option>';
            const selectJogos = document.getElementById('filtroModalidadeJogos');
            selectJogos.innerHTML = '<option value="">Todas modalidades</option>';

            const selectMob = document.getElementById('selectModalidadeMob');
            if (selectMob) selectMob.innerHTML = '<option value="">Selecione uma modalidade</option>';
            const selectJogosMob = document.getElementById('filtroModalidadeJogosMob');
            if (selectJogosMob) selectJogosMob.innerHTML = '<option value="">Todas modalidades</option>';

            modalidadesCache.forEach(mod => {
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                const label = `${mod.nome_modalidade}${genero}${categoria}`;
                select.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                selectJogos.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                if (selectMob) selectMob.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                if (selectJogosMob) selectJogosMob.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
            });

            atualizarStats(jogosCache);
        } catch (e) {
            console.error("Erro ao carregar modalidades:", e);
        }
    }

    async function carregarCategorias() {
        const select = document.getElementById('filtroCategoriaJogos');
        const selectMob = document.getElementById('filtroCategoriaJogosMob');
        try {
            const resp = await fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            const categorias = Array.isArray(data) ? data : [];
            select.innerHTML = '<option value="">Todas categorias</option>';
            if (selectMob) selectMob.innerHTML = '<option value="">Todas categorias</option>';
            categorias.forEach(c => {
                select.innerHTML += `<option value="${c.id_categoria}">${c.nome_categoria}</option>`;
                if (selectMob) selectMob.innerHTML += `<option value="${c.id_categoria}">${c.nome_categoria}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar categorias:", e);
        }
    }

    function formatarNomePartida(jogo) {
        const tag = jogo.nome_jogo || '';
        const mm = tag.match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 16: 'Oitavas de final', 8: 'Quartas de final', 4: 'Semifinal', 2: 'Final', 1: 'Campeão' };
            const fase = fases[largura] || 'Fase ' + largura;
            const confronto = slot + 1;
            const equipes = (jogo.equipes_nomes || '').trim();
            const sufixo = kind === 'B' ? ' (bye)' : '';
            if (equipes) {
                return `${fase} — confronto ${confronto}: ${equipes}${sufixo}`;
            }
            return `${fase} — confronto ${confronto}${sufixo}`;
        }
        const pos = tag.match(/^POS:(\d+):(\d+):([NB])$/);
        if (pos) {
            const posicao = parseInt(pos[1], 10);
            const nomesPos = { 3: 'Disputa de 3º lugar', 5: 'Disputa de 5º lugar' };
            return nomesPos[posicao] || `Disputa de ${posicao}º lugar`;
        }
        return tag || '---';
    }

    let _editIdJogo = null;
    let _editJogoData = null;

    function _popularModalEdicao(jogo) {
        _editIdJogo = jogo.id_jogo;
        _editJogoData = jogo;

        document.getElementById('editIdJogo').value = jogo.id_jogo || '';
        document.getElementById('editNomePartida').textContent = formatarNomePartida(jogo);
        document.getElementById('editModalidadePartida').textContent = jogo.nome_modalidade || '';
        document.getElementById('editDataJogo').value = jogo.data_jogo || '';
        document.getElementById('editInicioJogo').value = jogo.inicio_jogo || '';
        document.getElementById('editTerminoJogo').value = jogo.termino_jogo || '';
        document.getElementById('editStatusJogo').value = jogo.status_jogo || 'Agendado';
        document.getElementById('msgEditarJogo').innerHTML = '';

        const teamsSection = document.getElementById('editTeamsSection');
        const teamsList = document.getElementById('editTeamsList');
        const winnerSection = document.getElementById('editWinnerSection');
        const winnerOptions = document.getElementById('editWinnerOptions');
        const eqs = jogo.equipes || [];

        if (eqs.length > 0 && !jogo.eh_bye) {
            teamsSection.style.display = 'block';
            let teamsHtml = '';
            eqs.forEach((eq, idx) => {
                const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                teamsHtml += `
                    <div class="team-row">
                        <div class="team-row__name">${nome}</div>
                        <div class="team-row__score">
                            <input type="number" min="0" class="form-control edit-score-input" data-equipe-id="${eq.id_equipe}" value="${eq.gols ?? 0}">
                        </div>
                    </div>`;
            });
            teamsList.innerHTML = teamsHtml;

            if (eqs.length === 2) {
                winnerSection.style.display = 'block';
                let winnerHtml = '';
                eqs.forEach(eq => {
                    const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                    const checked = jogo.equipe_vencedora_id == eq.id_equipe ? 'checked' : '';
                    winnerHtml += `
                        <div class="winner-radio">
                            <input type="radio" name="editWinner" id="winner_${eq.id_equipe}" value="${eq.id_equipe}" ${checked}>
                            <label for="winner_${eq.id_equipe}">${nome}</label>
                        </div>`;
                });
                winnerOptions.innerHTML = winnerHtml;
            } else {
                winnerSection.style.display = 'none';
                winnerOptions.innerHTML = '';
            }
        } else {
            teamsSection.style.display = 'none';
            teamsList.innerHTML = '';
            winnerSection.style.display = 'none';
            winnerOptions.innerHTML = '';
        }
    }

    function editarJogo(btn) {
        let jogo;
        try {
            jogo = JSON.parse(btn.dataset.jogo);
        } catch (e) {
            console.error('Erro ao parsear dados do jogo:', e);
            return;
        }

        _popularModalEdicao(jogo);

        var modalEl = document.getElementById('modalEditarJogo');
        var selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';

        fetch('../../../api/locais.php?id_interclasse=' + idInterclasse)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (_editIdJogo !== jogo.id_jogo) return;
                const locais = data.success && Array.isArray(data.data) ? data.data : [];
                selectLocal.innerHTML = '<option value="">Selecione um local</option>';
                locais.forEach(function(l) {
                    var sel = Number(l.id_local) === Number(jogo.locais_id_local) ? 'selected' : '';
                    selectLocal.innerHTML += '<option value="' + l.id_local + '" ' + sel + '>' + l.nome_local + '</option>';
                });
            })
            .catch(function() {
                if (_editIdJogo !== jogo.id_jogo) return;
                selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
            });

        var modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function editarJogoBracket(btn) {
        editarJogo(btn);
    }

    async function salvarEdicaoJogo(e) {
        e.preventDefault();
        var btn = document.getElementById('btnSalvarJogo');
        var msgEl = document.getElementById('msgEditarJogo');
        msgEl.innerHTML = '';

        var id_jogo = document.getElementById('editIdJogo').value;
        var data_jogo = document.getElementById('editDataJogo').value;
        var inicio_jogo = document.getElementById('editInicioJogo').value;
        var termino_jogo = document.getElementById('editTerminoJogo').value;
        var locais_id_local = document.getElementById('editLocalJogo').value;
        var status_jogo = document.getElementById('editStatusJogo').value;

        if (!data_jogo) {
            msgEl.innerHTML = '<span class="text-danger fw-bold">Selecione a data do jogo.</span>';
            return;
        }

        if (status_jogo === 'Concluido') {
            const scoreInputsCheck = document.querySelectorAll('.edit-score-input');
            if (scoreInputsCheck.length > 0) {
                const totalGols = Array.from(scoreInputsCheck).reduce((sum, inp) => sum + (Number(inp.value) || 0), 0);
                if (totalGols === 0) {
                    msgEl.innerHTML = '<span class="text-danger fw-bold">Não é possível finalizar um jogo com placar 0x0. Registre o placar correto.</span>';
                    return;
                }
            }
        }

        var payload = { id_jogo: Number(id_jogo), data_jogo: data_jogo, status_jogo: status_jogo };
        if (inicio_jogo) payload.inicio_jogo = inicio_jogo;
        if (termino_jogo) payload.termino_jogo = termino_jogo;
        if (locais_id_local) payload.locais_id_local = Number(locais_id_local);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Salvando...';

        try {
            var resp = await fetch('../../../api/jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var data = await resp.json();

            if (data.success) {
                const scoreInputs = document.querySelectorAll('.edit-score-input');
                const hasScores = scoreInputs.length > 0;
                let scoresSaved = true;

                if (hasScores) {
                    const resultados = [];
                    scoreInputs.forEach(inp => {
                        resultados.push({
                            id_equipe: Number(inp.dataset.equipeId),
                            gols: Number(inp.value) || 0
                        });
                    });

                    const scoreResp = await fetch('../../../api/lancar_resultado.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_jogo: Number(id_jogo), resultados: resultados })
                    });
                    const scoreData = await scoreResp.json();
                    scoresSaved = scoreData.success;
                    if (!scoresSaved) {
                        msgEl.innerHTML = '<span class="text-warning fw-bold">Dados atualizados, mas erro ao salvar placar: ' + (scoreData.message || '') + '</span>';
                        setTimeout(function() {
                            var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarJogo'));
                            if (m) m.hide();
                            const selectAtivo = document.getElementById('selectModalidade');
                            if (selectAtivo && selectAtivo.value) {
                                carregarArvore(selectAtivo.value);
                                carregarJogos();
                            }
                        }, 1500);
                        return;
                    }
                }

                msgEl.innerHTML = '<span class="text-success fw-bold">Jogo atualizado com sucesso!</span>';
                setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarJogo'));
                    if (m) m.hide();
                    const selectAtivo = document.getElementById('selectModalidade');
                    if (selectAtivo && selectAtivo.value) {
                        carregarArvore(selectAtivo.value);
                        carregarJogos();
                    }
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger fw-bold">' + (data.message || 'Erro ao atualizar jogo.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger fw-bold">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

    function renderizarLinhaJogo(j, labelsLarguras) {
        let dataJogo = '---';
        if (j.data_jogo) {
            try {
                dataJogo = new Date(j.data_jogo + (j.inicio_jogo ? 'T' + j.inicio_jogo : '')).toLocaleString('pt-BR');
            } catch (_) { dataJogo = j.data_jogo; }
        }
        var nomePartida = formatarNomePartida(j);
        var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
        if (m) {
            var largura = parseInt(m[1], 10);
            var labelCorreto = labelsLarguras[largura];
            if (labelCorreto) {
                var fases = { 16: 'Oitavas de final', 8: 'Quartas de final', 4: 'Semifinal', 2: 'Final', 1: 'Campeão' };
                var faseOriginal = fases[largura] || '';
                if (faseOriginal && faseOriginal !== labelCorreto) {
                    nomePartida = nomePartida.replace(faseOriginal, labelCorreto);
                }
            }
        }
        const statusLower = (j.status_jogo || '').toLowerCase();
        const statusLabel = j.status_jogo || '---';
        return `<tr>
            <td class="td-partida">${nomePartida}</td>
            <td class="td-modalidade">${j.nome_modalidade || '---'}</td>
            <td class="td-data">${dataJogo}</td>
            <td><span class="kv-badge kv-badge--${statusLower}">${statusLabel}</span></td>
            <td>
                <div class="kv-table-actions">
                    <a href="./jogos.php?id_jogo=${j.id_jogo}" class="kv-action kv-action--play" title="Acessar Jogo">
                        <i class="bi bi-play-fill"></i>
                    </a>
                    <button class="kv-action kv-action--edit" title="Editar Jogo"
                        data-jogo='${JSON.stringify(j).replace(/'/g, "&#39;")}'
                        onclick="editarJogo(this)">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    async function carregarJogos() {
        const tbody = document.getElementById('tbodyJogos');
        const tbodyMob = document.getElementById('tbodyJogosMob');
        try {
            const idModalidade = document.getElementById('filtroModalidadeJogos').value;
            const idCategoria = document.getElementById('filtroCategoriaJogos').value;

            let statsUrl = `../../../api/jogos.php?id_interclasse=${idInterclasse}`;
            const statsResp = await fetch(statsUrl);
            const statsData = await statsResp.json();
            let statsJogos = Array.isArray(statsData) ? statsData : [];
            statsJogos = statsJogos.filter(function(j) {
                var nomes = (j.equipes_nomes || '').trim();
                return nomes.indexOf(' vs ') !== -1;
            });
            atualizarStats(statsJogos);

            if (!idModalidade && !idCategoria) {
                const msg = '<tr><td colspan="5" class="text-center text-muted py-4">Selecione uma modalidade ou categoria para ver os jogos.</td></tr>';
                tbody.innerHTML = msg;
                if (tbodyMob) tbodyMob.innerHTML = msg;
                return;
            }
            let url = `../../../api/jogos.php?id_interclasse=${idInterclasse}`;
            if (idModalidade) url += `&id_modalidade=${idModalidade}`;
            if (idCategoria) url += `&id_categoria=${idCategoria}`;

            const resp = await fetch(url);
            const data = await resp.json();
            let jogos = Array.isArray(data) ? data : [];

            jogos = jogos.filter(function(j) {
                var nomes = (j.equipes_nomes || '').trim();
                return nomes.indexOf(' vs ') !== -1;
            });

            jogosCache = jogos;

            if (!jogos.length) {
                const msg = '<tr><td colspan="5" class="text-center text-muted py-4">Nenhum jogo encontrado.</td></tr>';
                tbody.innerHTML = msg;
                if (tbodyMob) tbodyMob.innerHTML = msg;
                return;
            }

            var larguras = [];
            var largurasSet = {};
            jogos.forEach(function(j) {
                var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
                if (m) {
                    var l = parseInt(m[1], 10);
                    if (l > 1 && !largurasSet[l]) { largurasSet[l] = true; larguras.push(l); }
                }
            });
            larguras.sort(function(a, b) { return b - a; });
            var labelsLarguras = {};
            var nomesPos = { 1: 'Final', 2: 'Semifinal', 3: 'Quartas de final', 4: 'Oitavas de final', 5: 'Primeira fase' };
            larguras.forEach(function(l, i) {
                var pos = larguras.length - i;
                labelsLarguras[l] = nomesPos[pos] || 'Fase ' + l;
            });

            const html = jogos.map(j => renderizarLinhaJogo(j, labelsLarguras)).join('');
            tbody.innerHTML = html;
            if (tbodyMob) tbodyMob.innerHTML = html;
        } catch (e) {
            console.error("Erro ao carregar jogos:", e);
            const msg = '<tr><td colspan="5" class="text-center text-danger py-4">Erro ao carregar jogos.</td></tr>';
            tbody.innerHTML = msg;
            if (tbodyMob) tbodyMob.innerHTML = msg;
        }
    }

    const fasesLabel = {
        1: 'Campeão',
        2: 'Final',
        4: 'Semifinal',
        8: 'Quartas de final',
        16: 'Oitavas de final'
    };

    function formatFase(faseNivel) {
        return fasesLabel[faseNivel] || `Fase ${faseNivel}`;
    }

    function computarLabelsFases(niveis) {
        const total = niveis.length;
        const nomes = { 1: 'Final', 2: 'Semifinal', 3: 'Quartas de final', 4: 'Oitavas de final', 5: 'Primeira fase' };
        const labels = {};
        niveis.forEach((n, i) => {
            const pos = total - i;
            labels[n] = nomes[pos] || `Fase ${n}`;
        });
        return labels;
    }

    function formatFaseFromNome(nomeJogo) {
        const pos = (nomeJogo || '').match(/^POS:(\d+):/);
        if (pos) {
            const p = parseInt(pos[1], 10);
            const nomesPos = { 3: 'Disputa de 3º lugar', 5: 'Disputa de 5º lugar' };
            return nomesPos[p] || `Disputa de ${p}º lugar`;
        }
        return null;
    }

    /* ── Bracket Tree Renderer ── */
    let _lastBracketData = null;
    let _pollingTimer = null;
    let _currentModalidade = null;

    function _renderBracketMatch(jogo) {
        const eqs = jogo.equipes || [];
        const isBye = jogo.eh_bye;
        const isConcluido = jogo.status_jogo === 'Concluido' || jogo.status_jogo === 'Finalizado';
        const isIniciado = jogo.status_jogo === 'Iniciado' || jogo.status_jogo === 'Andamento';
        const isPosicao = jogo.eh_disputa_posicao;
        const vencId = jogo.equipe_vencedora_id;
        const jogoData = JSON.stringify(jogo).replace(/'/g, "&#39;").replace(/"/g, "&quot;");

        let cls = 'bkt-match';
        if (isConcluido) cls += ' bkt-match--concluido';
        if (isBye) cls += ' bkt-match--bye';
        if (isPosicao) cls += ' bkt-match--posicao';

        let teamsHtml = '';
        if (eqs.length === 0) {
            teamsHtml = `<div class="bkt-team"><span class="bkt-team__name" style="color:#9ca3af;font-style:italic;">A definir</span><span class="bkt-team__score">-</span></div>
                         <div class="bkt-team"><span class="bkt-team__name" style="color:#9ca3af;font-style:italic;">A definir</span><span class="bkt-team__score">-</span></div>`;
        } else {
            eqs.forEach(eq => {
                const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                const isWinner = isConcluido && vencId && eq.id_equipe == vencId;
                const isLoser = isConcluido && vencId && eq.id_equipe != vencId && eqs.length > 1;
                let teamCls = 'bkt-team';
                if (isWinner) teamCls += ' bkt-team--winner';
                if (isLoser) teamCls += ' bkt-team--loser';
                const trophy = isWinner ? '<span class="bkt-team__trophy"><i class="bi bi-trophy-fill"></i></span>' : '';
                teamsHtml += `<div class="${teamCls}"><span class="bkt-team__name">${nome}</span>${trophy}<span class="bkt-team__score">${eq.gols ?? 0}</span></div>`;
            });
        }

        let statusLabel = jogo.status_jogo || '---';
        let statusCls = 'bkt-match__status';
        if (isBye) { statusLabel = 'Bye'; statusCls += ' bkt-match__status--bye'; }
        else if (isConcluido) { statusCls += ' bkt-match__status--concluido'; statusLabel = 'Finalizado'; }
        else if (isIniciado) { statusCls += ' bkt-match__status--andamento'; statusLabel = 'Em andamento'; }
        else { statusCls += ' bkt-match__status--agendado'; }

        const nomeModalidade = jogo.nome_modalidade || '';
        const nomeCategoria = jogo.nome_categoria || '';
        const dataJogo = jogo.data_jogo || '';
        const inicioJogo = jogo.inicio_jogo || '';
        const localJogo = jogo.nome_local || '';
        const faseLabel = isPosicao ? (formatFaseFromNome(jogo.nome_jogo) || 'Posição') : (jogo.nome_fase || '');

        let metaParts = [];
        if (faseLabel) metaParts.push(`<i class="bi bi-tag"></i><span>${faseLabel}</span>`);
        if (nomeModalidade) metaParts.push(`<span>${nomeModalidade}</span>`);
        if (dataJogo) metaParts.push(`<i class="bi bi-calendar3"></i><span>${dataJogo}${inicioJogo ? ' ' + inicioJogo.substring(0,5) : ''}</span>`);
        if (localJogo) metaParts.push(`<i class="bi bi-geo-alt"></i><span>${localJogo}</span>`);

        let actionsHtml = '';
        if (!isBye && !isConcluido && jogo.id_jogo) {
            actionsHtml += '<div class="bkt-match__actions">';
            if (jogo.status_jogo === 'Agendado') {
                actionsHtml += `<a href="./jogos.php?id_jogo=${jogo.id_jogo}" class="game-action-btn game-action-btn--start" title="Iniciar Jogo"><i class="bi bi-play-fill"></i>Iniciar</a>`;
            }
            actionsHtml += `<button class="game-action-btn game-action-btn--edit" title="Editar Jogo" onclick="editarJogoBracket(this)" data-jogo='${jogoData}'><i class="bi bi-pencil"></i>Editar</button>`;
            actionsHtml += '</div>';
        }
        if (isConcluido && jogo.id_jogo) {
            actionsHtml += '<div class="bkt-match__actions">';
            actionsHtml += `<a href="./jogos.php?id_jogo=${jogo.id_jogo}" class="game-action-btn game-action-btn--view" title="Ver resultado"><i class="bi bi-eye"></i>Ver resultado</a>`;
            actionsHtml += `<button class="game-action-btn game-action-btn--edit" title="Editar Jogo" data-jogo='${JSON.stringify(jogo).replace(/'/g, "&#39;")}' onclick="editarJogoBracket(this)"><i class="bi bi-pencil"></i>Editar</button>`;
            actionsHtml += '</div>';
        }

        return `<div class="${cls}" data-jogo-id="${jogo.id_jogo || ''}">
            ${teamsHtml}
            <div class="bkt-match__meta">
                <div class="bkt-match__info">${metaParts.join(' ')}</div>
                <span class="${statusCls}">${statusLabel}</span>
            </div>
            ${actionsHtml}
        </div>`;
    }

    function _detectarCampeao(jogos) {
        const final = jogos.find(j => j.fase_nivel === 1 && (j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado'));
        if (!final || !final.equipes || !final.equipe_vencedora_id) return null;
        const winner = final.equipes.find(eq => eq.id_equipe == final.equipe_vencedora_id);
        if (!winner) return null;
        const nome = winner.nome_fantasia || winner.nome_turma || `Equipe #${winner.id_equipe}`;
        const mod = modalidadesCache.find(m => String(m.id_modalidade) == _currentModalidade);
        const modName = mod ? `${mod.nome_modalidade}${mod.genero_modalidade ? ' ('+mod.genero_modalidade+')' : ''}${mod.nome_categoria ? ' ['+mod.nome_categoria+']' : ''}` : '';
        return { nome, modalidade: modName };
    }

    function _renderModernBracket(jogos) {
        const mmGames = jogos.filter(j => !j.eh_disputa_posicao && j.fase_nivel !== 1);
        const posGames = jogos.filter(j => j.eh_disputa_posicao);

        const rounds = {};
        mmGames.forEach(j => {
            const nivel = j.fase_nivel || 0;
            if (!rounds[nivel]) rounds[nivel] = [];
            rounds[nivel].push(j);
        });

        const niveis = Object.keys(rounds).map(Number).sort((a, b) => b - a);
        const labelsFases = computarLabelsFases(niveis);

        const campeao = _detectarCampeao(jogos);

        let html = '<div class="bracket-tree">';

        niveis.forEach((nivel, nivelIdx) => {
            const roundGames = rounds[nivel].sort((a, b) => (a.posicao_na_chave || 0) - (b.posicao_na_chave || 0));
            const matchCount = roundGames.length;

            html += '<div class="bracket-round-col">';
            html += `<div class="bracket-round-header">${labelsFases[nivel] || formatFase(nivel)}</div>`;

            roundGames.forEach((jogo, idx) => {
                html += _renderBracketMatch(jogo);
            });

            html += '</div>';

            if (nivelIdx < niveis.length - 1) {
                const nextNivel = niveis[nivelIdx + 1];
                const nextCount = rounds[nextNivel]?.length || 1;
                const connectorHeight = matchCount * 140;
                html += '<div class="bkt-connector" style="height:' + connectorHeight + 'px;"></div>';
            }
        });

        if (posGames.length > 0) {
            html += '<div class="bracket-round-col">';
            html += '<div class="bracket-round-header" style="color:#e30613;border-color:#fecaca;background:linear-gradient(135deg,#fff5f5,#fff);">Disputas de Posição</div>';
            posGames.forEach(j => { html += _renderBracketMatch(j); });
            html += '</div>';
        }

        if (campeao) {
            html += '<div class="bkt-connector" style="height:120px;"></div>';
            html += '<div class="bracket-champion-col">';
            html += '<div class="bracket-champion-card">';
            html += '<div class="bracket-champion-card__icon">🏆</div>';
            html += '<div class="bracket-champion-card__label">Campeão</div>';
            html += `<div class="bracket-champion-card__name">${campeao.nome}</div>`;
            if (campeao.modalidade) html += `<div class="bracket-champion-card__mod">${campeao.modalidade}</div>`;
            html += '</div></div>';
        }

        html += '</div>';
        return html;
    }

    function _drawConnectors(container) {
        const tree = container.querySelector('.bracket-tree');
        if (!tree) return;
        const cols = tree.querySelectorAll('.bracket-round-col');
        const connectors = tree.querySelectorAll('.bkt-connector');

        let prevMatches = [];
        cols.forEach((col, colIdx) => {
            if (colIdx === 0) {
                prevMatches = Array.from(col.querySelectorAll('.bkt-match'));
                return;
            }

            const currentMatches = Array.from(col.querySelectorAll('.bkt-match'));
            const connector = connectors[colIdx - 1];
            if (!connector || currentMatches.length === 0 || prevMatches.length === 0) {
                prevMatches = currentMatches;
                return;
            }

            const treeRect = tree.getBoundingClientRect();
            const connRect = connector.getBoundingClientRect();

            let svg = connector.querySelector('svg');
            if (!svg) {
                svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('width', '20');
                svg.style.position = 'absolute';
                svg.style.top = '0';
                svg.style.left = '0';
                svg.style.width = '100%';
                svg.style.height = '100%';
                svg.style.pointerEvents = 'none';
                connector.appendChild(svg);
            }

            const totalWidth = connector.offsetWidth;
            const totalHeight = connector.offsetHeight;
            svg.setAttribute('viewBox', `0 0 ${totalWidth} ${totalHeight}`);
            svg.innerHTML = '';

            const pairsPerParent = Math.max(1, Math.floor(prevMatches.length / currentMatches.length));

            currentMatches.forEach((match, mIdx) => {
                const matchRect = match.getBoundingClientRect();
                const matchY = matchRect.top - treeRect.top + matchRect.height / 2;

                const startIdx = mIdx * pairsPerParent;
                const endIdx = Math.min(startIdx + pairsPerParent, prevMatches.length);

                for (let i = startIdx; i < endIdx; i++) {
                    if (!prevMatches[i]) continue;
                    const prevRect = prevMatches[i].getBoundingClientRect();
                    const prevY = prevRect.top - treeRect.top + prevRect.height / 2;

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    const midX = totalWidth / 2;
                    const d = `M 0 ${prevY - connRect.top + treeRect.top - connRect.top} H ${midX} V ${matchY - connRect.top} H ${totalWidth}`;
                    path.setAttribute('d', d);
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', '#d1d5db');
                    path.setAttribute('stroke-width', '1.5');
                    path.setAttribute('stroke-linecap', 'round');
                    svg.appendChild(path);
                }
            });

            prevMatches = currentMatches;
        });
    }

    async function carregarArvore(idModalidade) {
        const area = document.getElementById('bracketArea');
        const areaMob = document.getElementById('bracketAreaMob');
        const linkArvore = document.getElementById('linkVerArvore');
        const timeline = document.getElementById('faseTimeline');

        linkArvore.classList.add('d-none');
        timeline.classList.add('d-none');

        if (!idModalidade) {
            const emptyHtml = `
                <div class="kv-empty kv-animate">
                    <div class="kv-empty__icon" style="font-size:5rem;margin-bottom:24px;">
                        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="8" y="12" width="20" height="14" rx="3" stroke="#d1d5db" stroke-width="2" fill="#f9fafb"/>
                            <rect x="8" y="54" width="20" height="14" rx="3" stroke="#d1d5db" stroke-width="2" fill="#f9fafb"/>
                            <rect x="52" y="33" width="20" height="14" rx="3" stroke="#e30613" stroke-width="2" fill="#fef2f2"/>
                            <path d="M28 19 H40 V40 H52" stroke="#d1d5db" stroke-width="1.5" fill="none"/>
                            <path d="M28 61 H40 V40 H52" stroke="#d1d5db" stroke-width="1.5" fill="none"/>
                        </svg>
                    </div>
                    <div class="kv-empty__title" style="font-size:1.4rem;">Nenhum chaveamento gerado</div>
                    <div class="kv-empty__desc" style="max-width:450px;">Selecione uma modalidade acima para gerar automaticamente o chaveamento do torneio.</div>
                    <button class="kv-empty__btn" onclick="document.getElementById('selectModalidade').focus();" style="padding:12px 28px;font-size:0.95rem;">
                        <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                    </button>
                </div>`;
            area.innerHTML = emptyHtml;
            if (areaMob) areaMob.innerHTML = emptyHtml;
            return;
        }

        _currentModalidade = idModalidade;

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const isIndividual = mod && Number(mod.id_tipo_modalidade) === 2;

        if (isIndividual) {
            const infoHtml = `
                <div class="kv-empty kv-animate">
                    <div class="kv-empty__icon"><i class="bi bi-info-circle"></i></div>
                    <div class="kv-empty__title">Modalidade Individual</div>
                    <div class="kv-empty__desc">Modalidades individuais não possuem chaveamento. Utilize a seção de Pontuação Individual para registrar 1º, 2º e 3º lugar.</div>
                </div>`;
            area.innerHTML = infoHtml;
            if (areaMob) areaMob.innerHTML = infoHtml;
            return;
        }

        const loadingHtml = `
            <div class="kv-loading kv-animate">
                <div class="kv-loading__spinner"></div>
                <div class="kv-loading__text">Carregando chaveamento...</div>
            </div>`;
        area.innerHTML = loadingHtml;
        if (areaMob) areaMob.innerHTML = loadingHtml;

        try {
            const resp = await fetch(`../../../api/chaveamento.php?id_modalidade=${idModalidade}`);
            const data = await resp.json();

            if (!data.success) {
                const errHtml = `<div class="kv-empty kv-animate"><div class="kv-empty__icon"><i class="bi bi-exclamation-triangle" style="color:#f59e0b;"></i></div><div class="kv-empty__title">Erro</div><div class="kv-empty__desc">${data.message || 'Erro ao carregar chaveamento.'}</div></div>`;
                area.innerHTML = errHtml;
                if (areaMob) areaMob.innerHTML = errHtml;
                return;
            }

            const jogos = data.jogos || [];
            _lastBracketData = jogos;

            if (jogos.length === 0) {
                const emptyHtml = `
                    <div class="kv-empty kv-animate">
                        <div class="kv-empty__icon"><i class="bi bi-diagram-3"></i></div>
                        <div class="kv-empty__title">Nenhum chaveamento gerado</div>
                        <div class="kv-empty__desc">Clique em "Gerar Chaveamento" para criar o chaveamento desta modalidade.</div>
                    </div>`;
                area.innerHTML = emptyHtml;
                if (areaMob) areaMob.innerHTML = emptyHtml;
                return;
            }

            const modernHtml = _renderModernBracket(jogos);
            area.innerHTML = modernHtml;
            if (areaMob) areaMob.innerHTML = modernHtml;

            requestAnimationFrame(() => {
                _drawConnectors(area);
                _drawConnectors(areaMob);
            });

            const niveis = [...new Set(jogos.filter(j => !j.eh_disputa_posicao && j.fase_nivel !== 1).map(j => j.fase_nivel))].sort((a,b) => b - a);
            atualizarTimeline(niveis);
            iniciarPolling();

        } catch (e) {
            console.error("Erro ao carregar árvore:", e);
            const errHtml = `<div class="kv-empty kv-animate"><div class="kv-empty__icon"><i class="bi bi-exclamation-triangle" style="color:#f59e0b;"></i></div><div class="kv-empty__title">Erro de conexão</div><div class="kv-empty__desc">Não foi possível conectar ao servidor.</div></div>`;
            area.innerHTML = errHtml;
            if (areaMob) areaMob.innerHTML = errHtml;
        }
    }

    /* ── Polling for Real-time Updates ── */
    function iniciarPolling() {
        if (_pollingTimer) clearInterval(_pollingTimer);
        _pollingTimer = setInterval(async () => {
            if (!_currentModalidade) return;
            try {
                const resp = await fetch(`../../../api/chaveamento.php?id_modalidade=${_currentModalidade}`);
                const data = await resp.json();
                if (!data.success) return;
                const jogos = data.jogos || [];

                const oldStatuses = (_lastBracketData || []).map(j => `${j.id_jogo}:${j.status_jogo}`).join(',');
                const newStatuses = jogos.map(j => `${j.id_jogo}:${j.status_jogo}`).join(',');

                if (oldStatuses !== newStatuses) {
                    _lastBracketData = jogos;
                    const modernHtml = _renderModernBracket(jogos);
                    const area = document.getElementById('bracketArea');
                    const areaMob = document.getElementById('bracketAreaMob');
                    if (area) area.innerHTML = modernHtml;
                    if (areaMob) areaMob.innerHTML = modernHtml;
                    requestAnimationFrame(() => {
                        _drawConnectors(area);
                        _drawConnectors(areaMob);
                    });
                    carregarJogos();
                }
            } catch (e) { /* silent */ }
        }, 8000);
    }

    function pararPolling() {
        if (_pollingTimer) { clearInterval(_pollingTimer); _pollingTimer = null; }
    }

    async function carregarHistorico(idModalidade) {
        const histArea = document.getElementById('historicoArea');
        const histAreaMob = document.getElementById('historicoAreaMob');
        const podioEl = document.getElementById('podioContent');
        const podioElMob = document.getElementById('podioContentMob');
        const confrontosEl = document.getElementById('confrontosContent');
        const confrontosElMob = document.getElementById('confrontosContentMob');

        try {
            const resp = await fetch(`../../../api/chaveamento.php?id_modalidade=${idModalidade}&acao=historico`);
            const data = await resp.json();

            if (!data.success || !data.concluido) {
                histArea.classList.add('d-none');
                if (histAreaMob) histAreaMob.classList.add('d-none');
                return;
            }

            histArea.classList.remove('d-none');
            if (histAreaMob) histAreaMob.classList.remove('d-none');

            const classificacao = data.classificacao || [];
            const nomesPos = ['🏆 Campeão', '🥈 Vice-campeão', '🥉 3º Lugar', '4º Lugar', '5º Lugar', '6º Lugar', '7º Lugar', '8º Lugar'];
            const cores = ['#f59e0b', '#9ca3af', '#d97706', '#6b7280', '#9ca3af', '#9ca3af', '#9ca3af', '#9ca3af'];
            const bgClasses = ['kv-podium-item--first', 'kv-podium-item--second', 'kv-podium-item--third', '', '', '', '', ''];

            let podioHtml = '<div class="kv-podium">';
            classificacao.forEach((eq, idx) => {
                const nome = eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                const label = nomesPos[idx] || `${idx+1}º Lugar`;
                const icones = ['bi-trophy-fill', 'bi-award-fill', 'bi-award', 'bi-tag', 'bi-tag', 'bi-tag', 'bi-tag', 'bi-tag'];
                podioHtml += `
                    <div class="kv-podium-item ${bgClasses[idx] || ''}">
                        <div class="kv-podium-item__icon" style="color:${cores[idx]};"><i class="bi ${icones[idx] || 'bi-tag'}"></i></div>
                        <div class="kv-podium-item__label">${label}</div>
                        <div class="kv-podium-item__name">${nome}</div>
                    </div>`;
            });
            podioHtml += '</div>';
            if (podioEl) podioEl.innerHTML = podioHtml;
            if (podioElMob) podioElMob.innerHTML = podioHtml;

            const confrontos = data.confrontos || [];
            let confHtml = '<div class="kv-table-responsive">';
            confrontos.forEach(c => {
                let tempoHtml = '';
                if (c.total_min) {
                    const extraLabel = c.tempo_extra_min ? ` (+${c.tempo_extra_min}min acrésc.)` : '';
                    tempoHtml = `<div class="kv-confronto-row__time"><i class="bi bi-clock me-1"></i>${c.total_min} min${extraLabel}</div>`;
                }
                confHtml += `<div class="kv-confronto-row">
                    <div class="kv-confronto-row__fase">${c.fase}</div>
                    <div class="kv-confronto-row__winner"><i class="bi bi-check-circle-fill me-1" style="color:#16a34a;"></i>${c.vencedor_nome}</div>
                    <div class="kv-confronto-row__score">${c.vencedor_gols} x ${c.perdedor_gols}</div>
                    <div class="kv-confronto-row__loser">${c.perdedor_nome}</div>
                    ${tempoHtml}
                </div>`;
            });
            confHtml += '</div>';
            if (confrontosEl) confrontosEl.innerHTML = confHtml;
            if (confrontosElMob) confrontosElMob.innerHTML = confHtml;

        } catch (e) {
            console.error("Erro ao carregar histórico:", e);
            histArea.classList.add('d-none');
            if (histAreaMob) histAreaMob.classList.add('d-none');
        }
    }

    document.getElementById('selectModalidade').addEventListener('change', function () {
        document.getElementById('msgChaveamento').innerHTML = '';
        document.getElementById('historicoArea').classList.add('d-none');
        document.getElementById('faseTimeline').classList.add('d-none');
        pararPolling();
        carregarArvore(this.value);
        if (this.value) {
            carregarHistorico(this.value);
        }
    });

    document.getElementById('selectModalidadeMob').addEventListener('change', function () {
        const msgMob = document.getElementById('msgChaveamentoMob');
        if (msgMob) msgMob.style.display = 'none';
        const histMob = document.getElementById('historicoAreaMob');
        if (histMob) histMob.classList.add('d-none');
        pararPolling();
        carregarArvore(this.value);
        if (this.value) {
            carregarHistorico(this.value);
        }
    });

    document.getElementById('filtroModalidadeJogos').addEventListener('change', carregarJogos);
    document.getElementById('filtroCategoriaJogos').addEventListener('change', carregarJogos);

    document.getElementById('filtroModalidadeJogosMob').addEventListener('change', carregarJogos);
    document.getElementById('filtroCategoriaJogosMob').addEventListener('change', carregarJogos);

    document.getElementById('btnGerarChaveamento').addEventListener('click', async function () {
        await gerarChaveamento(this, 'msgChaveamento');
    });

    document.getElementById('btnGerarChaveamentoMob').addEventListener('click', async function () {
        const msgEl = document.getElementById('msgChaveamentoMob');
        if (msgEl) msgEl.style.display = 'block';
        await gerarChaveamento(this, 'msgChaveamentoMob');
    });

    async function gerarChaveamento(btnEl, msgId) {
        const idModalidade = document.getElementById('selectModalidade').value;
        const msgEl = document.getElementById(msgId);
        const btn = btnEl;

        if (!idModalidade) {
            msgEl.innerHTML = '<div class="kv-alert kv-alert--error">Selecione uma modalidade primeiro.</div>';
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const tipoId = mod ? Number(mod.id_tipo_modalidade) : 0;

        if (tipoId === 2) {
            msgEl.innerHTML = '<div class="kv-alert kv-alert--info">Use a seção "Pontuação Individual" para registrar 1º, 2º e 3º lugar.</div>';
            return;
        }

        msgEl.innerHTML = '<div class="kv-alert kv-alert--info">Gerando chaveamento...</div>';

        try {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gerando...';
            const resp = await fetch('../../../api/chaveamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idModalidade), tipo_modalidade: 'mata_mata' })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao gerar chaveamento.');

            msgEl.innerHTML = `<div class="kv-alert kv-alert--success">${data.message} (${data.jogos_criados} jogos criados).</div>`;
            const linkArvore = document.getElementById('linkVerArvore');
            linkArvore.classList.remove('d-none');
            document.getElementById('btnVerArvore').href = `./chaveamento_arvore.php?id=${idInterclasse}`;
            carregarArvore(idModalidade);
            carregarJogos();
            document.getElementById('historicoArea').classList.add('d-none');
        } catch (err) {
            msgEl.innerHTML = `<div class="kv-alert kv-alert--error">${err.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-diagram-3-fill me-1"></i> Gerar Chaveamento';
        }
    }

    document.getElementById('inputBuscaJogo').addEventListener('keyup', function () {
        var termo = this.value.toLowerCase().trim();
        var linhas = document.querySelectorAll('#tbodyJogos tr');
        linhas.forEach(function(tr) {
            if (termo === '') {
                tr.classList.remove('tr-filtro-oculto');
                return;
            }
            var texto = tr.textContent.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                tr.classList.remove('tr-filtro-oculto');
            } else {
                tr.classList.add('tr-filtro-oculto');
            }
        });
    });

    document.getElementById('inputBuscaJogoMob').addEventListener('keyup', function () {
        var termo = this.value.toLowerCase().trim();
        var linhas = document.querySelectorAll('#tbodyJogosMob tr');
        linhas.forEach(function(tr) {
            if (termo === '') {
                tr.classList.remove('tr-filtro-oculto');
                return;
            }
            var texto = tr.textContent.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                tr.classList.remove('tr-filtro-oculto');
            } else {
                tr.classList.add('tr-filtro-oculto');
            }
        });
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await carregarModalidades();
        await carregarCategorias();
        await carregarJogos();
    });

    window.addEventListener('beforeunload', pararPolling);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
