<?php
$tituloPagina = 'SGI - Elenco';
$cssExtra = '
:root {
  --aluno-primary: #dc3545;
  --aluno-primary-dark: #b02a37;
  --aluno-primary-light: #fce4e6;
  --aluno-primary-subtle: #fff0f0;
  --aluno-success: #198754;
  --aluno-bg: #f5f6fa;
  --aluno-surface: #ffffff;
  --aluno-border: #e9ecef;
  --aluno-text: #1a1a2e;
  --aluno-text-secondary: #6c757d;
  --aluno-text-muted: #adb5bd;
  --aluno-radius-sm: 8px;
  --aluno-radius-md: 12px;
  --aluno-radius-lg: 16px;
  --aluno-radius-xl: 24px;
  --aluno-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --aluno-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --aluno-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
  --aluno-shadow-hover: 0 12px 28px rgba(0,0,0,0.15);
  --aluno-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.aluno-page { font-family: "Segoe UI", system-ui, -apple-system, sans-serif; color: var(--aluno-text); }
.aluno-page-header {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;
  padding-bottom: 1rem; border-bottom: 2px solid var(--aluno-border);
}
.aluno-page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--aluno-text); }
.aluno-page-header .back-link {
  width: 40px; height: 40px; border-radius: var(--aluno-radius-md);
  display: flex; align-items: center; justify-content: center;
  background: var(--aluno-surface); border: 1px solid var(--aluno-border);
  color: var(--aluno-text); text-decoration: none;
  transition: all var(--aluno-transition); flex-shrink: 0;
}
.aluno-page-header .back-link:hover { background: var(--aluno-primary); color: #fff; border-color: var(--aluno-primary); }
.aluno-card {
  background: var(--aluno-surface); border-radius: var(--aluno-radius-lg);
  border: 1px solid var(--aluno-border); overflow: hidden;
  box-shadow: var(--aluno-shadow-sm);
}
.aluno-table { width: 100%; border-collapse: collapse; }
.aluno-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 500; color: var(--aluno-text-secondary); font-size: 0.85rem; border-bottom: 2px solid var(--aluno-border); }
.aluno-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--aluno-border); }
.aluno-table tr:last-child td { border-bottom: none; }
.aluno-table tbody tr { transition: background var(--aluno-transition); }
.aluno-table tbody tr:hover { background: #f8f9fa; }
.aluno-member-item {
  background: var(--aluno-surface); border-radius: var(--aluno-radius-md);
  border: 1px solid var(--aluno-border); padding: 0.75rem 1rem;
  display: flex; align-items: center; justify-content: space-between;
}
.btn-aluno { background: var(--aluno-primary); color: #fff; border: none; border-radius: var(--aluno-radius-md); padding: 0.5rem 1.25rem; font-weight: 500; transition: all var(--aluno-transition); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-aluno:hover { background: var(--aluno-primary-dark); color: #fff; }
.btn-aluno { background: transparent; color: var(--aluno-primary); border: 1.5px solid var(--aluno-primary); border-radius: var(--aluno-radius-md); padding: 0.5rem 1.25rem; font-weight: 500; transition: all var(--aluno-transition); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-aluno:hover { background: var(--aluno-primary); color: #fff; }
.aluno-empty { text-align: center; padding: 3rem 1rem; color: var(--aluno-text-secondary); }
.aluno-empty .empty-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--aluno-text-muted); }
.aluno-empty h5 { font-weight: 600; margin-bottom: 0.5rem; }
.aluno-empty p { font-size: 0.9rem; max-width: 400px; margin: 0 auto; }
.aluno-card-table { max-width: 100%; }
';

include 'componentes/head.php';
include 'componentes/header.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isAdmin = $nivelUsuario === 0;
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none p-3" style="padding-bottom: 5rem;">
    <a href="./edicao_equipes.php" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" id="btnVoltarElencoMob" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseElencoMob">Interclasse</span>
    </a>
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
            <a href="./edicao_equipes.php" id="btnVoltarElencoDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
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

async function carregar() {
    await carregarNomeInterclasse();
    montarVoltar();
    montarGerenciar();
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
                    <button onclick="removerAluno(${u.id_usuario}, ${idEquipe})" class="btn btn-aluno btn-sm" style="padding:0.3rem 0.75rem;font-size:0.8rem">
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
                        <button onclick="removerAluno(${u.id_usuario}, ${idEquipe})" class="btn btn-aluno btn-sm" style="padding:0.3rem 0.75rem;font-size:0.8rem">
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
