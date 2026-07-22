<?php
$tituloPagina = 'SGI - Placar';
$titulo = 'Placar';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';

$cssExtra = '
/* ==========================================================
   MATCH CENTER — Premium Sports UI
   ========================================================== */

/* --- Page Layout --- */
.mc-page{max-width:1100px;margin:0 auto;width:100%}

/* --- Header --- */
.mc-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap}
.mc-back-link{display:inline-flex;align-items:center;gap:.5rem;color:#6b7280;text-decoration:none;font-size:.9rem;font-weight:600;transition:color .2s;padding:.5rem .75rem;border-radius:10px;margin:-.5rem -.75rem}
.mc-back-link:hover{color:#111827;background:rgba(0,0,0,.04)}
.mc-back-link i{font-size:1.1rem;transition:transform .2s}
.mc-back-link:hover i{transform:translateX(-3px)}
.mc-match-info{display:flex;flex-direction:column;gap:.25rem;flex:1;min-width:0}
.mc-match-title{font-size:1.6rem;font-weight:800;color:#111827;letter-spacing:-.03em;line-height:1.2}
.mc-match-meta{font-size:.85rem;color:#9ca3af;font-weight:500;letter-spacing:.01em}
.mc-header-status{flex-shrink:0}

/* --- Status Badges --- */
.mc-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.45rem .9rem;border-radius:999px;font-size:.8rem;font-weight:700;letter-spacing:.02em;white-space:nowrap}
.mc-badge-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.mc-badge--live{background:#dcfce7;color:#166534}
.mc-badge--live .mc-badge-dot{background:#16a34a}
.mc-badge-dot--pulse{animation:dot-pulse 1.5s ease-in-out infinite}
.mc-badge--finished{background:#f3f4f6;color:#6b7280}
.mc-badge--finished .mc-badge-dot{background:#9ca3af}
.mc-badge--scheduled{background:#fef3c7;color:#92400e}
.mc-badge--scheduled .mc-badge-dot{background:#f59e0b}
.mc-badge--paused{background:#dbeafe;color:#1e40af}
.mc-badge--paused .mc-badge-dot{background:#3b82f6}

/* --- Actions --- */
.mc-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;align-items:center}
.mc-action-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.4rem;border-radius:12px;font-size:.9rem;font-weight:700;border:none;cursor:pointer;transition:all .2s ease;letter-spacing:-.01em}
.mc-action-btn i{font-size:1rem}
.mc-action-btn--start{background:#E30613;color:#fff;box-shadow:0 4px 14px rgba(227,6,19,.3)}
.mc-action-btn--start:hover{background:#bb0812;box-shadow:0 6px 20px rgba(227,6,19,.4);transform:translateY(-1px)}
.mc-action-btn--finish{background:#fff;color:#E30613;border:2px solid #fecdd3;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.mc-action-btn--finish:hover{background:#fef2f2;border-color:#E30613;transform:translateY(-1px)}
.mc-action-btn:active{transform:scale(.97)!important}

/* --- Scoreboard Card --- */
#placar-grid{background:#fff;border-radius:24px;box-shadow:0 4px 24px rgba(0,0,0,.06);border:1px solid #f3f4f6;padding:2.5rem 2rem 2rem;display:flex;flex-direction:column;align-items:center;gap:0;position:relative;overflow:hidden;transition:box-shadow .3s}
#placar-grid.score-blocked::after{content:"Tempo esgotado";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);color:#fff;font-size:1.25rem;font-weight:800;border-radius:24px;backdrop-filter:blur(4px);z-index:5;letter-spacing:.02em}

/* --- Timer Section --- */
.mc-timer-section{text-align:center;padding-bottom:1.75rem;width:100%;border-bottom:1px solid #f3f4f6;margin-bottom:1.75rem}
.mc-timer-time{font-size:3.5rem;font-weight:800;color:#111827;font-variant-numeric:tabular-nums;letter-spacing:.03em;line-height:1;transition:color .3s,text-shadow .3s}
.mc-timer-time.timer-expired{color:#E30613;text-shadow:0 0 30px rgba(227,6,19,.25);animation:timer-pulse .8s ease-in-out infinite alternate}
.mc-timer-time--idle{color:#d1d5db}
.mc-timer-controls{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-top:.75rem}
.mc-duration-select{appearance:none;-webkit-appearance:none;background:#f9fafb url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'%236b7280\' d=\'M2 4l4 4 4-4\'/%3E%3C/svg%3E") no-repeat right .75rem center;border:1.5px solid #e5e7eb;border-radius:8px;padding:.45rem 2rem .45rem .85rem;font-size:.8rem;font-weight:600;color:#374151;cursor:pointer;transition:border-color .2s,box-shadow .2s}
.mc-duration-select:focus{outline:none;border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.1)}
.mc-duration-select:disabled{opacity:.4;cursor:not-allowed}
.mc-pause-btn{padding:.45rem 1rem;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s}
.mc-pause-btn:hover{border-color:#E30613;color:#E30613;background:#fef2f2}

/* --- Teams Row --- */
.mc-teams{display:flex;align-items:center;justify-content:center;gap:1.5rem;width:100%}
.mc-team{flex:1;text-align:center;max-width:340px;padding:1rem 0}
.mc-team-name{font-size:1.2rem;font-weight:700;color:#1f2937;margin-bottom:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
.mc-score-row{display:flex;align-items:center;justify-content:center;gap:1rem}
.mc-score{font-size:6.5rem;font-weight:800;color:#111827;line-height:1;min-width:5rem;text-align:center;font-variant-numeric:tabular-nums;letter-spacing:-.03em;transition:color .2s}
.mc-vs{display:flex;align-items:center;justify-content:center;padding:0 .5rem;flex-shrink:0}
.mc-vs span{font-size:1.6rem;font-weight:800;color:#d1d5db;letter-spacing:.08em}

/* --- Score Buttons --- */
.btn-score{width:56px;height:56px;border-radius:16px;border:none;font-size:1.5rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s ease;line-height:1;flex-shrink:0}
.btn-score:active:not(:disabled){transform:scale(.85)}
.btn-score:disabled{opacity:.25;cursor:not-allowed}
.btn-score-minus{background:#f3f4f6;color:#9ca3af}
.btn-score-minus:hover:not(:disabled){background:#e5e7eb;color:#6b7280}
.btn-score-plus{background:#E30613;color:#fff;box-shadow:0 4px 14px rgba(227,6,19,.3)}
.btn-score-plus:hover:not(:disabled){background:#dc2626;box-shadow:0 6px 18px rgba(227,6,19,.4);transform:translateY(-1px)}
.btn-score-plus:active:not(:disabled){transform:scale(.85)!important;box-shadow:0 2px 8px rgba(227,6,19,.3)!important}

/* --- Score Animation --- */
@keyframes score-bump{0%{transform:scale(1)}40%{transform:scale(1.15)}100%{transform:scale(1)}}
.score-animate{animation:score-bump .3s cubic-bezier(.4,0,.2,1)}

/* --- Quick Stats --- */
.mc-quick-stats{display:flex;justify-content:center;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap}
.mc-stat-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .85rem;background:#f9fafb;border:1px solid #f3f4f6;border-radius:999px;font-size:.78rem;font-weight:600;color:#6b7280}
.mc-stat-chip i{font-size:.85rem;color:#9ca3af}
.mc-stat-chip-label{color:#9ca3af;font-weight:500}

/* --- Section Headers --- */
.mc-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;margin-top:2.5rem}
.mc-section-title{font-size:1.1rem;font-weight:800;color:#111827;letter-spacing:-.02em;display:flex;align-items:center;gap:.5rem;margin:0}
.mc-section-title i{color:#E30613;font-size:1.1rem}

/* --- Timeline --- */
.mc-timeline{position:relative;padding-left:1.5rem}
.tl-event{display:flex;gap:1rem;padding-bottom:1.25rem;position:relative;animation:event-slide-in .35s ease-out}
.tl-event:last-child{padding-bottom:0}
.tl-event-track{display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:16px}
.tl-event-dot{width:12px;height:12px;border-radius:50%;background:#d1d5db;z-index:1;flex-shrink:0;margin-top:6px;transition:transform .2s}
.tl-event:hover .tl-event-dot{transform:scale(1.3)}
.tl-event-line{width:2px;flex:1;background:#e5e7eb;margin-top:4px}
.tl-event--last .tl-event-line{display:none}
.tl-event--amarelo .tl-event-dot{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.15)}
.tl-event--vermelho .tl-event-dot{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.15)}
.tl-event--suspensao .tl-event-dot{background:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.15)}
.tl-event-body{flex:1;background:#fff;border-radius:14px;padding:.9rem 1.1rem;border:1px solid #f3f4f6;box-shadow:0 1px 3px rgba(0,0,0,.03);transition:box-shadow .2s,transform .2s;min-width:0}
.tl-event-body:hover{box-shadow:0 4px 14px rgba(0,0,0,.07);transform:translateY(-1px)}
.tl-event-top{display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem;flex-wrap:wrap}
.tl-event-icon{width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;color:#fff;flex-shrink:0}
.tl-event--amarelo .tl-event-icon{background:#f59e0b}
.tl-event--vermelho .tl-event-icon{background:#ef4444}
.tl-event--suspensao .tl-event-icon{background:#8b5cf6}
.tl-event-label{font-size:.85rem;font-weight:700;color:#1f2937}
.tl-badge{font-size:.7rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;background:#fef2f2;color:#dc2626}
.tl-event-player{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.15rem}
.tl-event-desc{font-size:.8rem;color:#9ca3af;line-height:1.4}
.tl-event-actions{display:flex;gap:.35rem;margin-top:.5rem;opacity:0;transition:opacity .2s}
.tl-event:hover .tl-event-actions{opacity:1}
.tl-action-btn{width:30px;height:30px;border-radius:8px;border:none;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;cursor:pointer;transition:all .15s}
.tl-action-btn--edit{background:#f3f4f6;color:#6b7280}
.tl-action-btn--edit:hover{background:#dbeafe;color:#2563eb}
.tl-action-btn--delete{background:#f3f4f6;color:#6b7280}
.tl-action-btn--delete:hover{background:#fef2f2;color:#dc2626}
.mc-timeline-empty{text-align:center;padding:3rem 1.5rem;color:#9ca3af}
.mc-timeline-empty i{font-size:2.5rem;color:#d1d5db;margin-bottom:.75rem;display:block}
.mc-timeline-empty p{font-size:.9rem;font-weight:500;margin:0}
.mc-timeline-empty--error i{color:#fca5a5}

/* --- Artilheiro --- */
.mc-artilheiro-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem}
.mc-artilheiro-card{background:#fff;border-radius:14px;padding:1.25rem;border:1px solid #f3f4f6;box-shadow:0 1px 3px rgba(0,0,0,.03);transition:box-shadow .2s,transform .2s}
.mc-artilheiro-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.07);transform:translateY(-2px)}

/* --- FAB --- */
.mc-fab{position:fixed;bottom:2rem;right:2rem;width:62px;height:62px;border-radius:50%;background:#E30613;color:#fff;border:none;box-shadow:0 6px 24px rgba(227,6,19,.4);font-size:1.5rem;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:1030;transition:all .25s cubic-bezier(.4,0,.2,1)}
.mc-fab:hover{transform:scale(1.08) translateY(-2px);box-shadow:0 10px 32px rgba(227,6,19,.5)}
.mc-fab:active{transform:scale(.95);box-shadow:0 4px 16px rgba(227,6,19,.4)}
.mc-fab i{line-height:1;transition:transform .2s}
.mc-fab:hover i{transform:rotate(90deg)}

/* --- Error / Loading --- */
.mc-error{background:#fef2f2;border:1px solid #fecdd3;color:#991b1b;padding:1rem 1.25rem;border-radius:14px;font-size:.9rem;font-weight:500;margin-bottom:1rem}
.mc-loading{text-align:center;padding:4rem 2rem;color:#9ca3af;font-size:.95rem;font-weight:500}
.mc-loading::before{content:"";display:block;width:36px;height:36px;border:3px solid #e5e7eb;border-top-color:#E30613;border-radius:50%;margin:0 auto 1rem;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.mc-empty{text-align:center;padding:3rem;color:#9ca3af;font-size:.9rem;font-weight:500}

/* --- Modal Restyling --- */
.mc-modal .modal-content{border-radius:20px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.12);overflow:hidden}
.mc-modal .modal-header{padding:1.5rem 1.5rem .75rem;border-bottom:none}
.mc-modal .modal-title{font-size:1.15rem;font-weight:800;letter-spacing:-.02em;color:#111827}
.mc-modal .modal-body{padding:.5rem 1.5rem 1rem}
.mc-modal .modal-footer{padding:.5rem 1.5rem 1.5rem;border-top:none}
.mc-modal .form-label{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;font-weight:700;margin-bottom:.35rem}
.mc-modal .form-select,.mc-modal .form-control{border-radius:10px;font-size:.875rem;padding:.6rem .85rem;border:1.5px solid #e5e7eb;transition:border-color .2s,box-shadow .2s;background-color:#fafbfc}
.mc-modal .form-select:focus,.mc-modal .form-control:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);background-color:#fff}
.mc-modal .btn-close{opacity:.4;transition:opacity .2s}
.mc-modal .btn-close:hover{opacity:.8}
.mc-modal .ocorrencia-tipo-option{border-radius:10px;padding:.5rem .9rem;font-size:.82rem;font-weight:600;border:1.5px solid #e5e7eb;transition:all .2s}
.mc-modal .ocorrencia-tipo-option:hover{transform:translateY(-1px)}
.mc-tipo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}

/* --- Animations --- */
@keyframes dot-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.7)}}
@keyframes timer-pulse{0%{opacity:1}100%{opacity:.55}}
@keyframes event-slide-in{0%{opacity:0;transform:translateY(12px)}100%{opacity:1;transform:translateY(0)}}
@keyframes alert-slide-in{0%{opacity:0;transform:translateY(-8px)}100%{opacity:1;transform:translateY(0)}}
@keyframes fab-enter{0%{opacity:0;transform:scale(.5)}100%{opacity:1;transform:scale(1)}}

/* --- Alert Second Yellow --- */
#alerta-segundo-amarelo .alert{animation:alert-slide-in .35s ease-out;border-left:4px solid #E30613!important;border-radius:14px!important;background:#fef2f2!important;color:#991b1b!important}

/* --- Responsive --- */
@media(max-width:767px){
    #placar-grid{padding:1.75rem 1rem 1.25rem;border-radius:20px}
    .mc-timer-time{font-size:2.5rem}
    .mc-teams{flex-direction:column;gap:.5rem}
    .mc-team{max-width:100%;padding:.5rem 0}
    .mc-team-name{font-size:1.05rem;margin-bottom:.5rem}
    .mc-score{font-size:4.5rem;min-width:auto}
    .mc-vs{padding:.25rem 0}
    .mc-vs span{font-size:1.2rem}
    .btn-score{width:48px;height:48px;border-radius:14px;font-size:1.3rem}
    .mc-score-row{gap:.75rem}
    .mc-header{flex-direction:column;gap:.75rem}
    .mc-header-status{align-self:flex-start}
    .mc-fab{bottom:5.5rem;right:1.25rem;width:56px;height:56px;font-size:1.3rem}
    .mc-section-header{margin-top:2rem}
    .mc-timeline{padding-left:1rem}
    .tl-event{gap:.75rem}
    .tl-event-actions{opacity:1}
    .mc-quick-stats{gap:.5rem}
    .mc-stat-chip{padding:.35rem .7rem;font-size:.72rem}
    .mc-modal .modal-content{margin:.5rem;border-radius:16px}
    .mc-modal .modal-header{padding:1.25rem 1.25rem .5rem}
    .mc-modal .modal-body{padding:.5rem 1.25rem .75rem}
    .mc-modal .modal-footer{padding:.5rem 1.25rem 1.25rem}
    .mc-tipo-grid{grid-template-columns:repeat(2,1fr)}
    .mc-modal .ocorrencia-tipo-option{padding:.45rem .75rem;font-size:.78rem}
    .mc-match-title{font-size:1.3rem}
}

@media(max-width:400px){
    .mc-score{font-size:3.5rem}
    .mc-timer-time{font-size:2rem}
    .btn-score{width:44px;height:44px;border-radius:12px;font-size:1.15rem}
}

/* --- Section 14 Override (compatibility) --- */
.time-card,.timer-wrap,.timer-display,.timer-select,.score-display,.timer-expired,.timer-pulse,.ocorrencias-card,.ocorrencia-item,.ocorrencia-tipo,.artilheiro-select-wrap,.artilheiro-list,.score-blocked,.container-principal,.section-meta,.section-header,.section-title,#placar-meta,#placar-acoes,#artilheiro-section .row.g-3>div,#alerta-segundo-amarelo .alert,.page-header,.page-title{all:unset}
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<main class="main-desktop-layout">
    <div class="container-principal mc-page">

        <div class="mc-header">
            <div class="mc-match-info">
                <a href="./edicao_agenda.php" class="mc-back-link" data-back-link="true">
                    <i class="bi bi-arrow-left"></i>
                    <span>Voltar</span>
                </a>
                <h1 id="placar-titulo-jogo" class="mc-match-title">Placar</h1>
                <span id="placar-meta" class="mc-match-meta"></span>
            </div>
            <div id="mc-status-badge" class="mc-header-status"></div>
        </div>

        <div id="placar-erro" class="mc-error d-none" role="alert"></div>
        <div id="placar-loading" class="mc-loading">Carregando partida...</div>

        <div id="placar-conteudo" class="d-none d-grid gap-3">

            <div id="placar-acoes" class="mc-actions"></div>

            <div id="placar-grid"></div>

            <div class="mc-quick-stats">
                <div class="mc-stat-chip">
                    <i class="bi bi-clock-history"></i>
                    <span id="mc-occ-count">0</span>
                    <span class="mc-stat-chip-label">ocorrências</span>
                </div>
                <div class="mc-stat-chip">
                    <i class="bi bi-stopwatch"></i>
                    <span id="mc-duration-stat">--</span>
                    <span class="mc-stat-chip-label">duração</span>
                </div>
            </div>

            <div id="artilheiro-section" class="d-none">
                <div class="mc-section-header">
                    <h2 class="mc-section-title"><i class="bi bi-trophy-fill"></i> Artilharia / Destaques</h2>
                </div>
                <div class="mc-artilheiro-grid" id="artilheiro-cards"></div>
            </div>

            <div id="ocorrencias-section" class="d-none">
                <div class="mc-section-header">
                    <h2 class="mc-section-title"><i class="bi bi-clock-history"></i> Timeline da Partida</h2>
                </div>
                <div id="lista-ocorrencias" class="mc-timeline">
                    <div class="mc-timeline-empty"><i class="bi bi-clock-history"></i><p>Nenhuma ocorrência registrada.</p></div>
                </div>
            </div>
        </div>

        <div id="alerta-segundo-amarelo"></div>
    </div>
</main>

<button type="button" class="mc-fab" id="btnNovaOcorrencia" onclick="abrirModalOcorrencia()" title="Nova ocorrência" aria-label="Registrar nova ocorrência">
    <i class="bi bi-plus-lg"></i>
</button>

<!-- Modal Ocorrência -->
<div class="modal fade mc-modal" id="modalOcorrencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Nova Ocorrência</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formOcorrencia" onsubmit="return salvarOcorrencia(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="mc-tipo-grid">
                            <label class="btn btn-outline-warning ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1" data-tipo="Amarelo">
                                <i class="bi bi-square-fill" style="color:#f59e0b;font-size:.7rem"></i>
                                Amarelo
                                <input type="radio" name="tipo_ocorrencia" value="Amarelo" class="d-none">
                            </label>
                            <label class="btn btn-outline-danger ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1" data-tipo="Vermelho">
                                <i class="bi bi-x-octagon-fill" style="font-size:.7rem"></i>
                                Vermelho
                                <input type="radio" name="tipo_ocorrencia" value="Vermelho" class="d-none">
                            </label>
                            <label class="btn btn-outline-suspensao ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1" data-tipo="Suspensao">
                                <i class="bi bi-pause-circle-fill" style="font-size:.7rem"></i>
                                Suspensão
                                <input type="radio" name="tipo_ocorrencia" value="Suspensao" class="d-none">
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Turma</label>
                        <select class="form-select" id="filtroTurmaOcorrencia" onchange="carregarAlunosOcorrencia()">
                            <option value="">Selecione a turma</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Aluno</label>
                        <select class="form-select" id="selectAlunoOcorrencia" required disabled>
                            <option value="">Selecione uma turma primeiro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penalidade (1–30)</label>
                        <select class="form-select" id="penalidadeOcorrencia">
                            <option value="0">Sem penalidade</option>
                            <?php for($i=1;$i<=30;$i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricaoOcorrencia" rows="2" required placeholder="Motivo da ocorrência..."></textarea>
                    </div>
                    <div id="msgOcorrencia" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" id="btnSalvarOcorrencia">
                        <i class="bi bi-check-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Artilheiro -->
<div class="modal fade mc-modal" id="modalArtilheiro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trophy-fill me-1" style="color:#f59e0b"></i>Registrar Gol</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formArtilheiro" onsubmit="return salvarArtilheiro(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Equipe</label>
                        <select class="form-select" id="selectEquipeArtilheiro" onchange="carregarAlunosArtilheiro()">
                            <option value="">Selecione a equipe</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jogador(a)</label>
                        <select class="form-select" id="selectAlunoArtilheiro" required>
                            <option value="">Selecione uma equipe primeiro</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Quantidade de gols neste lance</label>
                        <input type="number" class="form-control" id="numGolsArtilheiro" value="1" min="1" max="99" required>
                    </div>
                    <div id="msgArtilheiro" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" id="btnSalvarArtilheiro">
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
        var statTimer = document.getElementById('mc-duration-stat');
        if (statTimer) statTimer.textContent = m + ':' + s;
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

        // Mostrar botões de tempo extra se o jogo não estiver encerrado
        var st = estadoJogo.status_jogo;
        if (st === 'Iniciado' || st === 'Pausado') {
            mostrarBotoesTempoExtra();
        }
    }

    function mostrarBotoesTempoExtra() {
        var acoes = document.getElementById('placar-acoes');
        if (!acoes) return;
        // Evitar duplicar
        if (document.getElementById('mc-overtime-actions')) return;

        var container = document.createElement('div');
        container.id = 'mc-overtime-actions';
        container.className = 'mc-actions';
        container.style.marginTop = '1rem';

        var label = document.createElement('span');
        label.className = 'fw-bold text-danger';
        label.style.fontSize = '.9rem';
        label.innerHTML = '<i class="bi bi-stopwatch me-1"></i>Tempo esgotado — Adicionar acréscimos:';
        container.appendChild(label);

        [1, 2, 3, 5].forEach(function(min) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'mc-action-btn mc-action-btn--start';
            b.style.fontSize = '.8rem';
            b.style.padding = '.5rem 1rem';
            b.innerHTML = '<i class="bi bi-plus-circle me-1"></i>+' + min + ' min';
            b.addEventListener('click', function() {
                adicionarTempoExtra(min * 60);
            });
            container.appendChild(b);
        });

        acoes.appendChild(container);
    }

    async function adicionarTempoExtra(segundos) {
        var novoTotal = (parseInt(estadoJogo.tempo_extra_jogo, 10) || 0) + segundos;
        try {
            await fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_jogo: idJogo,
                    tempo_extra_jogo: novoTotal,
                    tempo_restante_jogo: tempoRestante + segundos
                })
            });
            estadoJogo.tempo_extra_jogo = novoTotal;
            tempoRestante = tempoRestante + segundos;
            tempoEsgotado = false;

            // Remover bloqueio visual
            var grid = document.getElementById('placar-grid');
            if (grid) grid.classList.remove('score-blocked');
            document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
                b.disabled = false;
            });

            // Remover botões de overtime
            var ov = document.getElementById('mc-overtime-actions');
            if (ov) ov.remove();

            // Reiniciar timer
            atualizarDisplayTimer();
            pausado = false;
            timerId = setInterval(function() {
                if (tempoRestante > 0) {
                    tempoRestante--;
                    atualizarDisplayTimer();
                    if (tempoRestante <= 0) {
                        bloquearPontuacao();
                    }
                }
            }, 1000);

            // Salvar estado
            fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_jogo: idJogo, tempo_restante_jogo: tempoRestante })
            }).catch(function() {});
        } catch (e) {
            alert('Erro ao adicionar tempo extra: ' + (e.message || 'Erro de conexão'));
        }
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
            // Se jogo em andamento, mostrar botões de tempo extra
            var st = estadoJogo.status_jogo;
            if (st === 'Iniciado' || st === 'Pausado') {
                document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
                    b.disabled = true;
                });
                var grid = document.getElementById('placar-grid');
                if (grid) grid.classList.add('score-blocked');
                mostrarBotoesTempoExtra();
            }
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
            // Pausar: servidor calcula e salva tempo_restante, limpa data_inicio_real
            fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_jogo: idJogo, status_jogo: 'Pausado' })
            }).catch(function() {});
            estadoJogo.status_jogo = 'Pausado';
        } else {
            // Retomar: servidor define data_inicio_real = NOW()
            fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_jogo: idJogo, status_jogo: 'Iniciado' })
            }).catch(function() {});
            estadoJogo.status_jogo = 'Iniciado';
            if (!timerId) {
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
            body: JSON.stringify({
                id_jogo: idJogo,
                status_jogo: 'Iniciado',
                duracao_jogo: duracaoJogo,
                tempo_restante_jogo: duracaoJogo,
                tempo_extra_jogo: 0
            })
        });
        estadoJogo.status_jogo = 'Iniciado';
        estadoJogo.duracao_jogo = duracaoJogo;
        estadoJogo.tempo_extra_jogo = 0;
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
        var statusEl = document.getElementById('mc-status-badge');

        titulo.textContent = formatNomeJogo(estadoJogo.nome_jogo) || 'Placar';
        meta.textContent = [
            estadoJogo.nome_modalidade,
            estadoJogo.nome_local,
            estadoJogo.data_jogo,
            estadoJogo.inicio_jogo ? estadoJogo.inicio_jogo.slice(0, 5) : ''
        ].filter(Boolean).join('  ·  ');

        var st = estadoJogo.status_jogo;
        if (statusEl) {
            var badgeClass = 'mc-badge--scheduled';
            var badgeLabel = 'Agendado';
            var dotPulse = '';
            if (st === 'Iniciado') { badgeClass = 'mc-badge--live'; badgeLabel = 'Em andamento'; dotPulse = ' mc-badge-dot--pulse'; }
            else if (st === 'Pausado') { badgeClass = 'mc-badge--paused'; badgeLabel = 'Pausado'; }
            else if (st === 'Concluido' || st === 'Finalizado') { badgeClass = 'mc-badge--finished'; badgeLabel = 'Encerrado'; }
            statusEl.innerHTML = '<span class="mc-badge ' + badgeClass + '"><span class="mc-badge-dot' + dotPulse + '"></span>' + badgeLabel + '</span>';
        }

        acoes.innerHTML = '';
        grid.innerHTML = '';
        grid.classList.remove('score-blocked');

        var emAndamento = st === 'Iniciado' || st === 'Pausado';
        var encerrado = st === 'Concluido' || st === 'Finalizado';

        if (st === 'Agendado') {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'mc-action-btn mc-action-btn--start';
            b.innerHTML = '<i class="bi bi-play-fill"></i> Iniciar jogo';
            b.addEventListener('click', function() {
                iniciarJogoServidor().catch(function(e) { alert(e.message); });
            });
            acoes.appendChild(b);
        }

        if (emAndamento && partidasLista.length >= 2) {
            var b2 = document.createElement('button');
            b2.type = 'button';
            b2.className = 'mc-action-btn mc-action-btn--finish';
            b2.innerHTML = '<i class="bi bi-stop-fill"></i> Finalizar jogo';
            b2.addEventListener('click', function() { finalizarJogo(); });
            acoes.appendChild(b2);
        }


        if (partidasLista.length === 0) {
            grid.innerHTML = '<div class="mc-empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#d1d5db"></i>Não há equipes vinculadas a este jogo. Cadastre as partidas no sistema.</div>';
            return;
        }

        var podeTimer = emAndamento || st === 'Agendado';
        var readonly = encerrado || st === 'Agendado' || tempoEsgotado;

        var html = '';

        // Timer
        if (podeTimer) {
            var selOpts = [5,10,15,20,25,30,40,45].map(function(v) {
                return '<option value="' + v + '"' + (duracaoJogo === v*60 ? ' selected' : '') + '>' + v + ' min</option>';
            }).join('');

            html += '<div class="mc-timer-section">';
            html += '<div class="mc-timer-time" id="timer-placar">' +
                String(Math.floor(duracaoJogo / 60)).padStart(2, '0') + ':' +
                String(duracaoJogo % 60).padStart(2, '0') + '</div>';
            html += '<div class="mc-timer-controls">';
            html += '<select id="select-duracao" class="mc-duration-select"' + (emAndamento ? ' disabled' : '') + '>' + selOpts + '</select>';
            if (emAndamento) {
                html += '<button type="button" class="mc-pause-btn" id="btn-pausar">' + (pausado ? 'Retomar' : 'Pausar') + '</button>';
            }
            html += '</div></div>';
        } else {
            html += '<div class="mc-timer-section"><div class="mc-timer-time mc-timer-time--idle" id="timer-placar">--:--</div></div>';
        }

        // Teams
        html += '<div class="mc-teams">';

        partidasLista.forEach(function(p, idx) {
            var gols = Math.max(0, parseInt(p.resultado_partida, 10) || 0);
            var btnMinus = readonly
                ? '<button type="button" class="btn-score btn-score-minus" disabled><i class="bi bi-dash-lg"></i></button>'
                : '<button type="button" class="btn-score btn-score-minus" data-idx="' + idx + '"><i class="bi bi-dash-lg"></i></button>';
            var btnPlus = readonly
                ? '<button type="button" class="btn-score btn-score-plus" disabled><i class="bi bi-plus-lg"></i></button>'
                : '<button type="button" class="btn-score btn-score-plus" data-idx="' + idx + '"><i class="bi bi-plus-lg"></i></button>';

            html += '<div class="mc-team" data-partida-idx="' + idx + '">';
            html += '<h3 class="mc-team-name">' + esc(nomeEquipe(p)) + '</h3>';
            html += '<div class="mc-score-row">';
            html += btnMinus;
            html += '<span class="mc-score score-number" data-gols="' + idx + '">' + String(gols).padStart(2, '0') + '</span>';
            html += btnPlus;
            html += '</div></div>';

            if (idx === 0 && partidasLista.length === 2) {
                html += '<div class="mc-vs"><span>VS</span></div>';
            }
        });

        html += '</div>';

        grid.innerHTML = html;

        // Bind events
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

        var durStat = document.getElementById('mc-duration-stat');
        if (durStat) durStat.textContent = Math.floor(duracaoJogo / 60) + ' min';
    }

    function ajustarGols(idx, delta) {
        var p = partidasLista[idx];
        var st = estadoJogo.status_jogo;
        if (!p || (st !== 'Iniciado' && st !== 'Pausado') || tempoEsgotado) return;
        var g = Math.max(0, (parseInt(p.resultado_partida, 10) || 0) + delta);
        p.resultado_partida = g;
        var el = document.querySelector('[data-gols="' + idx + '"]');
        if (el) {
            el.textContent = String(g).padStart(2, '0');
            el.classList.remove('score-animate');
            void el.offsetWidth;
            el.classList.add('score-animate');
        }
        agendarSalvarPartida(parseInt(p.id_partida, 10), g);

        if (delta > 0 && ehFutsal()) {
            var idEquipe = parseInt(p.equipes_id_equipe, 10);
            abrirModalArtilheiro(idEquipe);
        }
    }

    function ehFutsal() {
        return parseInt(estadoJogo.tipos_modalidades_id_tipo_modalidade, 10) === 1;
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

            // Restaurar duração do jogo do servidor
            if (estadoJogo.duracao_jogo) {
                var d = parseInt(estadoJogo.duracao_jogo, 10);
                if (!isNaN(d) && d > 0) duracaoJogo = d;
            }

            if (estadoJogo.tempo_restante_calculado != null) {
                var v = parseInt(estadoJogo.tempo_restante_calculado, 10);
                if (!isNaN(v) && v > 0) {
                    tempoRestante = v;
                } else if (v <= 0 && (estadoJogo.status_jogo === 'Iniciado' || estadoJogo.status_jogo === 'Pausado')) {
                    tempoRestante = 0;
                    tempoEsgotado = true;
                }
            } else if (estadoJogo.tempo_restante_jogo != null) {
                var v2 = parseInt(estadoJogo.tempo_restante_jogo, 10);
                if (!isNaN(v2) && v2 > 0) tempoRestante = v2;
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
            var countEl = document.getElementById('mc-occ-count');
            if (countEl) countEl.textContent = lista.length;
            if (!lista.length) {
                container.innerHTML = '<div class="mc-timeline-empty"><i class="bi bi-clock-history"></i><p>Nenhuma ocorrência registrada.</p></div>';
                return;
            }
            container.innerHTML = lista.map(function(o, i) {
                var tipo = (o.titulo_ocorrencia || '').toLowerCase();
                var isAmarelo = tipo === 'amarelo';
                var isVermelho = tipo.indexOf('vermelho') !== -1;
                var isSuspensao = tipo.indexOf('suspensao') !== -1 || tipo.indexOf('suspensão') !== -1;

                var cls = isAmarelo ? 'amarelo' : (isVermelho ? 'vermelho' : 'suspensao');
                var icon = isAmarelo ? 'bi-square-fill' : (isVermelho ? 'bi-x-octagon-fill' : 'bi-pause-circle-fill');
                var label = isAmarelo ? 'Cartão Amarelo' : (isVermelho ? 'Cartão Vermelho' : 'Suspensão');

                var pts = parseInt(o.penalidade, 10);
                var ptsHtml = pts > 0 ? '<span class="tl-badge">-' + pts + ' pts</span>' : '';
                var isLast = i === lista.length - 1;

                return '<div class="tl-event tl-event--' + cls + (isLast ? ' tl-event--last' : '') + '">' +
                    '<div class="tl-event-track">' +
                        '<div class="tl-event-dot"></div>' +
                        '<div class="tl-event-line"></div>' +
                    '</div>' +
                    '<div class="tl-event-body">' +
                        '<div class="tl-event-top">' +
                            '<span class="tl-event-icon"><i class="bi ' + icon + '"></i></span>' +
                            '<span class="tl-event-label">' + label + '</span>' +
                            ptsHtml +
                        '</div>' +
                        '<div class="tl-event-player">' + esc(o.nome_usuario) + '</div>' +
                        '<div class="tl-event-desc">' + esc(limparDescricaoOcorrencia(o.descricao_ocorrencia)) + '</div>' +
                        '<div class="tl-event-actions">' +
                            '<button type="button" class="tl-action-btn tl-action-btn--edit" onclick="editarOcorrencia(' + o.id_ocorrencia + ')" title="Editar"><i class="bi bi-pencil-square"></i></button>' +
                            '<button type="button" class="tl-action-btn tl-action-btn--delete" onclick="excluirOcorrencia(' + o.id_ocorrencia + ')" title="Excluir"><i class="bi bi-trash3"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');
        } catch (e) {
            container.innerHTML = '<div class="mc-timeline-empty mc-timeline-empty--error"><i class="bi bi-exclamation-circle"></i><p>Erro ao carregar ocorrências.</p></div>';
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
        carregarArtilheiros();
    }

    async function carregarArtilheiros() {
        var cards = document.getElementById('artilheiro-cards');
        if (!cards) return;
        try {
            var data = await fetchJson(API + 'artilheiro.php?id_jogo=' + idJogo);
            if (!Array.isArray(data) || data.length === 0) {
                cards.innerHTML = '<div class="mc-artilheiro-empty"><i class="bi bi-trophy"></i><p>Nenhum gol registrado ainda.</p></div>';
                return;
            }
            var html = '';
            data.forEach(function(a) {
                var nome = esc(a.nome_usuario || 'Desconhecido');
                var turma = esc(a.nome_fantasia_turma || a.nome_turma || '');
                var gols = parseInt(a.total_gols, 10) || 0;
                var icon = gols >= 3 ? 'bi-star-fill text-warning' : gols >= 2 ? 'bi-fire text-danger' : 'bi-circle-fill text-success';
                html += '<div class="mc-artilheiro-card">' +
                    '<div class="mc-artilheiro-card-icon"><i class="bi ' + icon + '"></i></div>' +
                    '<div class="mc-artilheiro-card-info">' +
                    '<div class="mc-artilheiro-card-nome">' + nome + '</div>' +
                    '<div class="mc-artilheiro-card-turma">' + turma + '</div>' +
                    '</div>' +
                    '<div class="mc-artilheiro-card-gols">' + gols + ' gol' + (gols > 1 ? 's' : '') + '</div>' +
                    '</div>';
            });
            cards.innerHTML = html;
        } catch (e) {
            cards.innerHTML = '<div class="mc-artilheiro-empty"><i class="bi bi-exclamation-circle"></i><p>Erro ao carregar artilharia.</p></div>';
        }
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
                carregarArtilheiros();
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
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
            '<div class="alert d-flex align-items-center gap-3 py-3 px-4 mb-0 rounded-3 shadow-sm border-0" role="alert" style="background:#fef2f2;color:#991b1b;">' +
                '<span style="width:36px;height:36px;font-size:1rem;flex-shrink:0;border-radius:50%;background:#ef4444;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;">V</span>' +
                '<div class="flex-grow-1">' +
                    '<strong class="d-block mb-1" style="font-size:.85rem;">SEGUNDO CARTÃO AMARELO</strong>' +
                    '<span style="font-size:.82rem;">' + esc(nome) + ' recebeu o segundo amarelo e foi expulso(a) da partida (Cartão Vermelho automático).</span>' +
                '</div>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar" style="font-size:.75rem;"></button>' +
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
