<?php
$titulo = 'Alunos da turma';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'turmas';
$podeGerenciar = $nivelUsuario === 0;
$podeExcluir   = in_array($nivelUsuario, [0], true);
$podeResetarSenha = in_array($nivelUsuario, [0], true);
?>



<!-- main mobile -->
<main class="d-md-none bg-body-tertiary min-vh-100 p-3 pt-5 pb-5" >
    <a href="#" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none" id="btnVoltarTurmaAlunosMob">
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmaAlunosMob">Interclasse</span>
    </a>

    <div class="mb-3 mt-3">
        <h1 class="h3 fw-bold text-body mb-1">Alunos da Turma: <span id="nomeTurmaMob">…</span></h1>
        <p class="small text-body-secondary mb-0"><i class="bi bi-people-fill text-primary me-1"></i> Gerencie os alunos vinculados a esta turma</p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <?php if ($podeGerenciar): ?>
        <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" onclick="abrirModalAluno()">
            <i class="bi bi-plus-lg"></i> Adicionar Aluno
        </button>
        <?php endif; ?>
        <div class="input-group flex-grow-1">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="buscaAlunoMob" placeholder="Buscar aluno por nome ou RM..." autocomplete="off">
        </div>
    </div>

    <?php if ($nivelUsuario === 0): ?>
    <div class="card border shadow-sm overflow-hidden mb-4">
        <button class="btn w-100 d-flex align-items-center justify-content-between gap-3 p-3 text-start" id="botaoPdfMob" type="button" data-bs-toggle="collapse" data-bs-target="#blocoPdfMob" aria-expanded="false" aria-controls="blocoPdfMob">
            <span class="d-flex align-items-center gap-2 fw-semibold"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Importar alunos via PDF</span>
            <span class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Administrador</span>
                <i class="bi bi-chevron-down" data-pdf-chevron></i>
            </span>
        </button>
        <div id="blocoPdfMob" class="collapse">
            <div class="card-body p-3 pt-0">
                <p class="alert alert-warning d-flex align-items-start gap-2 small mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>O PDF deve conter <strong>texto selecionável</strong> (não imagem). Se for imagem, converta antes de importar.</span>
                </p>
                <form id="formPdfTurmaMob" enctype="multipart/form-data">
                    <div class="border rounded-3 p-4 text-center bg-body-tertiary" id="dropzoneMob">
                        <input type="file" class="d-none" name="pdf" id="pdfInputMob" accept="application/pdf" required>
                        <i class="bi bi-cloud-arrow-up fs-1 text-danger"></i>
                        <p class="small text-body-secondary mb-0 mt-2"><strong>Arraste e solte o PDF aqui</strong><br>ou <span class="text-danger fw-semibold text-decoration-underline">clique para selecionar</span></p>
                        <span class="d-none small fw-semibold text-success mt-2" id="pdfNomeMob"></span>
                    </div>
                    <div class="d-none mt-3" id="progressMob">
                        <div class="progress" role="progressbar" aria-label="Progresso do upload">
                            <div class="progress-bar" id="progressBarMob"></div>
                        </div>
                        <span class="small text-body-secondary mt-1 d-block" id="progressTextoMob">Enviando…</span>
                    </div>
                    <div id="msgPdfMob" class="small mt-2 text-center"></div>
                    <div id="fallbackMob" class="d-none mt-2 text-center">
                        <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                        <a href="https://www.ilovepdf.com/pt/ocr-pdf" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                            <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                        </a>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-arrow-up"></i> Importar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="listaAlunosTurmaMob" class="d-flex flex-column gap-2"></div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3">
        <span id="taInfoPaginaMob"></span>
        <nav aria-label="Paginação">
            <ul class="pagination pagination-sm mb-0" id="paginacaoMob"></ul>
        </nav>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">

        <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
            <a href="#" id="btnVoltarTurmaAlunosDesk" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-semibold text-decoration-none">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmaAlunosDesk">Interclasse</span>
            </a>
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold text-body mb-1">Alunos da Turma: <span id="nomeTurmaDesk">…</span></h1>
                <p class="small text-body-secondary mb-0"><i class="bi bi-people-fill text-primary me-1"></i> Gerencie os alunos vinculados a esta turma</p>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <?php if ($podeGerenciar): ?>
            <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" onclick="abrirModalAluno()">
                <i class="bi bi-plus-lg"></i> Adicionar Aluno
            </button>
            <?php endif; ?>
            <div class="input-group flex-grow-1">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="buscaAlunoDesk" placeholder="Buscar aluno por nome ou RM..." autocomplete="off">
            </div>
            <span class="badge rounded-pill text-bg-light border text-body-secondary" id="contadorAlunosDesk"></span>
        </div>

        <?php if ($nivelUsuario === 0): ?>
        <div class="card border shadow-sm overflow-hidden mb-4">
            <button class="btn w-100 d-flex align-items-center justify-content-between gap-3 p-3 text-start" id="botaoPdfDesk" type="button" data-bs-toggle="collapse" data-bs-target="#blocoPdfDesk" aria-expanded="false" aria-controls="blocoPdfDesk">
                <span class="d-flex align-items-center gap-2 fw-semibold"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Importar alunos via PDF</span>
                <span class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Administrador</span>
                    <i class="bi bi-chevron-down" data-pdf-chevron></i>
                </span>
            </button>
            <div id="blocoPdfDesk" class="collapse">
                <div class="card-body p-3 pt-0">
                    <p class="alert alert-warning d-flex align-items-start gap-2 small mb-3">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>O PDF deve conter <strong>texto selecionável</strong> (não imagem). Os alunos serão vinculados automaticamente a esta turma.</span>
                    </p>
                    <form id="formPdfTurmaDesk" enctype="multipart/form-data">
                        <div class="border rounded-3 p-4 text-center bg-body-tertiary" id="dropzoneDesk">
                            <input type="file" class="d-none" name="pdf" id="pdfInputDesk" accept="application/pdf" required>
                            <i class="bi bi-cloud-arrow-up fs-1 text-danger"></i>
                            <p class="small text-body-secondary mb-0 mt-2"><strong>Arraste e solte o PDF aqui</strong><br>ou <span class="text-danger fw-semibold text-decoration-underline">clique para selecionar</span></p>
                            <span class="d-none small fw-semibold text-success mt-2" id="pdfNomeDesk"></span>
                        </div>
                        <div class="d-none mt-3" id="progressDesk">
                            <div class="progress" role="progressbar" aria-label="Progresso do upload">
                                <div class="progress-bar" id="progressBarDesk"></div>
                            </div>
                            <span class="small text-body-secondary mt-1 d-block" id="progressTextoDesk">Enviando…</span>
                        </div>
                        <div id="msgPdfDesk" class="small mt-2 text-center"></div>
                        <div id="fallbackDesk" class="d-none mt-2 text-center">
                            <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                            <a href="https://www.ilovepdf.com/pt/ocr-pdf" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                                <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                            </a>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importar PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border shadow-sm overflow-hidden mt-4">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <span class="fw-semibold"><i class="bi bi-people-fill text-primary me-1"></i> Alunos cadastrados</span>
                <span class="badge rounded-pill text-bg-light border text-body-secondary" id="taTableCount"></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>RM</th>
                            <th>Gênero</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlunosTurmaDesk"></tbody>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-3 border-top small text-body-secondary">
                <span id="taInfoPaginaDesk"></span>
                <nav aria-label="Paginação">
                    <ul class="pagination pagination-sm mb-0" id="paginacaoDesk"></ul>
                </nav>
            </div>
        </div>

    </div>
