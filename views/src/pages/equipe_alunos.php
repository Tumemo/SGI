<?php
$tituloPagina = 'SGI - Adicionar Alunos';
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
.aluno-search { position: relative; }
.aluno-search .form-control {
  border-radius: var(--aluno-radius-lg);
  border: 2px solid var(--aluno-border);
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  font-size: 0.95rem;
  transition: border-color var(--aluno-transition), box-shadow var(--aluno-transition);
}
.aluno-search .form-control:focus { border-color: var(--aluno-primary); box-shadow: 0 0 0 3px rgba(220,53,69,0.15); outline: none; }
.aluno-search .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--aluno-text-muted); pointer-events: none; }
.aluno-card-item {
  background: var(--aluno-surface); border-radius: var(--aluno-radius-md);
  border: 1px solid var(--aluno-border); padding: 0.75rem 1rem;
  transition: all var(--aluno-transition);
  display: flex; align-items: center; justify-content: space-between;
}
.aluno-card-item:hover { border-color: var(--aluno-primary); box-shadow: var(--aluno-shadow-sm); }
.aluno-card-item .form-check-input { cursor: pointer; width: 1.2rem; height: 1.2rem; }
.aluno-card-item .form-check-input:checked { background-color: var(--aluno-primary); border-color: var(--aluno-primary); }
.aluno-empty { text-align: center; padding: 3rem 1rem; color: var(--aluno-text-secondary); }
.aluno-empty .empty-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--aluno-text-muted); }
.aluno-empty h5 { font-weight: 600; margin-bottom: 0.5rem; }
.aluno-empty p { font-size: 0.9rem; max-width: 400px; margin: 0 auto; }
.btn-aluno { background: var(--aluno-primary); color: #fff; border: none; border-radius: var(--aluno-radius-md); padding: 0.5rem 1.5rem; font-weight: 500; transition: all var(--aluno-transition); text-decoration: none; }
.btn-aluno:hover { background: var(--aluno-primary-dark); color: #fff; }
';

include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <a href="#" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" id="btnVoltarEquipesMobile" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosMob">Interclasse</span>
        </a>
        <div id="listaAlunosMobile" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem">
            <p class="text-muted text-center">(Carregando alunos...)</p>
        </div>
        <button id="btnSalvarAlunosMobile" class="btn btn-aluno w-100 mt-3"><i class="bi bi-check-lg"></i></button>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="aluno-page container-fluid py-4 px-4">
        <div class="aluno-page-header">
            <a href="#" id="btnVoltarEquipesDesktop" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseEquipeAlunosDesk">Interclasse</span>
            </a>
            <h1>Adicionar alunos à equipe</h1>
            <div class="ms-auto d-flex gap-2">
                <button id="btnSalvarAlunosDesktop" class="btn btn-aluno"><i class="bi bi-check-lg"></i></button>
            </div>
        </div>

        <div id="listaAlunosDesktop" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem">
            <div class="aluno-loading text-center py-4 text-muted">Carregando alunos...</div>
        </div>
    </div>
</main>

<div id="toastMensagem" class="position-fixed top-0 start-50 translate-middle-x z-3 p-3" style="display:none; margin-top: 10px;">
    <div class="d-flex align-items-center gap-2 px-4 py-3 rounded-3 shadow-lg" id="toastConteudo" style="min-width: 280px; background: white; border-left: 5px solid #198754;">
        <i class="bi fs-4" id="toastIcone"></i>
        <span class="fw-semibold" id="toastTexto"></span>
    </div>
</div>

<script>
let alunos = [];
let alunosNaEquipe = [];
let generoDaModalidade = 'MISTO';
let _idEquipe = null;

function mostrarToast(tipo, texto) {
    const container = document.getElementById('toastMensagem');
    const conteudo = document.getElementById('toastConteudo');
    const icone = document.getElementById('toastIcone');
    const txt = document.getElementById('toastTexto');
    const cor = tipo === 'sucesso' ? '#198754' : '#dc3545';
    const iconeNome = tipo === 'sucesso' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
    conteudo.style.borderLeftColor = cor;
    icone.className = `bi ${iconeNome} fs-4`;
    txt.textContent = texto;
    container.style.display = 'block';
    clearTimeout(container._timer);
    container._timer = setTimeout(() => { container.style.display = 'none'; }, 4000);
}

function cardAluno(aluno) {
    const estaNaEquipe = alunosNaEquipe.some(a => a.id_usuario === aluno.id_usuario);
    return `
        <label class="aluno-card-item">
            <div>
                <strong>${esc(aluno.nome_usuario)}</strong>
                <div class="text-muted small">${esc(aluno.matricula_usuario)} (${aluno.genero_usuario || 'Não informado'})</div>
            </div>
            <input class="form-check-input aluno-check" type="checkbox" value="${aluno.id_usuario}" ${estaNaEquipe ? 'checked' : ''}>
        </label>
    `;
}

function renderizar(lista) {
    const mobile = document.getElementById('listaAlunosMobile');
    const desktop = document.getElementById('listaAlunosDesktop');

    if (!lista.length) {
        const msg = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-people"></i></div><h5>Nenhum aluno disponível</h5><p>Nenhum aluno foi encontrado para esta turma com o gênero compatível com a modalidade.</p></div>';
        mobile.innerHTML = msg;
        desktop.innerHTML = msg;
        return;
    }

    const html = lista.map(cardAluno).join('');
    mobile.innerHTML = html;
    desktop.innerHTML = html;
}

function filtrar(termo) {
    const t = termo.trim().toLowerCase();
    const filtrados = alunos.filter(aluno =>
        String(aluno.nome_usuario || '').toLowerCase().includes(t) ||
        String(aluno.matricula_usuario || '').toLowerCase().includes(t)
    );
    renderizar(filtrados);
}

async function carregar() {
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');

    if (idInterclasse) {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then(dados => {
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseEquipeAlunosMob', 'nomeInterclasseEquipeAlunosDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = nome;
            });
        }).catch(() => {});
    }
    const idTurma = params.get('id_turma');
    _idEquipe = params.get('id_equipe');
    const idCategoria = params.get('id_categoria');
    const idModalidade = params.get('id_modalidade');
    const nomeTurma = params.get('nome_turma') || '';
    const nomeModalidade = params.get('nome_modalidade') || '';

    const qVoltar = new URLSearchParams();
    if (idInterclasse) qVoltar.set('id', idInterclasse);
    if (idTurma) qVoltar.set('id_turma', idTurma);
    if (_idEquipe) qVoltar.set('id_equipe', _idEquipe);
    if (idCategoria) qVoltar.set('id_categoria', idCategoria);
    if (nomeTurma) qVoltar.set('nome_turma', nomeTurma);
    if (nomeModalidade) qVoltar.set('nome_modalidade', nomeModalidade);
    const voltar = `./elenco_equipe.php?${qVoltar.toString()}`;
    document.getElementById('btnVoltarEquipesDesktop').href = voltar;
    const vm = document.getElementById('btnVoltarEquipesMobile');
    if (vm) vm.href = voltar;

    try {
        const ts = Date.now();

        if (idModalidade) {
            try {
                const resMod = await fetch(`../../../api/modalidades.php?id_modalidade=${idModalidade}&_t=${ts}`);
                const dadosMod = await resMod.json();
                if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                    generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                } else if (dadosMod && dadosMod.genero_modalidade) {
                    generoDaModalidade = dadosMod.genero_modalidade;
                } else {
                    generoDaModalidade = 'MISTO';
                }
            } catch (e) {
                console.error("Erro ao obter gênero da modalidade:", e);
                generoDaModalidade = 'MISTO';
            }
        } else if (idCategoria) {
            try {
                const resMod = await fetch(`../../../api/modalidades.php?id_categoria=${idCategoria}&_t=${ts}`);
                const dadosMod = await resMod.json();
                if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                    generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                }
            } catch (e) {
                generoDaModalidade = 'MISTO';
            }
        }

        const resEquipe = await fetch(`../../../api/equipes.php?id_equipe=${_idEquipe}&_t=${ts}`);
        const rawEq = await resEquipe.json();
        alunosNaEquipe = Array.isArray(rawEq) ? rawEq : [];

        const generoParam = (generoDaModalidade === 'MISTO' || generoDaModalidade === 'MISTA') ? '' : `&genero=${generoDaModalidade}`;
        const res = await fetch(`../../../api/usuarios.php?acao=listar_competidores&id_turma=${idTurma}${generoParam}&_t=${ts}`);
        const data = await res.json();
        alunos = (data && data.competidores) ? data.competidores : (Array.isArray(data) ? data : []);

        renderizar(alunos);
    } catch (error) {
        console.error("Erro ao carregar dados:", error);
        document.getElementById('listaAlunosMobile').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        document.getElementById('listaAlunosDesktop').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
    }
}

async function salvar() {
    const checks = Array.from(document.querySelectorAll('.aluno-check:checked'));
    const ids = checks.map(item => Number(item.value)).filter(Boolean);

    if (!ids.length) {
        mostrarToast('erro', 'Selecione pelo menos um aluno.');
        return;
    }

    const botoes = [
        document.getElementById('btnSalvarAlunosDesktop'),
        document.getElementById('btnSalvarAlunosMobile')
    ];

    botoes.forEach(b => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; });

    try {
        const response = await fetch('../../../api/equipes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                acao: 'adicionar_usuarios',
                id_equipe: Number(_idEquipe),
                usuarios: ids
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao salvar.');
        mostrarToast('sucesso', 'Alterações salvas com sucesso.');
    } catch (error) {
        mostrarToast('erro', error.message);
        return;
    } finally {
        botoes.forEach(b => { b.disabled = false; b.innerHTML = '<i class=\"bi bi-check-lg\"></i>'; });
    }

    await carregar();
}

document.getElementById('btnSalvarAlunosDesktop').addEventListener('click', salvar);
document.getElementById('btnSalvarAlunosMobile').addEventListener('click', salvar);

window.addEventListener('pageshow', carregar);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
