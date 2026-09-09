<?php
$tituloPagina = 'SGI - Alunos da turma';
$titulo = 'Alunos da turma';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'turmas';
$podeGerenciar = in_array($nivelUsuario, [0, 1], true);
$podeExcluir   = in_array($nivelUsuario, [0], true);
$podeResetarSenha = in_array($nivelUsuario, [0], true);
?>



<!-- main mobile -->
<main class="d-md-none ta-page-bg p-3 sgi-inline-b9068c61" >
    <a href="#" class="ta-btn-interclasse" id="btnVoltarTurmaAlunosMob">
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmaAlunosMob">Interclasse</span>
    </a>

    <div class="ta-title-wrap mb-3 mt-3">
        <h1 class="ta-title ta-title--mob">Alunos da Turma: <span id="nomeTurmaMob">…</span></h1>
        <p class="ta-subtitle"><i class="bi bi-people-fill"></i> Gerencie os alunos vinculados a esta turma</p>
    </div>

    <div class="ta-toolbar">
        <?php if ($podeGerenciar): ?>
        <button class="btn btn-outline-danger ta-btn-add" onclick="abrirModalAluno()">
            <i class="bi bi-plus-lg"></i> Adicionar Aluno
        </button>
        <?php endif; ?>
        <div class="ta-search">
            <i class="bi bi-search"></i>
            <input type="text" id="buscaAlunoMob" placeholder="Buscar aluno por nome ou RM..." autocomplete="off">
        </div>
    </div>

    <?php if ($nivelUsuario === 0): ?>
    <div class="ta-pdf-card">
        <button class="ta-pdf-toggle" id="botaoPdfMob" type="button" data-bs-toggle="collapse" data-bs-target="#blocoPdfMob" aria-expanded="false" aria-controls="blocoPdfMob">
            <span class="ta-pdf-toggle-label"><i class="bi bi-file-earmark-pdf-fill"></i> Importar alunos via PDF</span>
            <span class="ta-pdf-toggle-right">
                <span class="ta-pdf-badge">Administrador</span>
                <i class="bi bi-chevron-down ta-chevron"></i>
            </span>
        </button>
        <div id="blocoPdfMob" class="collapse">
            <div class="ta-pdf-body">
                <p class="ta-pdf-aviso">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>O PDF deve conter <strong>texto selecionável</strong> (não imagem). Se for imagem, converta antes de importar.</span>
                </p>
                <form id="formPdfTurmaMob" enctype="multipart/form-data">
                    <div class="ta-dropzone" id="dropzoneMob">
                        <input type="file" class="d-none" name="pdf" id="pdfInputMob" accept="application/pdf" required>
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p><strong>Arraste e solte o PDF aqui</strong><br>ou <span class="ta-link">clique para selecionar</span></p>
                        <span class="ta-file-name" id="pdfNomeMob"></span>
                    </div>
                    <div class="ta-progress" id="progressMob">
                        <div class="progress" role="progressbar" aria-label="Progresso do upload">
                            <div class="progress-bar sgi-inline-66785aa8" id="progressBarMob" ></div>
                        </div>
                        <span class="ta-progress-text" id="progressTextoMob">Enviando…</span>
                    </div>
                    <div id="msgPdfMob" class="small mt-2 text-center"></div>
                    <div id="fallbackMob" class="d-none mt-2 text-center">
                        <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                        <a href="https://www.ilovepdf.com/pt/ocr-pdf" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                            <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                        </a>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-danger ta-btn-submit">
                            <i class="bi bi-file-earmark-arrow-up"></i> Importar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="listaAlunosTurmaMob" class="d-flex flex-column gap-2"></div>

    <div class="ta-table-footer ta-footer-mob">
        <span id="taInfoPaginaMob"></span>
        <nav aria-label="Paginação">
            <ul class="pagination pagination-sm ta-pagination mb-0" id="paginacaoMob"></ul>
        </nav>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">

        <div class="ta-header">
            <a href="#" id="btnVoltarTurmaAlunosDesk" class="ta-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseTurmaAlunosDesk">Interclasse</span>
            </a>
            <div class="ta-title-wrap">
                <h1 class="ta-title">Alunos da Turma: <span id="nomeTurmaDesk">…</span></h1>
                <p class="ta-subtitle"><i class="bi bi-people-fill"></i> Gerencie os alunos vinculados a esta turma</p>
            </div>
        </div>

        <div class="ta-toolbar">
            <?php if ($podeGerenciar): ?>
            <button class="btn btn-outline-danger ta-btn-add" onclick="abrirModalAluno()">
                <i class="bi bi-plus-lg"></i> Adicionar Aluno
            </button>
            <?php endif; ?>
            <div class="ta-search">
                <i class="bi bi-search"></i>
                <input type="text" id="buscaAlunoDesk" placeholder="Buscar aluno por nome ou RM..." autocomplete="off">
            </div>
            <span class="ta-count" id="contadorAlunosDesk"></span>
        </div>

        <?php if ($nivelUsuario === 0): ?>
        <div class="ta-pdf-card">
            <button class="ta-pdf-toggle" id="botaoPdfDesk" type="button" data-bs-toggle="collapse" data-bs-target="#blocoPdfDesk" aria-expanded="false" aria-controls="blocoPdfDesk">
                <span class="ta-pdf-toggle-label"><i class="bi bi-file-earmark-pdf-fill"></i> Importar alunos via PDF</span>
                <span class="ta-pdf-toggle-right">
                    <span class="ta-pdf-badge">Administrador</span>
                    <i class="bi bi-chevron-down ta-chevron"></i>
                </span>
            </button>
            <div id="blocoPdfDesk" class="collapse">
                <div class="ta-pdf-body">
                    <p class="ta-pdf-aviso">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>O PDF deve conter <strong>texto selecionável</strong> (não imagem). Os alunos serão vinculados automaticamente a esta turma.</span>
                    </p>
                    <form id="formPdfTurmaDesk" enctype="multipart/form-data">
                        <div class="ta-dropzone" id="dropzoneDesk">
                            <input type="file" class="d-none" name="pdf" id="pdfInputDesk" accept="application/pdf" required>
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p><strong>Arraste e solte o PDF aqui</strong><br>ou <span class="ta-link">clique para selecionar</span></p>
                            <span class="ta-file-name" id="pdfNomeDesk"></span>
                        </div>
                        <div class="ta-progress" id="progressDesk">
                            <div class="progress" role="progressbar" aria-label="Progresso do upload">
                                <div class="progress-bar sgi-inline-66785aa8" id="progressBarDesk" ></div>
                            </div>
                            <span class="ta-progress-text" id="progressTextoDesk">Enviando…</span>
                        </div>
                        <div id="msgPdfDesk" class="small mt-2 text-center"></div>
                        <div id="fallbackDesk" class="d-none mt-2 text-center">
                            <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                            <a href="https://www.ilovepdf.com/pt/ocr-pdf" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                                <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                            </a>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-danger ta-btn-submit">
                                <i class="bi bi-file-earmark-arrow-up"></i> Importar PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="ta-table-card mt-4">
            <div class="ta-table-card-header">
                <span class="ta-title-label"><i class="bi bi-people-fill"></i> Alunos cadastrados</span>
                <span class="ta-badge-count" id="taTableCount"></span>
            </div>
            <div class="table-responsive">
                <table class="table ta-table align-middle mb-0">
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
            <div class="ta-table-footer">
                <span id="taInfoPaginaDesk"></span>
                <nav aria-label="Paginação">
                    <ul class="pagination pagination-sm ta-pagination mb-0" id="paginacaoDesk"></ul>
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
                <div class="ta-view-avatar" id="verInicial">—</div>
                <h5 class="text-center mb-1" id="verNome">—</h5>
                <div class="text-center mb-3"><span class="ta-badge-genero" id="verGenero">—</span></div>
                <div class="ta-view-row"><span>RM</span><strong id="verRm">—</strong></div>
                <div class="ta-view-row"><span>Data de nascimento</span><strong id="verDataNasc">—</strong></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-danger btn-sm rounded-3 px-4" data-bs-dismiss="modal">Fechar</button>
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
                    <button type="submit" class="btn btn-danger btn-sm rounded-3" id="btnSalvarAluno">Salvar</button>
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
                <p class="text-muted small">A senha voltará para o padrão <strong>123</strong> e o aluno deverá trocá-la no próximo acesso.</p>
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
