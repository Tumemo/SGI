<?php
$tituloPagina = 'SGI - Elenco';
include 'componentes/head.php';
include 'componentes/header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3 sgi-inline-d6522d52" >
    <a href="./edicao_equipes.php" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" id="btnVoltarElencoMob" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseElencoMob">Interclasse</span>
    </a>
    <div id="alertaLimiteMob" class="alert alert-danger d-none d-flex flex-wrap align-items-center gap-2 small"></div>
    <div id="listaElencoMob" class="d-flex flex-column gap-2"></div>
    <?php if ($isAdmin): ?>
    <a class="btn btn-aluno w-100 mt-4" id="linkGerenciarMob" href="#">
        <i class="bi bi-person-plus"></i>
    </a>
    <?php endif; ?>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <a href="./edicao_equipes.php" id="btnVoltarElencoDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseElencoDesk">Interclasse</span>
            </a>
            <h1>Elenco da equipe</h1>
            <?php if ($isAdmin): ?>
            <div class="ms-auto">
                <a class="btn btn-aluno" id="linkGerenciarDesk" href="#">
                    <i class="bi bi-person-plus"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="aluno-card">
            <div id="alertaLimiteDesk" class="alert alert-danger d-none d-flex flex-wrap align-items-center gap-2 small mx-3 mt-3 mb-0"></div>
            <div class="table-responsive">
                <table class="aluno-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>RM / Matrícula</th>
                            <?php if ($isAdmin): ?>
                            <th class="text-end">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbodyElencoDesk"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const API = '../../../api/';
const isAdmin = <?php echo json_encode($isAdmin); ?>;
const params = new URLSearchParams(window.location.search);
const idInterclasse = params.get('id');
const idEquipe = params.get('id_equipe');
const idTurma = params.get('id_turma');
const idCategoria = params.get('id_categoria');
const idModalidade = params.get('id_modalidade');
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
    if (idCategoria) q.set('id_categoria', idCategoria);
    const hrefEq = `./edicao_equipes.php?${q.toString()}`;
    ['btnVoltarElencoMob', 'btnVoltarElencoDesk'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.href = hrefEq;
    });
}

async function carregarNomeInterclasse() {
    if (!idInterclasse) return;
    try {
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const nome = dados?.nome_interclasse || 'Interclasse';
        ['nomeInterclasseElencoMob', 'nomeInterclasseElencoDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = nome;
        });
    } catch (e) {}
}

function montarGerenciar() {
    const q = new URLSearchParams();
    if (idInterclasse) q.set('id', idInterclasse);
    if (idTurma) q.set('id_turma', idTurma);
    if (idEquipe) q.set('id_equipe', idEquipe);
    if (idCategoria) q.set('id_categoria', idCategoria);
    if (idModalidade) q.set('id_modalidade', idModalidade);
    if (nomeTurma) q.set('nome_turma', nomeTurma);
    if (nomeModalidade) q.set('nome_modalidade', nomeModalidade);
    const href = `./equipe_alunos.php?${q.toString()}`;
    const a = document.getElementById('linkGerenciarMob');
    const b = document.getElementById('linkGerenciarDesk');
    if (a) a.href = href;
    if (b) b.href = href;
}

async function carregarAlertaLimite() {
    const mob = document.getElementById('alertaLimiteMob');
    const desk = document.getElementById('alertaLimiteDesk');
    const ocultar = () => {
        if (mob) { mob.innerHTML = ''; mob.classList.add('d-none'); }
        if (desk) { desk.innerHTML = ''; desk.classList.add('d-none'); }
    };

    if (!idEquipe || !idTurma || !idModalidade) {
        ocultar();
        return;
    }

    try {
        const r = await fetch(`${API}equipes.php?id_turma=${encodeURIComponent(idTurma)}&id_modalidade=${encodeURIComponent(idModalidade)}&_t=${Date.now()}`);
        const lista = await r.json();
        const arr = Array.isArray(lista) ? lista : [];
        const eq = arr.find(e => String(e.id_equipe) === String(idEquipe));
        const excedeu = eq && (eq.excedeu_limite === true || eq.excedeu_limite === '1' || eq.excedeu_limite === 1);

        if (!eq || !excedeu) {
            ocultar();
            return;
        }

        const total = Number(eq.total_alunos) || 0;
        const limite = Number(eq.limite_maximo) || 0;

        let msg = `<i class="bi bi-exclamation-triangle-fill"></i>`;
        msg += `<span class="flex-grow-1"><strong>Limite excedido:</strong> esta equipe possui <strong>${total}</strong> inscritos e o limite da modalidade é <strong>${limite}</strong>.</span>`;
        if (isAdmin) {
            msg += `<button type="button" class="btn btn-aluno btn-sm flex-shrink-0" onclick="redistribuirElenco()"><i class="bi bi-shuffle"></i> Enviar alunos para as outras equipes</button>`;
        }

        if (mob) { mob.innerHTML = msg; mob.classList.remove('d-none'); }
        if (desk) { desk.innerHTML = msg; desk.classList.remove('d-none'); }
    } catch (e) {
        ocultar();
    }
}

