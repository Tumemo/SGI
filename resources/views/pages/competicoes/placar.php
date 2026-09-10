<?php
$titulo = 'Placar';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');

include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'dashboard';
?>

<main class="main-desktop-layout">
    <div class="container-xxl py-4 px-3 px-md-4">

        <div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
            <div class="d-flex flex-column gap-1 flex-grow-1">
                <a href="<?= \App\Shared\Http\Url::to('edicoes/agenda') ?>" id="btnVoltarPlacar" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span>Voltar</span>
                </a>
                <h1 id="placar-titulo-jogo" class="h3 fw-bold lh-sm mb-0">Placar</h1>
                <span id="placar-meta" class="small text-body-secondary"></span>
            </div>
            <div id="mc-status-badge" class="flex-shrink-0"></div>
        </div>

        <div id="placar-erro" class="alert alert-danger d-none mb-3" role="alert"></div>
        <div id="placar-loading" class="text-center py-5 text-body-secondary">
            <span class="spinner-border spinner-border-sm text-primary me-2" aria-hidden="true"></span>Carregando partida...
        </div>

        <div id="placar-conteudo" class="d-none d-grid gap-3">

            <div id="placar-acoes" class="d-flex flex-wrap gap-3 align-items-center"></div>

            <div id="placar-grid" class="card border-0 shadow-sm rounded-4 p-4 d-flex flex-column align-items-center position-relative overflow-hidden"></div>

            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                <div class="badge rounded-pill text-bg-light border text-body-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-clock-history"></i>
                    <span id="mc-occ-count">0</span>
                    <span class="fw-normal">ocorrências</span>
                </div>
                <div class="badge rounded-pill text-bg-light border text-body-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-stopwatch"></i>
                    <span id="mc-duration-stat">--</span>
                    <span class="fw-normal">duração</span>
                </div>
            </div>

            <div id="artilheiro-section" class="d-none">
                <div class="d-flex align-items-center justify-content-between mb-3 mt-5">
                    <h2 class="h5 fw-bold d-flex align-items-center gap-2 mb-0"><i class="bi bi-trophy-fill text-primary"></i> Artilharia / Destaques</h2>
                </div>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="artilheiro-cards"></div>
            </div>

            <div id="ocorrencias-section" class="d-none">
                <div class="d-flex align-items-center justify-content-between mb-3 mt-5">
                    <h2 class="h5 fw-bold d-flex align-items-center gap-2 mb-0"><i class="bi bi-clock-history text-primary"></i> Timeline da Partida</h2>
                </div>
                <div id="lista-ocorrencias" class="mc-timeline ps-3 ps-md-4">
                    <div class="text-center py-5 text-body-secondary"><i class="bi bi-clock-history fs-2 d-block mb-2 text-body-tertiary"></i><p class="mb-0">Nenhuma ocorrência registrada.</p></div>
                </div>
            </div>
        </div>

        <div id="alerta-segundo-amarelo"></div>
    </div>
</main>

<button type="button" class="mc-fab btn btn-primary rounded-circle shadow position-fixed bottom-0 end-0 mb-5 me-4 d-inline-flex align-items-center justify-content-center z-3" id="btnNovaOcorrencia" onclick="abrirModalOcorrencia()" title="Nova ocorrência" aria-label="Registrar nova ocorrência">
    <i class="bi bi-plus-lg fs-5"></i>
</button>

<!-- Modal Ocorrência -->
<div class="modal fade" id="modalOcorrencia" tabindex="-1" aria-hidden="true">
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
                        <div class="row row-cols-1 row-cols-sm-3 g-2">
                            <label class="col btn btn-outline-warning ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1 w-100" data-tipo="Amarelo">
                                <i class="bi bi-square-fill text-warning small" ></i>
                                Amarelo
                                <input type="radio" name="tipo_ocorrencia" value="Amarelo" class="d-none">
                            </label>
                            <label class="col btn btn-outline-danger ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1 w-100" data-tipo="Vermelho">
                                <i class="bi bi-x-octagon-fill small" ></i>
                                Vermelho
                                <input type="radio" name="tipo_ocorrencia" value="Vermelho" class="d-none">
                            </label>
                            <label class="col btn btn-outline-suspensao ocorrencia-tipo-option d-flex align-items-center justify-content-center gap-1 w-100" data-tipo="Suspensao">
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
<div class="modal fade" id="modalArtilheiro" tabindex="-1" aria-hidden="true">
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