</main>

<!-- Modal visualizar aluno -->
<div class="modal fade" id="modalVerAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title">Detalhes do aluno</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="rounded-circle bg-danger-subtle text-danger-emphasis fw-bold fs-3 d-flex align-items-center justify-content-center mx-auto mb-3 p-3" id="verInicial">—</div>
                <h5 class="text-center mb-1" id="verNome">—</h5>
                <div class="text-center mb-3"><span class="badge rounded-pill text-bg-light border" id="verGenero">—</span></div>
                <div class="d-flex align-items-center justify-content-between gap-3 py-2 border-top"><span class="small text-body-secondary">RM</span><strong id="verRm">—</strong></div>
                <div class="d-flex align-items-center justify-content-between gap-3 py-2 border-top"><span class="small text-body-secondary">Data de nascimento</span><strong id="verDataNasc">—</strong></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal criar/editar aluno -->
<div class="modal fade" id="modalAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h6 class="modal-title" id="modalAlunoTitulo">Adicionar aluno</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAluno" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="alunoId" value="">
                    <div class="mb-3">
                        <label for="alunoNome" class="form-label small">Nome completo</label>
                        <input type="text" class="form-control rounded-3" id="alunoNome" required maxlength="45">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="alunoRm" class="form-label small">RM</label>
                            <input type="text" class="form-control rounded-3" id="alunoRm" required maxlength="45">
                        </div>
                        <div class="col-md-6">
                            <label for="alunoGenero" class="form-label small">Gênero</label>
                            <select class="form-select rounded-3" id="alunoGenero">
                                <option value="MASC">Masculino</option>
                                <option value="FEM">Feminino</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="alunoDataNasc" class="form-label small">Data de nascimento</label>
                        <input type="date" class="form-control rounded-3" id="alunoDataNasc" required>
                    </div>
                    <div id="msgAluno" class="small text-center"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3" id="btnSalvarAluno">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal confirmar exclusão -->
<div class="modal fade" id="modalConfirmarExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4">
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                <p class="mt-3 mb-1 fw-medium">Remover aluno?</p>
                <p class="text-muted small" id="nomeAlunoExcluir"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm rounded-3" id="btnConfirmarExcluir">Excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmar reset de senha -->
<div class="modal fade" id="modalResetarSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4">
            <div class="modal-body text-center py-4">
                <i class="bi bi-key-fill text-warning fs-1"></i>
                <p class="mt-3 mb-1 fw-medium">Resetar senha do aluno?</p>
                <p class="text-muted small mb-1" id="nomeAlunoResetar"></p>
                <p class="text-muted small">A senha será redefinida para <strong>sesi-senai</strong>, e a troca será obrigatória no próximo acesso.</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm rounded-3" id="btnConfirmarResetar">Resetar</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="participantes/turma-alunos"><?= json_encode(['value2' => (bool) $podeGerenciar, 'value3' => (bool) $podeExcluir, 'value4' => (bool) $podeResetarSenha], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/participantes/turma-alunos.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
