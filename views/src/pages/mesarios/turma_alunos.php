<?php
$tituloPagina = 'SGI - Mesário - Alunos';
$titulo = 'Alunos da turma';
$mostrarVoltar = true;
$urlVoltar = './turmas.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<!-- main mobile -->
<main class="d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="#" class="btn btn-danger btn-sm mb-3 rounded-3 d-inline-flex align-items-center gap-1" id="btnVoltarTurmaAlunosMob">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <h5 style="font-weight: 400;" id="tituloTurmaMob">Turma</h5>
    <p class="text-muted small" id="contagemMob"></p>
    <div id="listaAlunosTurmaMob" class="d-flex flex-column gap-2 mt-3">
        <p class="text-muted text-center">Carregando...</p>
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
    const API = '../../../../api/';
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
        const href = `./turmas.php?${q.toString()}`;
        const a = document.getElementById('btnVoltarTurmaAlunosMob');
        const b = document.getElementById('btnVoltarTurmaAlunosDesk');
        if (a) a.href = href;
        if (b) b.href = href;
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
            const r = await fetch(`${API}usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
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
                return;
            }

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

    document.addEventListener('DOMContentLoaded', carregarAlunos);
</script>

<?php
require_once '../../componentes/footer.php';
?>
