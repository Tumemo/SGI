<?php
$titulo = 'Placar';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');

include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="main-desktop-layout">
    <div class="container-principal mc-page">

        <div class="mc-header">
            <div class="mc-match-info">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/agenda') ?>" id="btnVoltarPlacar" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span>Voltar</span>
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
                                <i class="bi bi-square-fill text-warning small" ></i>
                                Amarelo
                                <input type="radio" name="tipo_ocorrencia" value="Amarelo" class="d-none">
                            </label>
                            <label class="btn btn-outline-danger ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1" data-tipo="Vermelho">
                                <i class="bi bi-x-octagon-fill small" ></i>
                                Vermelho
                                <input type="radio" name="tipo_ocorrencia" value="Vermelho" class="d-none">
                            </label>
                            <label class="btn btn-outline-suspensao ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1" data-tipo="Suspensao">
                                <i class="bi bi-pause-circle-fill small" ></i>
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
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" id="btnSalvarOcorrencia">
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
                <h6 class="modal-title"><i class="bi bi-trophy-fill me-1 text-warning" ></i>Registrar ponto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formArtilheiro" onsubmit="return salvarPonto(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Equipe</label>
                        <select class="form-select" id="selectEquipeArtilheiro" onchange="carregarAlunosArtilheiro()">
                            <option value="">Selecione a equipe</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Aluno responsável pela jogada</label>
                        <select class="form-select" id="selectAlunoArtilheiro" aria-required="true">
                            <option value="">Selecione uma equipe primeiro</option>
                        </select>
                    </div>
                    <div id="msgArtilheiro" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" id="btnSalvarArtilheiro">
                        <i class="bi bi-check-lg me-1"></i>Registrar ponto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="competicoes/placar"><?= json_encode([], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/placar.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
