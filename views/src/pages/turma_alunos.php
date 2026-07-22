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

<!-- main mobile -->
<main class="d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="#" class="btn btn-danger btn-sm mb-3 rounded-3" id="btnVoltarTurmaAlunosMob">Voltar</a>

    <?php if ($podeGerenciar): ?>
    <button class="btn btn-outline-danger btn-sm rounded-3 mb-3" onclick="abrirModalAluno()">
        <i class="bi bi-plus-lg"></i> Adicionar aluno
    </button>
    <?php endif; ?>

    <div id="listaAlunosTurmaMob" class="d-flex flex-column gap-2 mt-3"></div>
    <?php if ($nivelUsuario === 0): ?>
    <div id="blocoPdfMob" class="card border-0 shadow-sm rounded-4 p-3 mt-4">
        <p class="small text-muted mb-2">Importar alunos via PDF</p>
        <form id="formPdfTurmaMob" enctype="multipart/form-data">
            <label class="form-label small">Arquivo PDF (com texto selecionável)</label>
            <input type="file" class="form-control form-control-sm mb-2" name="pdf" accept="application/pdf" required>
            <button type="submit" class="btn btn-danger w-100 rounded-3">Importar PDF</button>
            <div id="msgPdfMob" class="small mt-2 text-center"></div>
            <div id="fallbackMob" class="d-none mt-3 text-center">
                <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                <a href="https://www.ilovepdf.com/pt/ocr-pdf" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                    <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0">
        <a href="#" id="btnVoltarTurmaAlunosDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <div class="d-flex align-items-center gap-3 mb-2">
            <?php if ($podeGerenciar): ?>
            <button class="btn btn-outline-danger btn-sm rounded-3" onclick="abrirModalAluno()">
                <i class="bi bi-plus-lg"></i> Adicionar aluno
            </button>
            <?php endif; ?>
        </div>

        <?php if ($nivelUsuario === 0): ?>
        <div id="blocoPdfDesk" class="card border-0 shadow-sm rounded-4 p-4">
            <h6 style="font-weight: 400;">Cadastrar alunos por PDF</h6>
            <p class="text-muted small">O PDF deve conter texto selecionável (não imagem). Os alunos serão vinculados automaticamente a esta turma.</p>
            <form id="formPdfTurmaDesk" enctype="multipart/form-data" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small">PDF (lista de alunos)</label>
                    <input type="file" class="form-control" name="pdf" accept="application/pdf" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100 rounded-3">Importar</button>
                </div>
                <div id="msgPdfDesk" class="col-12 small text-center"></div>
                <div id="fallbackDesk" class="col-12 d-none text-center mt-2">
                    <p class="small text-muted mb-2">O PDF parece ser uma imagem. Converta para PDF selecionável:</p>
                    <a href="https://www.ilovepdf.com/pt/download/l3w270hy6bhh3vg86m6h107rv3wA1dkt377wsjbsj9v9k7tgz76cyxfw89fkymvxw7wqy3vy4bwmntykf5kd8g6s2pg6xkl7v1qp0js1A3wdk9wrdvvndvvs3xggvsm2l9rq8bpx6l7qh68c69x8rvvpd3tpdbhd4zj29qhs8nm02zvc7bfq/95o" target="_blank" class="btn btn-outline-danger btn-sm rounded-3">
                        <i class="bi bi-box-arrow-up-right"></i> Converter PDF (iLovePDF)
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 mb-4 mt-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-weight: 400;">Nome</th>
                            <th style="font-weight: 400;">RM</th>
                            <th style="font-weight: 400;">Gênero</th>
                            <?php if ($podeGerenciar): ?>
                            <th style="font-weight: 400; width: 100px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlunosTurmaDesk"></tbody>
                </table>
            </div>
        </div>

    </div>
