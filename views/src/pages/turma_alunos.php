<?php
$titulo = "Alunos da turma";
$textTop = "Alunos da turma";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="#" class="btn btn-danger btn-sm mb-3 rounded-3" id="btnVoltarTurmaAlunosMob">Voltar</a>
    <h5 style="font-weight: 400;" id="tituloTurmaMob">Turma</h5>
    <p class="text-muted small" id="contagemMob"></p>
    <div id="listaAlunosTurmaMob" class="d-flex flex-column gap-2 mt-3"></div>
    <div id="blocoPdfMob" class="card border-0 shadow-sm rounded-4 p-3 mt-4">
        <p class="small text-muted mb-2">Importar alunos via PDF</p>
        <form id="formPdfTurmaMob" enctype="multipart/form-data">
            <input type="hidden" name="nome_turma" id="hiddenNomeTurmaMob">
            <label class="form-label small">Arquivo PDF</label>
            <input type="file" class="form-control form-control-sm mb-2" name="pdf" accept="application/pdf" required>
            <button type="submit" class="btn btn-danger w-100 rounded-3">Importar PDF</button>
            <div id="msgPdfMob" class="small mt-2 text-center"></div>
        </form>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 800px;">
        <a href="#" class="btn btn-danger d-inline-flex align-items-center gap-2 mb-4 border-0 shadow-sm text-decoration-none rounded-3" id="btnVoltarTurmaAlunosDesk">
            <i class="bi bi-arrow-left-circle"></i>
            <span style="font-weight: 400;">Voltar</span>
        </a>
        <h4 style="font-weight: 400;" id="tituloTurmaDesk">Alunos da turma</h4>
        <p class="text-muted" id="contagemDesk"></p>

        <div id="blocoPdfDesk" class="card border-0 shadow-sm rounded-4 p-4">
            <h6 style="font-weight: 400;">Cadastrar alunos por PDF</h6>
            <p class="text-muted small">Use o mesmo nome da turma cadastrada para o arquivo bater com o esperado pelo sistema.</p>
            <form id="formPdfTurmaDesk" enctype="multipart/form-data" class="row g-2 align-items-end">
                <input type="hidden" name="nome_turma" id="hiddenNomeTurmaDesk">
                <div class="col-md-8">
                    <label class="form-label small">PDF (lista de alunos)</label>
                    <input type="file" class="form-control" name="pdf" accept="application/pdf" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100 rounded-3">Importar</button>
                </div>
                <div id="msgPdfDesk" class="col-12 small text-center"></div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 mt-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-weight: 400;">Nome</th>
                            <th style="font-weight: 400;">RM</th>
                            <th style="font-weight: 400;">Gênero</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlunosTurmaDesk"></tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = Number(params.get('id') || 0);
    const idCategoria = Number(params.get('id_categoria') || 0);
    const idTurma = Number(params.get('id_turma') || 0);

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

    function atualizarNomeTurmaPdf(nomeTurma) {
        document.getElementById('hiddenNomeTurmaMob').value = nomeTurma || '';
        document.getElementById('hiddenNomeTurmaDesk').value = nomeTurma || '';
    }

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
            try {
                turmas = JSON.parse(textTurmas || 'null');
            } catch (parseErr) {
                throw new Error('Resposta inválida de turmas: ' + textTurmas.slice(0, 200));
            }
            const t = Array.isArray(turmas) ? turmas[0] : null;
            nomeTurma = t?.nome_turma || 'Turma';
            document.getElementById('tituloTurmaMob').textContent = nomeTurma;
            document.getElementById('tituloTurmaDesk').textContent = nomeTurma;
        } catch (_) {
            document.getElementById('tituloTurmaMob').textContent = 'Turma';
            document.getElementById('tituloTurmaDesk').textContent = 'Turma';
        }

        try {
            const r = await fetch(`${API}usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurma)}`);
            const textData = await r.text();
            let data;
            try {
                data = JSON.parse(textData || '{}');
            } catch (parseErr) {
                throw new Error('Resposta inválida de usuários: ' + textData.slice(0, 200));
            }
            const arr = data.competidores || data.usuarios || (Array.isArray(data) ? data : []);
            const n = arr.length;
            document.getElementById('contagemMob').textContent = `${n} aluno(s) na turma`;
            document.getElementById('contagemDesk').textContent = `${n} aluno(s) na turma`;

            if (n === 0) {
                document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-muted small">Sem registros.</p>';
                document.getElementById('tbodyAlunosTurmaDesk').innerHTML =
                    '<tr><td colspan="3" class="text-muted px-3 py-4">Nenhum aluno cadastrado nesta turma.</td></tr>';
                atualizarNomeTurmaPdf(nomeTurma);
                return;
            }
            atualizarNomeTurmaPdf(nomeTurma);
            document.getElementById('listaAlunosTurmaMob').innerHTML = arr
                .map(
                    (u) => `
                <div class="bg-white rounded-3 shadow-sm p-3">
                    <div class="fw-medium">${esc(u.nome_usuario)}</div>
                    <div class="text-muted small">${esc(u.matricula_usuario)} · ${esc(u.genero_usuario || '')}</div>
                </div>`
                )
                .join('');
            document.getElementById('tbodyAlunosTurmaDesk').innerHTML = arr
                .map(
                    (u) => `
                <tr>
                    <td class="px-3">${esc(u.nome_usuario)}</td>
                    <td class="px-3">${esc(u.matricula_usuario)}</td>
                    <td class="px-3">${esc(u.genero_usuario || '')}</td>
                </tr>`
                )
                .join('');
        } catch (e) {
            console.error(e);
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
        }
    }

    async function enviarPdf(form, msgEl, btn, idManual = null) {
        msgEl.innerHTML = '';
        // Construir FormData explicitamente para garantir nomes esperados pelo backend
        const fd = new FormData();
        // Anexa campos existentes do form (texto, hidden, etc.)
        for (const [k, v] of new FormData(form).entries()) {
            fd.append(k, v);
        }
        // Garante que o arquivo seja enviado com o nome esperado pelo servidor
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('pdf_arquivo', fileInput.files[0]);
        }
        // Campos importantes enviados pelo frontend
        fd.append('id_interclasse', idInterclasse || '');
        fd.append('id_categoria', idCategoria || '');
        fd.append('id_turma', idManual !== null ? String(idManual) : (idTurma || ''));
        try {
            if (btn) {
                btn.disabled = true;
            }
            const r = await fetch('../../../api/upload_turma_pdf.php', {
                method: 'POST',
                body: fd,
                credentials: 'include'
            });
            const text = await r.text();
            let js = {};
            try {
                js = JSON.parse(text);
            } catch (_) {
                js = {};
            }
            if (!r.ok || js.success === false) throw new Error(js.message || 'Falha no upload: ' + text);
            msgEl.innerHTML = '<span class="text-success">Importação concluída. Atualizando…</span>';
            setTimeout(() => window.location.reload(), 1200);
        } catch (err) {
            msgEl.innerHTML = `<span class="text-danger">${esc(err.message || String(err))}</span>`;
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarAlunos();
        const fMob = document.getElementById('formPdfTurmaMob');
        const fDesk = document.getElementById('formPdfTurmaDesk');
        if (fMob) {
            fMob.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fMob, document.getElementById('msgPdfMob'), fMob.querySelector('button[type="submit"]'));
            });
        }
        if (fDesk) {
            fDesk.addEventListener('submit', (e) => {
                e.preventDefault();
                enviarPdf(fDesk, document.getElementById('msgPdfDesk'), fDesk.querySelector('button[type="submit"]'));
            });
        }
    });
</script>

<?php
require_once '../componentes/footer.php';
