<?php
$titulo = "Modalidades / Alunos";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Borda dourada suave para o aluno destaque */
    .borda-destaque {
        border: 2px solid #D4AF37 !important;
        transition: border 0.3s ease-in-out;
    }

    /* Cor para a estrela preenchida */
    .estrela-dourada {
        color: #D4AF37 !important;
    }
</style>

<main class="d-md-none" style="margin-bottom:120px;">
    <div class="container mt-3">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button id="filtroCat1Mobile" class="btn btn-sm btn-light border">Categoria I</button>
            <button id="filtroCat2Mobile" class="btn btn-sm btn-light border">Categoria II</button>
            <button id="filtroDestaqueMobile" class="btn btn-sm btn-light border">Aluno destaque</button>
        </div>
        <div id="listaAlunosMobile"></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="mx-auto" style="max-width: 720px;">
        <div class="d-flex gap-2 mb-4 overflow-auto pb-1" style="white-space: nowrap; scrollbar-width: none;">
            <button id="filtroCat1" class="btn border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem;">Categoria I</button>
            <button id="filtroCat2" class="btn bg-white border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem; color: #555;">Categoria II</button>
            <button id="filtroDestaque" class="btn bg-white border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem; color: #555;">Aluno destaque</button>
        </div>
        <div id="listaAlunosDesktop" class="d-flex flex-column gap-3">
            <p class="text-center text-muted">(Carregando alunos...)</p>
        </div>
    </div>
</main>

<div class="modal fade" id="modalRemoverAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 320px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-4 pt-5 pb-4">
                <h5 class="mb-4" style="font-size: 1.2rem; font-weight: 400; color: #000;">Deseja remover esse aluno?</h5>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">Remover</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let alunosLista = [];

    function cardAluno(aluno, mobile = false) {
        const nome = aluno.nome_usuario || aluno.nome || `Aluno ${aluno.id_usuario || aluno.id}`;
        const id = aluno.id_usuario || aluno.id;
        const cat = ((Number(id) || 1) % 2) + 1;
        const classe = mobile ? 'mb-2' : 'mb-0';
        return `
            <div id="card-aluno-${id}${mobile ? '-m' : ''}" class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center aluno-card ${classe}" data-cat="${cat}" data-destaque="0">
                <h6 class="mb-0 fw-bold text-dark">${nome}</h6>
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#modalRemoverAluno"><i class="bi bi-trash"></i> Remover</button></li>
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="alternarDestaque(${id})"><i id="icone-estrela-${id}" class="bi bi-star"></i> Destacar</button></li>
                    </ul>
                </div>
            </div>
        `;
    }

    function alternarDestaque(idAluno) {
        document.querySelectorAll(`#card-aluno-${idAluno}, #card-aluno-${idAluno}-m`).forEach((card) => {
            if (!card) return;
            card.classList.toggle('borda-destaque');
            card.dataset.destaque = card.dataset.destaque === '1' ? '0' : '1';
        });
        const iconeEstrela = document.getElementById(`icone-estrela-${idAluno}`);
        if (iconeEstrela) {
            if (iconeEstrela.classList.contains('bi-star')) {
                iconeEstrela.classList.remove('bi-star');
                iconeEstrela.classList.add('bi-star-fill', 'estrela-dourada');
            } else {
                iconeEstrela.classList.remove('bi-star-fill', 'estrela-dourada');
                iconeEstrela.classList.add('bi-star');
            }
        }
    }

    function aplicarFiltro(tipo, seletor = '.aluno-card') {
        document.querySelectorAll('.aluno-card').forEach((card) => {
            if (tipo === 'cat1') {
                card.classList.toggle('d-none', card.dataset.cat !== '1');
                return;
            }
            if (tipo === 'cat2') {
                card.classList.toggle('d-none', card.dataset.cat !== '2');
                return;
            }
            if (tipo === 'destaque') {
                card.classList.toggle('d-none', card.dataset.destaque !== '1');
                return;
            }
            card.classList.remove('d-none');
        });
    }

    async function carregarAlunos() {
        const desktop = document.getElementById('listaAlunosDesktop');
        const mobile = document.getElementById('listaAlunosMobile');
        try {
            const res = await fetch('../../../api/usuarios.php');
            const data = await res.json();
            alunosLista = data.usuarios || [];
            if (!alunosLista.length) {
                desktop.innerHTML = '<p class="text-center text-muted">Nenhum aluno cadastrado.</p>';
                mobile.innerHTML = '<p class="text-center text-muted">Nenhum aluno cadastrado.</p>';
                return;
            }
            desktop.innerHTML = alunosLista.map((a) => cardAluno(a, false)).join('');
            mobile.innerHTML = alunosLista.map((a) => cardAluno(a, true)).join('');
        } catch (error) {
            console.error(error);
            desktop.innerHTML = '<p class="text-center text-danger">Erro ao carregar alunos.</p>';
            mobile.innerHTML = '<p class="text-center text-danger">Erro ao carregar alunos.</p>';
        }
    }

    document.getElementById('filtroCat1')?.addEventListener('click', () => aplicarFiltro('cat1'));
    document.getElementById('filtroCat2')?.addEventListener('click', () => aplicarFiltro('cat2'));
    document.getElementById('filtroDestaque')?.addEventListener('click', () => aplicarFiltro('destaque'));
    document.getElementById('filtroCat1Mobile')?.addEventListener('click', () => aplicarFiltro('cat1'));
    document.getElementById('filtroCat2Mobile')?.addEventListener('click', () => aplicarFiltro('cat2'));
    document.getElementById('filtroDestaqueMobile')?.addEventListener('click', () => aplicarFiltro('destaque'));

    window.addEventListener('load', carregarAlunos);
</script>

<?php
require_once '../componentes/footer.php';
?>