async function redistribuirElenco() {
    if (!confirm('Enviar os alunos excedentes para as outras equipes desta turma?')) return;
    try {
        const resp = await fetch(`${API}equipes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                acao: 'redistribuir',
                modalidades_id_modalidade: Number(idModalidade),
                turmas_id_turma: Number(idTurma)
            })
        });
        const data = await resp.json();
        if (data.success === false) throw new Error(data.message || 'Falha ao redistribuir.');
        alert(data.message || 'Redistribuição concluída.');
        carregar();
    } catch (err) {
        alert(err.message || 'Erro de conexão ao redistribuir.');
    }
}

async function carregar() {
    await carregarNomeInterclasse();
    montarVoltar();
    montarGerenciar();
    carregarAlertaLimite();
    const mob = document.getElementById('listaElencoMob');
    const tbody = document.getElementById('tbodyElencoDesk');

    if (!idEquipe) {
        mob.innerHTML = '<p class="text-muted">Parâmetro id_equipe ausente.</p>';
        tbody.innerHTML = '';
        return;
    }

    try {
        const r = await fetch(`${API}equipes.php?id_equipe=${encodeURIComponent(idEquipe)}&_t=${Date.now()}`);
        const lista = await r.json();
        const arr = Array.isArray(lista) ? lista : [];

        if (arr.length === 0) {
            const msg = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-people"></i></div><h5>Elenco vazio</h5><p>Nenhum jogador vinculado a esta equipe ainda.</p></div>';
            mob.innerHTML = msg;
            tbody.innerHTML = `<tr><td colspan="${isAdmin ? 3 : 2}" class="text-muted px-3 py-4">Nenhum jogador vinculado a esta equipe ainda.</td></tr>`;
            return;
        }

        mob.innerHTML = arr.map(u => `
            <div class="aluno-member-item">
                <div>
                    <div class="fw-medium">${esc(u.nome_usuario)}</div>
                    <div class="text-muted small">${esc(u.matricula_usuario)}</div>
                </div>
                ${isAdmin ? `
                    <button onclick="removerAluno(${u.id_usuario}, ${idEquipe})" class="btn btn-aluno btn-sm sgi-inline-d00c1ab7" >
                        <i class="bi bi-trash"></i>
                    </button>
                ` : ''}
            </div>
        `).join('');

        tbody.innerHTML = arr.map(u => `
            <tr>
                <td>${esc(u.nome_usuario)}</td>
                <td>${esc(u.matricula_usuario)}</td>
                ${isAdmin ? `
                    <td class="text-end">
                        <button onclick="removerAluno(${u.id_usuario}, ${idEquipe})" class="btn btn-aluno btn-sm sgi-inline-d00c1ab7" >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                ` : ''}
            </tr>
        `).join('');

    } catch (e) {
        console.error(e);
        mob.innerHTML = '<p class="text-danger">Erro ao carregar elenco.</p>';
        tbody.innerHTML = `<tr><td colspan="${isAdmin ? 3 : 2}" class="text-danger px-3">Erro ao carregar.</td></tr>`;
    }
}

async function removerAluno(idUsuario, idEquipe) {
    if (!confirm('Deseja realmente remover este aluno da equipe?')) return;

    try {
        const response = await fetch(`${API}equipes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                acao: 'remover_aluno',
                id_usuario: idUsuario,
                id_equipe: idEquipe
            })
        });
        const res = await response.json();
        if (res.success) {
            carregar();
        } else {
            alert(res.message || 'Erro ao remover aluno.');
        }
    } catch (e) {
        console.error('Erro de requisição:', e);
        alert('Erro de conexão ao tentar remover o aluno.');
    }
}

window.addEventListener('pageshow', carregar);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
