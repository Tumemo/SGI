<?php
$tituloPagina = 'SGI - Alunos da turma';
$titulo = 'Alunos da turma';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'turmas';
$podeGerenciar = in_array($nivelUsuario, [0, 1, 2], true);
?>

<style>
    :root {
        --ta-red: #E30613;
        --ta-red-dark: #b9050f;
        --ta-bg: #f8f9fa;
        --ta-border: #eceef1;
        --ta-text: #1f2937;
        --ta-text-2: #6b7280;
        --ta-text-3: #9ca3af;
        --ta-surface: #ffffff;
        --ta-radius: 16px;
        --ta-shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.06);
        --ta-shadow-hover: 0 6px 20px rgba(16,24,40,.08);
    }

    .ta-page-bg { background-color: var(--ta-bg); min-height: 100vh; }

    /* ── Cabeçalho ─────────────────────────────────────────────── */
    .ta-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }

    .ta-btn-interclasse {
        display: inline-flex; align-items: center; gap: .5rem;
        background: var(--ta-red); color: #fff; font-weight: 600;
        border-radius: 8px; padding: 8px 16px; text-decoration: none;
        box-shadow: 0 2px 8px rgba(227,6,19,.25);
        transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
        flex-shrink: 0;
    }
    .ta-btn-interclasse:hover { background: var(--ta-red-dark); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(227,6,19,.35); }

    .ta-title-wrap { min-width: 0; }
    .ta-title { font-size: 1.55rem; font-weight: 600; color: var(--ta-text); margin: 0; letter-spacing: -.01em; }
    .ta-title--mob { font-size: 1.3rem; }
    .ta-title span { color: var(--ta-red); }
    .ta-subtitle { font-size: .85rem; color: var(--ta-text-2); margin: .3rem 0 0; }
    .ta-subtitle i { color: var(--ta-red); margin-right: .25rem; }

    /* ── Barra de ferramentas ──────────────────────────────────── */
    .ta-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-bottom: 1.25rem; }

    .ta-search { position: relative; flex: 1 1 260px; min-width: 220px; max-width: 460px; }
    .ta-search > i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--ta-text-3); font-size: .9rem; }
    .ta-search input {
        width: 100%; border: 1.5px solid #e2e5e9; border-radius: 11px;
        padding: .55rem .9rem .55rem 2.35rem; font-size: .9rem;
        background: #fff; color: var(--ta-text);
        transition: border-color .2s, box-shadow .2s;
    }
    .ta-search input::placeholder { color: #a6adb6; }
    .ta-search input:focus { outline: none; border-color: var(--ta-red); box-shadow: 0 0 0 3px rgba(227,6,19,.1); }

    .ta-btn-add { border-radius: 10px; font-weight: 600; padding: .5rem 1.1rem; border-width: 1.5px; }
    .ta-btn-add:hover { box-shadow: 0 4px 12px rgba(227,6,19,.2); }
    .ta-btn-submit { border-radius: 10px; font-weight: 600; padding: .6rem 1.25rem; }

    .ta-count {
        font-size: .78rem; color: var(--ta-text-2); background: #fff;
        border: 1px solid var(--ta-border); padding: .35rem .8rem;
        border-radius: 50px; white-space: nowrap;
    }

    /* ── Card PDF (accordion) ──────────────────────────────────── */
    .ta-pdf-card {
        background: #fff; border: 1px solid var(--ta-border);
        border-radius: var(--ta-radius); box-shadow: var(--ta-shadow);
        overflow: hidden; margin-bottom: 1.5rem;
    }
    .ta-pdf-toggle {
        width: 100%; display: flex; align-items: center; justify-content: space-between;
        gap: 1rem; padding: 1rem 1.25rem; background: #fff; border: none; text-align: left;
        transition: background .2s;
    }
    .ta-pdf-toggle:hover, .ta-pdf-toggle.aberto { background: #fafbfc; }
    .ta-pdf-toggle-label { display: flex; align-items: center; gap: .7rem; font-weight: 600; color: var(--ta-text); font-size: .95rem; }
    .ta-pdf-toggle-label > i { color: var(--ta-red); font-size: 1.35rem; }
    .ta-pdf-toggle-right { display: flex; align-items: center; gap: .6rem; }
    .ta-pdf-badge {
        font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
        color: var(--ta-red); background: #fdecec; padding: .22rem .6rem; border-radius: 50px;
    }
    .ta-chevron { color: var(--ta-text-3); transition: transform .25s ease; }
    .ta-pdf-toggle.aberto .ta-chevron { transform: rotate(180deg); }

    .ta-pdf-body { padding: 0 1.25rem 1.25rem; }
    .ta-pdf-aviso {
        display: flex; align-items: flex-start; gap: .5rem;
        background: #fff8e6; border: 1px solid #fdecc8; color: #8a6d1a;
        font-size: .82rem; border-radius: 10px; padding: .65rem .8rem; margin: 0 0 1rem;
    }
    .ta-pdf-aviso i { color: #e0a800; margin-top: .05rem; }

    .ta-dropzone {
        border: 2px dashed #d3d7dd; border-radius: 14px; background: #f9fafb;
        padding: 1.9rem 1rem; text-align: center; cursor: pointer;
        transition: border-color .2s, background .2s, transform .15s;
    }
    .ta-dropzone:hover { border-color: var(--ta-red); background: #fff7f7; }
    .ta-dropzone--dragover { border-color: var(--ta-red); background: #ffecec; transform: scale(1.01); }
    .ta-dropzone--has-file { border-style: solid; border-color: #c6e0d1; background: #f4fbf7; }
    .ta-dropzone > i { font-size: 2.3rem; color: var(--ta-red); opacity: .75; display: block; margin-bottom: .35rem; }
    .ta-dropzone--has-file > i { color: #198754; }
    .ta-dropzone p { margin: .35rem 0 0; color: var(--ta-text-2); font-size: .88rem; }
    .ta-dropzone p strong { color: var(--ta-text); }
    .ta-dropzone p .ta-link { color: var(--ta-red); font-weight: 600; text-decoration: underline; }
    .ta-file-name { display: none; margin-top: .55rem; font-size: .8rem; font-weight: 600; color: #198754; }
    .ta-file-name.visivel { display: inline-block; }

    .ta-progress { display: none; margin-top: 1rem; }
    .ta-progress .progress { height: 8px; border-radius: 50px; background: #f1f3f5; }
    .ta-progress .progress-bar { background: var(--ta-red); border-radius: 50px; transition: width .25s ease; }
    .ta-progress-text { font-size: .78rem; color: var(--ta-text-2); margin-top: .4rem; display: block; }

    /* ── Card Table ────────────────────────────────────────────── */
    .ta-table-card {
        background: #fff; border: 1px solid var(--ta-border);
        border-radius: var(--ta-radius); overflow: hidden; box-shadow: var(--ta-shadow);
    }
    .ta-table-card-header {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f3f5;
    }
    .ta-table-card-header .ta-title-label { font-weight: 600; color: var(--ta-text); font-size: .95rem; display: inline-flex; align-items: center; gap: .5rem; }
    .ta-table-card-header .ta-title-label i { color: var(--ta-red); }
    .ta-table-card-header .ta-badge-count { background: #f3f4f6; color: #4b5563; font-size: .75rem; font-weight: 600; padding: .3rem .75rem; border-radius: 50px; }

    .ta-table thead th {
        background: #fafbfc; color: #6b7280; font-size: .72rem; text-transform: uppercase;
        letter-spacing: .05em; font-weight: 600; padding: .85rem 1.25rem;
        border-bottom: 1px solid #f1f3f5; white-space: nowrap;
    }
    .ta-table tbody td { padding: .8rem 1.25rem; border-bottom: 1px solid #f7f8f9; color: #374151; font-size: .9rem; vertical-align: middle; }
    .ta-table tbody tr { transition: background .15s ease; }
    .ta-table tbody tr:hover { background: #fafbfc; }
    .ta-table tbody tr:last-child td { border-bottom: none; }

    .ta-cell-nome { font-weight: 500; color: var(--ta-text); }
    .ta-table-avatar {
        width: 30px; height: 30px; border-radius: 9px; background: #fdecec; color: var(--ta-red);
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: .78rem; margin-right: .65rem; vertical-align: middle;
    }
    .ta-badge-genero {
        display: inline-flex; align-items: center; gap: .3rem; background: #f3f4f6;
        color: #4b5563; font-size: .75rem; font-weight: 600; padding: .25rem .65rem; border-radius: 50px;
    }
    .ta-badge-genero--fem { background: #fce7f3; color: #be185d; }

    .ta-badge-sem-inscricao {
        display: inline-block; background: var(--ta-red); color: #fff;
        font-size: .66rem; font-weight: 600; padding: 2px 8px;
        border-radius: 50px; margin-left: .5rem; vertical-align: middle;
    }
    .ta-tr-sem-inscricao td { background: #fff7f7; }
    .ta-tr-sem-inscricao td:first-child { box-shadow: inset 3px 0 0 var(--ta-red); }

    .ta-action {
        width: 32px; height: 32px; border-radius: 10px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .9rem; cursor: pointer; transition: all .18s ease;
    }
    .ta-action--view { background: #eef4ff; color: #2563eb; }
    .ta-action--view:hover { background: #2563eb; color: #fff; transform: translateY(-1px); }
    .ta-action--edit { background: #f3f4f6; color: #4b5563; }
    .ta-action--edit:hover { background: #374151; color: #fff; transform: translateY(-1px); }
    .ta-action--delete { background: #feeaea; color: #dc2626; }
    .ta-action--delete:hover { background: #dc2626; color: #fff; transform: translateY(-1px); }

    .ta-table-footer {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .75rem; padding: .9rem 1.25rem;
        border-top: 1px solid #f1f3f5; font-size: .82rem; color: var(--ta-text-2);
    }
    .ta-footer-mob { border-top: none; padding: .75rem 0 0; }
    .ta-pagination { margin: 0; }
    .ta-pagination .page-link {
        border: none; border-radius: 8px; margin: 0 2px; color: var(--ta-text-2);
        font-size: .8rem; min-width: 30px; text-align: center; padding: .3rem .55rem;
    }
    .ta-pagination .page-item.active .page-link { background: var(--ta-red); color: #fff; }
    .ta-pagination .page-link:hover { background: #f3f4f6; color: var(--ta-red); }
    .ta-pagination .page-item.disabled .page-link { opacity: .4; pointer-events: none; }

    /* ── Cards mobile (lista de alunos) ────────────────────────── */
    .ta-student-card {
        display: flex; align-items: center; gap: .75rem; background: #fff;
        border: 1px solid var(--ta-border); border-radius: 14px; padding: .75rem .9rem;
        box-shadow: 0 1px 2px rgba(16,24,40,.03);
        transition: border-color .2s, box-shadow .2s;
    }
    .ta-student-card:hover { border-color: #f1c4c7; box-shadow: var(--ta-shadow-hover); }
    .ta-student-card.sem-inscricao { border-color: var(--ta-red); background: #fff7f7; }
    .ta-student-avatar {
        width: 40px; height: 40px; border-radius: 12px; background: #fdecec; color: var(--ta-red);
        font-weight: 600; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1rem;
    }
    .ta-student-info { flex: 1; min-width: 0; }
    .ta-student-name { font-weight: 600; color: var(--ta-text); font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ta-student-meta { color: var(--ta-text-2); font-size: .78rem; }

    .ta-empty { text-align: center; padding: 2.5rem 1rem; color: var(--ta-text-2); }
    .ta-empty i { font-size: 2.2rem; color: #d1d5db; margin-bottom: .6rem; display: block; }
    .ta-empty p { margin: 0; }
    .ta-empty--cell { background: #fff; border: 1px solid var(--ta-border); border-radius: var(--ta-radius); padding: 2.5rem 1rem; }

    /* ── Modal visualizar aluno ────────────────────────────────── */
    .ta-view-avatar {
        width: 64px; height: 64px; border-radius: 18px; background: #fdecec; color: var(--ta-red);
        font-weight: 700; font-size: 1.4rem; display: flex; align-items: center;
        justify-content: center; margin: 0 auto 1rem;
    }
    .ta-view-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 0; border-top: 1px solid #f1f3f5; }
    .ta-view-row span { color: var(--ta-text-2); font-size: .82rem; }
    .ta-view-row strong { color: var(--ta-text); font-size: .9rem; font-weight: 600; }
</style>

<!-- main mobile -->
<main class="d-md-none ta-page-bg p-3" style="padding-top: 5.5rem; padding-bottom: 6rem;">
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
                            <div class="progress-bar" id="progressBarMob" style="width:0%"></div>
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
                                <div class="progress-bar" id="progressBarDesk" style="width:0%"></div>
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

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = Number(params.get('id') || 0);
    const idCategoria = Number(params.get('id_categoria') || 0);
    const idTurma = Number(params.get('id_turma') || 0);
    const podeGerenciar = <?= $podeGerenciar ? 'true' : 'false' ?>;

    const POR_PAGINA = 10;
    let alunosTodos = [];
    let alunosMap = {};
    let paginaAtual = 1;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function normalizar(s) {
        return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function generoLabel(g) {
        if (g === 'FEM') return 'Feminino';
        if (g === 'MASC') return 'Masculino';
        return 'Não informado';
    }

    function formatarData(s) {
        if (!s) return '—';
        const partes = String(s).split('-');
        return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : s;
    }

    function setNomeTurma(nome) {
        ['nomeTurmaDesk', 'nomeTurmaMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = nome;
        });
        document.title = `SGI - Alunos da Turma: ${nome}`;
    }

    function setVoltar() {
        const q = new URLSearchParams();
        if (idInterclasse) q.set('id', idInterclasse);
        if (idCategoria) q.set('id_categoria', idCategoria);
        const pagina = idCategoria ? 'turmas' : 'edicao_turmas';
        const href = `./${pagina}.php?${q.toString()}`;
        ['btnVoltarTurmaAlunosMob', 'btnVoltarTurmaAlunosDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = href;
        });
    }

    async function carregarNomeInterclasseTurmaAlunos() {
        if (!idInterclasse) return;
        try {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseTurmaAlunosMob', 'nomeInterclasseTurmaAlunosDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = nome;
            });
        } catch (e) {}
    }

    async function carregarAlunos() {
        await carregarNomeInterclasseTurmaAlunos();
        setVoltar();
        if (!idTurma || isNaN(idTurma) || !idInterclasse || isNaN(idInterclasse)) {
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-muted">Parâmetros inválidos.</p>';
            return;
        }
        let nomeTurma = '';
        try {
            const rT = await fetch(`${API}turmas.php?id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const textTurmas = await rT.text();
            let turmas = null;
            try { turmas = JSON.parse(textTurmas || 'null'); } catch (_) { turmas = null; }
            const t = Array.isArray(turmas) ? turmas[0] : null;
            nomeTurma = t?.nome_turma || 'Turma';
        } catch (_) {
        }
        setNomeTurma(nomeTurma);

        try {
            const r = await fetch(`${API}usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const textData = await r.text();
            let data;
            try { data = JSON.parse(textData || '{}'); } catch (_) { data = {}; }
            alunosTodos = data.competidores || data.usuarios || (Array.isArray(data) ? data : []);
            alunosMap = {};
            alunosTodos.forEach(a => { alunosMap[a.id_usuario] = a; });
            paginaAtual = 1;
            renderizarAlunos();
        } catch (e) {
            console.error(e);
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('tbodyAlunosTurmaDesk').innerHTML =
                `<tr><td colspan="4" class="text-danger text-center py-4">Erro ao carregar alunos.</td></tr>`;
        }
    }

    function aplicarFiltro() {
        paginaAtual = 1;
        renderizarAlunos();
    }

    function renderizarAlunos() {
        const termo = normalizar(
            (document.getElementById('buscaAlunoDesk')?.value || '') + ' ' +
            (document.getElementById('buscaAlunoMob')?.value || '')
        );
        const filtrados = termo
            ? alunosTodos.filter(a => normalizar((a.nome_usuario || '') + ' ' + (a.matricula_usuario || '')).includes(termo))
            : alunosTodos;

        const total = filtrados.length;
        const totalPaginas = Math.max(1, Math.ceil(total / POR_PAGINA));
        if (paginaAtual > totalPaginas) paginaAtual = totalPaginas;

        const ini = (paginaAtual - 1) * POR_PAGINA;
        const pagina = filtrados.slice(ini, ini + POR_PAGINA);

        const mob = document.getElementById('listaAlunosTurmaMob');
        const desk = document.getElementById('tbodyAlunosTurmaDesk');

        if (!total) {
            const vazio = termo
                ? '<i class="bi bi-search"></i><p><strong>Nenhum resultado para sua busca.</strong></p><p class="small text-muted">Tente buscar por nome ou RM.</p>'
                : '<i class="bi bi-people"></i><p><strong>Nenhum aluno cadastrado nesta turma.</strong></p><p class="small text-muted">Clique em "Adicionar Aluno" ou importe um PDF para começar.</p>';
            mob.innerHTML = `<div class="ta-empty">${vazio}</div>`;
            desk.innerHTML = `<tr><td colspan="4"><div class="ta-empty ta-empty--cell">${vazio}</div></td></tr>`;
        } else {
            const acoesMob = (u) => `
                <div class="d-flex gap-1">
                    <button type="button" class="ta-action ta-action--view" data-bs-toggle="tooltip" title="Visualizar" onclick="verAlunoId(${u.id_usuario})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${podeGerenciar ? `
                    <button type="button" class="ta-action ta-action--edit" data-bs-toggle="tooltip" title="Editar" onclick="abrirModalAlunoId(${u.id_usuario})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="ta-action ta-action--delete" data-bs-toggle="tooltip" title="Excluir" onclick="confirmarExcluir(${u.id_usuario})">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                </div>`;

            mob.innerHTML = pagina.map((u) => `
                <div class="ta-student-card${Number(u.inscrito || 0) === 0 ? ' sem-inscricao' : ''}">
                    <div class="ta-student-avatar">${esc((u.nome_usuario || 'A').charAt(0)).toUpperCase()}</div>
                    <div class="ta-student-info">
                        <div class="ta-student-name">${esc(u.nome_usuario)}${Number(u.inscrito || 0) === 0 ? '<span class="ta-badge-sem-inscricao">Sem inscrição</span>' : ''}</div>
                        <div class="ta-student-meta">${esc(u.matricula_usuario || '—')} · ${esc(generoLabel(u.genero_usuario))}</div>
                    </div>
                    ${acoesMob(u)}
                </div>`).join('');

            const acoesDesk = (u) => `
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="ta-action ta-action--view" data-bs-toggle="tooltip" title="Visualizar" onclick="verAlunoId(${u.id_usuario})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${podeGerenciar ? `
                    <button type="button" class="ta-action ta-action--edit" data-bs-toggle="tooltip" title="Editar" onclick="abrirModalAlunoId(${u.id_usuario})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="ta-action ta-action--delete" data-bs-toggle="tooltip" title="Excluir" onclick="confirmarExcluir(${u.id_usuario})">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                </div>`;

            desk.innerHTML = pagina.map((u) => `
                <tr class="${Number(u.inscrito || 0) === 0 ? 'ta-tr-sem-inscricao' : ''}">
                    <td class="ta-cell-nome">
                        <span class="ta-table-avatar">${esc((u.nome_usuario || 'A').charAt(0)).toUpperCase()}</span>${esc(u.nome_usuario)}${Number(u.inscrito || 0) === 0 ? '<span class="ta-badge-sem-inscricao">Sem inscrição</span>' : ''}
                    </td>
                    <td>${esc(u.matricula_usuario)}</td>
                    <td>
                        <span class="ta-badge-genero ${u.genero_usuario === 'FEM' ? 'ta-badge-genero--fem' : ''}">
                            <i class="bi ${u.genero_usuario === 'FEM' ? 'bi-gender-female' : 'bi-gender-male'}"></i>
                            ${esc(generoLabel(u.genero_usuario))}
                        </span>
                    </td>
                    <td class="text-center">${acoesDesk(u)}</td>
                </tr>`).join('');
        }

        const rotulo = total
            ? `Mostrando ${ini + 1}–${Math.min(ini + POR_PAGINA, total)} de ${total} aluno${total !== 1 ? 's' : ''}`
            : 'Nenhum aluno encontrado';
        document.getElementById('taInfoPaginaDesk').textContent = rotulo;
        document.getElementById('taInfoPaginaMob').textContent = rotulo;
        document.getElementById('contadorAlunosDesk').textContent = `${total} aluno${total !== 1 ? 's' : ''}`;
        document.getElementById('taTableCount').textContent = `${total} aluno${total !== 1 ? 's' : ''}`;

        construirPaginacao('paginacaoDesk', total, totalPaginas);
        construirPaginacao('paginacaoMob', total, totalPaginas);

        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                if (bootstrap.Tooltip.getInstance(el)) bootstrap.Tooltip.getInstance(el).dispose();
                new bootstrap.Tooltip(el, { placement: 'top' });
            });
        }
    }

    function construirPaginacao(containerId, total, totalPaginas) {
        const cont = document.getElementById(containerId);
        if (!cont) return;
        cont.innerHTML = '';
        if (totalPaginas <= 1) return;

        const nova = (label, pagina, desabilitado, ativo) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (ativo ? ' active' : '') + (desabilitado ? ' disabled' : '');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-link';
            btn.innerHTML = label;
            if (!desabilitado && !ativo) {
                btn.addEventListener('click', () => {
                    paginaAtual = pagina;
                    renderizarAlunos();
                });
            }
            li.appendChild(btn);
            cont.appendChild(li);
        };

        nova('&laquo;', 1, paginaAtual === 1, false);

        let inicio = Math.max(1, paginaAtual - 2);
        let fim = Math.min(totalPaginas, inicio + 4);
        inicio = Math.max(1, fim - 4);

        if (inicio > 1) {
            nova('1', 1, false, false);
            if (inicio > 2) nova('…', 1, true, false);
        }
        for (let p = inicio; p <= fim; p++) nova(String(p), p, false, p === paginaAtual);
        if (fim < totalPaginas) {
            if (fim < totalPaginas - 1) nova('…', totalPaginas, true, false);
            nova(String(totalPaginas), totalPaginas, false, false);
        }

        nova('&raquo;', totalPaginas, paginaAtual === totalPaginas, false);
    }

    function abrirModalAlunoId(id) {
        abrirModalAluno(alunosMap[id] || null);
    }

    function verAlunoId(id) {
        const aluno = alunosMap[id];
        if (!aluno) return;
        verAluno(aluno);
    }

    function verAluno(aluno) {
        document.getElementById('verInicial').textContent = (aluno.nome_usuario || 'A').charAt(0).toUpperCase();
        document.getElementById('verNome').textContent = aluno.nome_usuario || '—';
        document.getElementById('verRm').textContent = aluno.matricula_usuario || '—';
        const g = document.getElementById('verGenero');
        g.innerHTML = `<i class="bi ${aluno.genero_usuario === 'FEM' ? 'bi-gender-female' : 'bi-gender-male'}"></i> ${esc(generoLabel(aluno.genero_usuario))}`;
        document.getElementById('verDataNasc').textContent = formatarData(aluno.data_nasc_usuario);
        new bootstrap.Modal(document.getElementById('modalVerAluno')).show();
    }

    function abrirModalAluno(aluno) {
        const modal = new bootstrap.Modal(document.getElementById('modalAluno'));
        document.getElementById('alunoId').value = aluno ? aluno.id_usuario : '';
        document.getElementById('alunoNome').value = aluno ? aluno.nome_usuario : '';
        document.getElementById('alunoRm').value = aluno ? aluno.matricula_usuario : '';
        document.getElementById('alunoGenero').value = aluno ? (aluno.genero_usuario || 'MASC') : 'MASC';
        document.getElementById('alunoDataNasc').value = aluno && aluno.data_nasc_usuario ? aluno.data_nasc_usuario : '';
        document.getElementById('modalAlunoTitulo').textContent = aluno ? 'Editar aluno' : 'Adicionar aluno';
        document.getElementById('msgAluno').innerHTML = '';
        modal.show();
    }

    async function salvarAluno(e) {
        e.preventDefault();
        const id = document.getElementById('alunoId').value;
        const nome = document.getElementById('alunoNome').value.trim();
        const rm = document.getElementById('alunoRm').value.trim();
        const genero = document.getElementById('alunoGenero').value;
        const dataNasc = document.getElementById('alunoDataNasc').value;
        const msgEl = document.getElementById('msgAluno');
        const btn = document.getElementById('btnSalvarAluno');

        if (!nome || !rm || !dataNasc) {
            msgEl.innerHTML = '<span class="text-danger">Preencha todos os campos.</span>';
            return;
        }

        const fd = new FormData();
        fd.append('acao', id ? 'editar_aluno' : 'criar_aluno');
        if (id) fd.append('id_usuario', id);
        fd.append('nome_usuario', nome);
        fd.append('matricula_usuario', rm);
        fd.append('genero_usuario', genero);
        fd.append('data_nasc_usuario', dataNasc);
        fd.append('turmas_id_turma', idTurma);
        fd.append('interclasses_id_interclasse', idInterclasse);

        try {
            btn.disabled = true;
            const r = await fetch(`${API}usuarios.php`, { method: 'POST', body: fd, credentials: 'include' });
            const js = await r.json();
            if (js.status === 'sucesso') {
                bootstrap.Modal.getInstance(document.getElementById('modalAluno')).hide();
                carregarAlunos();
            } else {
                msgEl.innerHTML = `<span class="text-danger">${esc(js.mensagem || 'Erro ao salvar.')}</span>`;
            }
        } catch (err) {
            msgEl.innerHTML = `<span class="text-danger">Falha de conexão.</span>`;
        } finally {
            btn.disabled = false;
        }
    }

    let idAlunoExcluir = 0;
    function confirmarExcluir(id) {
        const aluno = alunosMap[id];
        idAlunoExcluir = id;
        document.getElementById('nomeAlunoExcluir').textContent = aluno ? aluno.nome_usuario : '';
        new bootstrap.Modal(document.getElementById('modalConfirmarExcluir')).show();
    }

    async function executarExcluir() {
        const btn = document.getElementById('btnConfirmarExcluir');
        try {
            btn.disabled = true;
            const fd = new FormData();
            fd.append('acao', 'excluir_aluno');
            fd.append('id_usuario', idAlunoExcluir);
            const r = await fetch(`${API}usuarios.php`, { method: 'POST', body: fd, credentials: 'include' });
            const js = await r.json();
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExcluir')).hide();
            if (js.status === 'sucesso') {
                carregarAlunos();
            } else {
                alert(js.mensagem || 'Erro ao excluir.');
            }
        } catch (_) {
            alert('Falha de conexão.');
        } finally {
            btn.disabled = false;
        }
    }

    /* ── Upload de PDF com progresso ── */
    function setBtnLoading(btn, carregando) {
        if (!btn) return;
        if (carregando) {
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importando…';
        } else {
            btn.disabled = false;
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        }
    }

    function progressoHelper(containerId, barId, textoId) {
        const cont = document.getElementById(containerId);
        const bar = document.getElementById(barId);
        const txt = document.getElementById(textoId);
        if (!cont || !bar || !txt) return null;
        return {
            start() { cont.style.display = 'block'; bar.style.width = '8%'; txt.textContent = 'Enviando arquivo…'; },
            progress(p) { bar.style.width = p + '%'; txt.textContent = `Enviando… ${p}%`; },
            done() { bar.style.width = '100%'; txt.textContent = 'Processando alunos…'; },
            reset() { bar.style.width = '0%'; txt.textContent = 'Enviando…'; }
        };
    }

    function enviarPdf(form, msgEl, btn, fallbackEl, cfg) {
        msgEl.innerHTML = '';
        if (fallbackEl) fallbackEl.classList.add('d-none');

        const fd = new FormData(form);
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('pdf_arquivo', fileInput.files[0]);
        }
        fd.append('id_interclasse', idInterclasse || '');
        fd.append('id_categoria', idCategoria || '');
        fd.append('id_turma', idTurma || '');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../api/upload_turma_pdf.php');
        xhr.withCredentials = true;

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && cfg) cfg.progress(Math.round((e.loaded / e.total) * 100));
        });

        xhr.onload = () => {
            setBtnLoading(btn, false);
            let js = {};
            try { js = JSON.parse(xhr.responseText); } catch (_) { js = {}; }
            if (xhr.status >= 200 && xhr.status < 300 && js.success !== false) {
                if (cfg) cfg.done();
                msgEl.innerHTML = '<span class="text-success">Importação concluída. Atualizando…</span>';
                setTimeout(() => window.location.reload(), 1200);
            } else {
                if (cfg) cfg.reset();
                msgEl.innerHTML = `<span class="text-danger">${esc(js.message || 'Falha no upload: ' + xhr.responseText)}</span>`;
                if (fallbackEl && js.fallback_converter) fallbackEl.classList.remove('d-none');
            }
        };

        xhr.onerror = () => {
            setBtnLoading(btn, false);
            if (cfg) cfg.reset();
            msgEl.innerHTML = '<span class="text-danger">Falha de conexão.</span>';
        };

        setBtnLoading(btn, true);
        if (cfg) cfg.start();
        xhr.send(fd);
    }

    /* ── Drag and Drop ── */
    function configurarDropzone(dropzoneId, inputId, nomeId) {
        const dropzone = document.getElementById(dropzoneId);
        const input = document.getElementById(inputId);
        const nome = document.getElementById(nomeId);
        if (!dropzone || !input || !nome) return;

        dropzone.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            if (input.files && input.files[0]) {
                nome.textContent = input.files[0].name;
                nome.classList.add('visivel');
                dropzone.classList.add('ta-dropzone--has-file');
            } else {
                nome.textContent = '';
                nome.classList.remove('visivel');
                dropzone.classList.remove('ta-dropzone--has-file');
            }
        });

        ['dragenter', 'dragover'].forEach(ev =>
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('ta-dropzone--dragover');
            })
        );
        ['dragleave', 'drop'].forEach(ev =>
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('ta-dropzone--dragover');
            })
        );
        dropzone.addEventListener('drop', (e) => {
            const arquivos = e.dataTransfer ? e.dataTransfer.files : null;
            if (arquivos && arquivos.length) {
                try {
                    const dt = new DataTransfer();
                    Array.from(arquivos).forEach(f => dt.items.add(f));
                    input.files = dt.files;
                } catch (_) {
                    input.value = '';
                }
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarAlunos();

        document.getElementById('formAluno').addEventListener('submit', salvarAluno);
        document.getElementById('btnConfirmarExcluir').addEventListener('click', executarExcluir);

        const buscaDesk = document.getElementById('buscaAlunoDesk');
        const buscaMob = document.getElementById('buscaAlunoMob');
        if (buscaDesk && buscaMob) {
            buscaDesk.addEventListener('input', () => { buscaMob.value = buscaDesk.value; aplicarFiltro(); });
            buscaMob.addEventListener('input', () => { buscaDesk.value = buscaMob.value; aplicarFiltro(); });
        }

        const colapsoMob = document.getElementById('blocoPdfMob');
        const colapsoDesk = document.getElementById('blocoPdfDesk');
        const botaoMob = document.getElementById('botaoPdfMob');
        const botaoDesk = document.getElementById('botaoPdfDesk');
        if (colapsoMob && botaoMob) {
            colapsoMob.addEventListener('show.bs.collapse', () => botaoMob.classList.add('aberto'));
            colapsoMob.addEventListener('hide.bs.collapse', () => botaoMob.classList.remove('aberto'));
        }
        if (colapsoDesk && botaoDesk) {
            colapsoDesk.addEventListener('show.bs.collapse', () => botaoDesk.classList.add('aberto'));
            colapsoDesk.addEventListener('hide.bs.collapse', () => botaoDesk.classList.remove('aberto'));
        }

        configurarDropzone('dropzoneMob', 'pdfInputMob', 'pdfNomeMob');
        configurarDropzone('dropzoneDesk', 'pdfInputDesk', 'pdfNomeDesk');

        const fMob = document.getElementById('formPdfTurmaMob');
        const fDesk = document.getElementById('formPdfTurmaDesk');
        const cfgMob = progressoHelper('progressMob', 'progressBarMob', 'progressTextoMob');
        const cfgDesk = progressoHelper('progressDesk', 'progressBarDesk', 'progressTextoDesk');

        if (fMob) {
            fMob.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fMob, document.getElementById('msgPdfMob'), fMob.querySelector('button[type="submit"]'), document.getElementById('fallbackMob'), cfgMob);
            });
        }
        if (fDesk) {
            fDesk.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fDesk, document.getElementById('msgPdfDesk'), fDesk.querySelector('button[type="submit"]'), document.getElementById('fallbackDesk'), cfgDesk);
            });
        }
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
