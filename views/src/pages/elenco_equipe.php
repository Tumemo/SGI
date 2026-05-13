<?php
$titulo = "Elenco";
$textTop = "Elenco da equipe";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="d-md-none p-3" style="padding-top: 5.5rem; padding-bottom: 5rem;">
    <a href="./edicao_equipes.php" class="btn btn-danger btn-sm mb-3 rounded-3" id="btnVoltarElencoMob">Voltar</a>
    <h5 class="mb-2" style="font-weight: 400;" id="tituloElencoMob">Elenco</h5>
    <p class="text-muted small mb-3" id="subtituloElencoMob"></p>
    <div id="listaElencoMob" class="d-flex flex-column gap-2"></div>
    <a class="btn btn-outline-danger w-100 mt-4 rounded-3" id="linkGerenciarMob" href="#">Gerenciar inscrições na equipe</a>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 720px;">
        <a href="./edicao_equipes.php" class="btn btn-danger d-inline-flex align-items-center gap-2 mb-4 border-0 shadow-sm text-decoration-none rounded-3" id="btnVoltarElencoDesk">
            <i class="bi bi-arrow-left-circle"></i>
            <span style="font-weight: 400;">Voltar</span>
        </a>
        <h4 class="mb-1" style="font-weight: 400;" id="tituloElencoDesk">Elenco da equipe</h4>
        <p class="text-muted mb-4" id="subtituloElencoDesk"></p>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-weight: 400;">Nome</th>
                            <th style="font-weight: 400;">RM / Matrícula</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyElencoDesk"></tbody>
                </table>
            </div>
        </div>
        <a class="btn btn-outline-danger mt-4 rounded-3" id="linkGerenciarDesk" href="#">Gerenciar inscrições na equipe</a>
    </div>
</main>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');
    const idEquipe = params.get('id_equipe');
    const idTurma = params.get('id_turma');
    const idCategoria = params.get('id_categoria');
    const nomeTurma = params.get('nome_turma') || '';
    const nomeModalidade = params.get('nome_modalidade') || '';

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function montarVoltar() {
        const q = new URLSearchParams();
        if (idInterclasse) q.set('id', idInterclasse);
        const hrefEq = `./edicao_equipes.php?${q.toString()}`;
        const mob = document.getElementById('btnVoltarElencoMob');
        const desk = document.getElementById('btnVoltarElencoDesk');
        if (mob) mob.href = hrefEq;
        if (desk) desk.href = hrefEq;
    }

    function montarGerenciar() {
        const q = new URLSearchParams();
        if (idInterclasse) q.set('id', idInterclasse);
        if (idTurma) q.set('id_turma', idTurma);
        if (idEquipe) q.set('id_equipe', idEquipe);
        if (idCategoria) q.set('id_categoria', idCategoria);
        const href = `./equipe_alunos.php?${q.toString()}`;
        const a = document.getElementById('linkGerenciarMob');
        const b = document.getElementById('linkGerenciarDesk');
        if (a) a.href = href;
        if (b) b.href = href;
    }

    async function carregar() {
        montarVoltar();
        montarGerenciar();
        const tit = nomeModalidade ? `${nomeModalidade}` : 'Elenco da equipe';
        const sub = nomeTurma ? `Turma: ${nomeTurma}` : '';
        document.getElementById('tituloElencoMob').textContent = tit;
        document.getElementById('tituloElencoDesk').textContent = tit;
        document.getElementById('subtituloElencoMob').textContent = sub;
        document.getElementById('subtituloElencoDesk').textContent = sub;

        const mob = document.getElementById('listaElencoMob');
        const tbody = document.getElementById('tbodyElencoDesk');
        if (!idEquipe) {
            mob.innerHTML = '<p class="text-muted">Parâmetro id_equipe ausente.</p>';
            tbody.innerHTML = '';
            return;
        }
        try {
            const r = await fetch(`${API}equipes.php?id_equipe=${encodeURIComponent(idEquipe)}`);
            const lista = await r.json();
            const arr = Array.isArray(lista) ? lista : [];
            if (arr.length === 0) {
                mob.innerHTML = '<p class="text-muted">Nenhum jogador vinculado a esta equipe ainda.</p>';
                tbody.innerHTML = '<tr><td colspan="2" class="text-muted px-3 py-4">Nenhum jogador vinculado a esta equipe ainda.</td></tr>';
                return;
            }
            mob.innerHTML = arr
                .map(
                    (u) => `
                <div class="bg-white rounded-3 shadow-sm p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-medium">${esc(u.nome_usuario)}</div>
                        <div class="text-muted small">${esc(u.matricula_usuario)}</div>
                    </div>
                </div>`
                )
                .join('');
            tbody.innerHTML = arr
                .map(
                    (u) => `
                <tr>
                    <td class="px-3">${esc(u.nome_usuario)}</td>
                    <td class="px-3">${esc(u.matricula_usuario)}</td>
                </tr>`
                )
                .join('');
        } catch (e) {
            console.error(e);
            mob.innerHTML = '<p class="text-danger">Erro ao carregar elenco.</p>';
            tbody.innerHTML = '<tr><td colspan="2" class="text-danger px-3">Erro ao carregar.</td></tr>';
        }
    }

    document.addEventListener('DOMContentLoaded', carregar);
</script>

<?php
require_once '../componentes/footer.php';
