<?php
$tituloPagina = 'SGI - Colaborador - Turmas';
$titulo = 'Turmas';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <p class="text-center text-secondary mt-3" style="font-size: 14px;">Turmas do interclasse</p>
    <section id="listaTurmasMobile">
        <p class="text-center text-muted mt-4">(Carregando...)</p>
    </section>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-4">
            <i class="bi bi-people-fill fs-5"></i> Turmas
        </h4>
        <div class="row g-4" id="listaTurmasDesktop">
            <p class="text-muted">(Carregando turmas...)</p>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalhesTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold" id="modalDetalhesLabel">Detalhes da Turma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalhesBody"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let turmasData = [];

    async function carregarTurmas() {
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
            if (idInterclasse) {
                window.history.replaceState(null, '', `?id=${idInterclasse}`);
            }
        }
        if (!idInterclasse) {
            const msg = '<p class="text-center text-muted mt-4">Nenhum interclasse ativo.</p>';
            document.getElementById('listaTurmasMobile').innerHTML = msg;
            document.getElementById('listaTurmasDesktop').innerHTML = msg;
            return;
        }

        const listaMobile = document.getElementById('listaTurmasMobile');
        const listaDesktop = document.getElementById('listaTurmasDesktop');

        try {
            const res = await fetch(`../../../../api/turmas.php?id_interclasse=${idInterclasse}`);
            const data = await res.json();
            turmasData = Array.isArray(data) ? data : [];

            if (!turmasData.length) {
                const msg = '<p class="text-center text-muted mt-4">Nenhuma turma cadastrada.</p>';
                listaMobile.innerHTML = msg;
                listaDesktop.innerHTML = msg;
                return;
            }

            listaMobile.innerHTML = turmasData.map((turma) => `
                <div class="d-flex flex-column m-auto justify-content-between shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="m-0 fs-5">${turma.nome_turma}${turma.nome_fantasia_turma ? ' - ' + turma.nome_fantasia_turma : ''}</h2>
                            <small class="text-muted">${turma.turno_turma || 'Turno não definido'}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button type="button" class="btn btn-link text-primary p-0 text-decoration-none" onclick='verDetalhes(${turma.id_turma})' data-bs-toggle="modal" data-bs-target="#modalDetalhesTurma">
                            Ver detalhes
                        </button>
                        <a href="./turma_alunos.php?id_turma=${turma.id_turma}&id=${idInterclasse}" class="btn btn-danger btn-sm px-3">Ver alunos</a>
                    </div>
                </div>
            `).join('');

            listaDesktop.innerHTML = turmasData.map((turma) => `
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
                        <div class="card-body p-0 d-flex flex-column">
                            <h5 class="fw-bold text-dark mb-1">${turma.nome_turma}</h5>
                            ${turma.nome_fantasia_turma ? `<span class="text-muted small mb-1">${turma.nome_fantasia_turma}</span>` : ''}
                            <span class="text-muted small mb-3">${turma.turno_turma || 'Turno não definido'}</span>
                            <div class="mt-auto d-flex gap-2">
                                <button type="button" class="btn btn-outline-danger btn-sm flex-grow-1" onclick='verDetalhes(${turma.id_turma})' data-bs-toggle="modal" data-bs-target="#modalDetalhesTurma">
                                    <i class="bi bi-info-circle"></i> Detalhes
                                </button>
                                <a href="./turma_alunos.php?id_turma=${turma.id_turma}&id=${idInterclasse}" class="btn btn-danger btn-sm flex-grow-1">
                                    <i class="bi bi-people"></i> Ver alunos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Erro ao carregar turmas:', error);
            listaMobile.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
        }
    }

    window.verDetalhes = function(idTurma) {
        const turma = turmasData.find(t => t.id_turma == idTurma);
        if (!turma) return;

        document.getElementById('modalDetalhesLabel').textContent = turma.nome_turma;
        document.getElementById('modalDetalhesBody').innerHTML = `
            <div class="mb-3">
                <strong>Categoria:</strong> ${turma.nome_categoria || 'Não definida'}
            </div>
            <div class="mb-3">
                <strong>Turno:</strong> ${turma.turno_turma || 'Não definido'}
            </div>
            ${turma.nome_fantasia_turma ? `<div class="mb-3"><strong>Nome fantasia:</strong> ${turma.nome_fantasia_turma}</div>` : ''}
            <div class="mb-3">
                <strong>Quantidade de alunos:</strong> ${turma.quantidade_alunos || '0'}
            </div>
        `;
    };

    document.addEventListener('DOMContentLoaded', () => carregarTurmas().catch(console.error));
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
