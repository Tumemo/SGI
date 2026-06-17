<?php
$tituloPagina = 'SGI - Colaborador - Alunos';
$titulo = 'Alunos da Turma';
$mostrarVoltar = true;
$urlVoltar = './turmas.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'turmas';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center text-secondary mt-3" style="font-size: 14px;" id="tituloTurmaMob">Turma</p>
    <p class="text-muted small text-center" id="contagemMob"></p>
    <div id="listaAlunosMob" class="d-flex flex-column gap-2 px-3 mt-3"></div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 800px;">
        <a href="./turmas.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <h4 style="font-weight: 400;" id="tituloTurmaDesk">Alunos da Turma</h4>
        <p class="text-muted" id="contagemDesk"></p>

        <div class="card border-0 shadow-sm rounded-4 mb-4 mt-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-weight: 400;">Nome</th>
                            <th style="font-weight: 400;">RA</th>
                            <th style="font-weight: 400;">Gênero</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlunosDesk"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    const API = '../../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idTurma = Number(params.get('id_turma') || 0);

    async function carregarDados() {
        if (!idTurma || isNaN(idTurma)) {
            document.getElementById('listaAlunosMob').innerHTML = '<p class="text-muted text-center">Parâmetros inválidos.</p>';
            return;
        }

        let nomeTurma = 'Turma';
        try {
            const rT = await fetch(`${API}turmas.php?id_turma=${encodeURIComponent(idTurma)}`);
            const textTurmas = await rT.text();
            let turmas = null;
            try {
                turmas = JSON.parse(textTurmas || 'null');
            } catch (_) {
                throw new Error('Resposta inválida');
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
            } catch (_) {
                throw new Error('Resposta inválida');
            }
            const arr = data.competidores || data.usuarios || (Array.isArray(data) ? data : []);
            const n = arr.length;

            document.getElementById('contagemMob').textContent = `${n} aluno(s) na turma`;
            document.getElementById('contagemDesk').textContent = `${n} aluno(s) na turma`;

            if (n === 0) {
                document.getElementById('listaAlunosMob').innerHTML = '<p class="text-muted text-center">Nenhum aluno encontrado nesta turma.</p>';
                document.getElementById('tbodyAlunosDesk').innerHTML =
                    '<tr><td colspan="3" class="text-muted px-3 py-4 text-center">Nenhum aluno encontrado nesta turma.</td></tr>';
                return;
            }

            document.getElementById('listaAlunosMob').innerHTML = arr
                .map(u => `
                    <div class="bg-white rounded-3 shadow-sm p-3">
                        <div class="fw-medium">${esc(u.nome_usuario)}</div>
                        <div class="text-muted small">${esc(u.matricula_usuario)} · ${esc(u.genero_usuario || '')}</div>
                    </div>
                `)
                .join('');

            document.getElementById('tbodyAlunosDesk').innerHTML = arr
                .map(u => `
                    <tr>
                        <td class="px-3">${esc(u.nome_usuario)}</td>
                        <td class="px-3">${esc(u.matricula_usuario)}</td>
                        <td class="px-3">${esc(u.genero_usuario || '')}</td>
                    </tr>
                `)
                .join('');
        } catch (e) {
            console.error(e);
            document.getElementById('listaAlunosMob').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        }
    }

    document.addEventListener('DOMContentLoaded', () => carregarDados().catch(console.error));
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