</main>

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

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function setVoltar() {
        const q = new URLSearchParams();
        if (idInterclasse) q.set('id', idInterclasse);
        if (idCategoria) q.set('id_categoria', idCategoria);
        const pagina = idCategoria ? 'turmas' : 'edicao_turmas';
        const href = `./${pagina}.php?${q.toString()}`;
        const a = document.getElementById('btnVoltarTurmaAlunosMob');
        const b = document.getElementById('btnVoltarTurmaAlunosDesk');
        if (a) a.href = href;
        if (b) b.href = href;
    }

    function atualizarNomeTurmaPdf() {}

    async function carregarAlunos() {
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

        try {
            const r = await fetch(`${API}usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const textData = await r.text();
            let data;
            try { data = JSON.parse(textData || '{}'); } catch (_) { data = {}; }
            const arr = data.competidores || data.usuarios || (Array.isArray(data) ? data : []);
            const n = arr.length;

            if (n === 0) {
                document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-muted small">Sem registros.</p>';
                document.getElementById('tbodyAlunosTurmaDesk').innerHTML =
                    `<tr><td colspan="${podeGerenciar ? 4 : 3}" class="text-muted px-3 py-4">Nenhum aluno cadastrado nesta turma.</td></tr>`;
                return;
            }

            const acoesMob = (u) => podeGerenciar ? `
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-secondary btn-sm rounded-3 flex-fill" onclick='abrirModalAluno(${JSON.stringify(u)})'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-3 flex-fill" onclick="confirmarExcluir(${u.id_usuario}, '${esc(u.nome_usuario).replace(/'/g, "\\'")}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>` : '';

            document.getElementById('listaAlunosTurmaMob').innerHTML = arr
                .map((u) => `
                <div class="bg-white rounded-3 shadow-sm p-3">
                    <div class="fw-medium">${esc(u.nome_usuario)}</div>
                    <div class="text-muted small">${esc(u.matricula_usuario)} · ${esc(u.genero_usuario || '')}</div>
                    ${acoesMob(u)}
                </div>`).join('');

            const acoesDesk = (u) => podeGerenciar ? `
                <td class="px-3">
                    <button class="btn btn-outline-secondary btn-sm rounded-3 me-1" onclick='abrirModalAluno(${JSON.stringify(u)})' title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-3" onclick="confirmarExcluir(${u.id_usuario}, '${esc(u.nome_usuario).replace(/'/g, "\\'")}')" title="Excluir">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>` : '';

            document.getElementById('tbodyAlunosTurmaDesk').innerHTML = arr
                .map((u) => `
                <tr>
                    <td class="px-3">${esc(u.nome_usuario)}</td>
                    <td class="px-3">${esc(u.matricula_usuario)}</td>
                    <td class="px-3">${esc(u.genero_usuario || '')}</td>
                    ${acoesDesk(u)}
                </tr>`).join('');
        } catch (e) {
            console.error(e);
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
        }
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
    function confirmarExcluir(id, nome) {
        idAlunoExcluir = id;
        document.getElementById('nomeAlunoExcluir').textContent = nome;
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

    async function enviarPdf(form, msgEl, btn, fallbackEl) {
        msgEl.innerHTML = '';
        if (fallbackEl) fallbackEl.classList.add('d-none');
        const fd = new FormData();
        for (const [k, v] of new FormData(form).entries()) {
            fd.append(k, v);
        }
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('pdf_arquivo', fileInput.files[0]);
        }
        fd.append('id_interclasse', idInterclasse || '');
        fd.append('id_categoria', idCategoria || '');
        fd.append('id_turma', idTurma || '');
        let js = {};
        try {
            if (btn) btn.disabled = true;
            const r = await fetch('../../../api/upload_turma_pdf.php', {
                method: 'POST',
                body: fd,
                credentials: 'include'
            });
            const text = await r.text();
            try { js = JSON.parse(text); } catch (_) { js = {}; }
            if (!r.ok || js.success === false) {
                throw new Error(js.message || 'Falha no upload: ' + text);
            }
            msgEl.innerHTML = '<span class="text-success">Importação concluída. Atualizando…</span>';
            setTimeout(() => window.location.reload(), 1200);
        } catch (err) {
            msgEl.innerHTML = `<span class="text-danger">${esc(err.message || String(err))}</span>`;
            if (fallbackEl && js.fallback_converter) {
                fallbackEl.classList.remove('d-none');
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarAlunos();

        document.getElementById('formAluno').addEventListener('submit', salvarAluno);
        document.getElementById('btnConfirmarExcluir').addEventListener('click', executarExcluir);

        const fMob = document.getElementById('formPdfTurmaMob');
        const fDesk = document.getElementById('formPdfTurmaDesk');
        if (fMob) {
            fMob.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fMob, document.getElementById('msgPdfMob'), fMob.querySelector('button[type="submit"]'), document.getElementById('fallbackMob'));
            });
        }
        if (fDesk) {
            fDesk.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fDesk, document.getElementById('msgPdfDesk'), fDesk.querySelector('button[type="submit"]'), document.getElementById('fallbackDesk'));
            });
        }
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
