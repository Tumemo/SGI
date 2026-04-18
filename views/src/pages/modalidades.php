<?php
$titulo = "Basquete";
$textTop = "Basquete";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';

// Simulando dados que viriam do seu banco de dados na integração
$alunos = [
    ['id' => 1, 'nome' => 'Ana Clara'],
    ['id' => 2, 'nome' => 'Geovana'],
    ['id' => 3, 'nome' => 'Lara'],
    ['id' => 4, 'nome' => 'Braien']
];
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

<main class="bg-light min-vh-100 p-3" style="padding-top: 2rem;">
    <div class="mx-auto" style="max-width: 450px;">

        <div class="d-flex gap-2 mb-4 overflow-auto pb-1" style="white-space: nowrap; scrollbar-width: none;">
            <button class="btn border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem; background-color: #e6e6e6; color: #333;">
                Categoria I
            </button>
            <button class="btn bg-white border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem; color: #555;">
                Categoria II
            </button>
            <button class="btn bg-white border border-secondary-subtle rounded-3 px-2 py-1" style="font-size: 0.85rem; color: #555;">
                Aluno destaque
            </button>
        </div>

        <div class="d-flex flex-column gap-3">
            <?php foreach ($alunos as $aluno): ?>
                <div id="card-aluno-<?= $aluno['id'] ?>" class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><?= $aluno['nome'] ?></h6>

                    <div class="dropdown">
                        <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>

                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                            <li>
                                <button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#modalRemoverAluno">
                                    <i class="bi bi-trash"></i> Remover
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="alternarDestaque(<?= $aluno['id'] ?>)">
                                    <i id="icone-estrela-<?= $aluno['id'] ?>" class="bi bi-star"></i> Destacar
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<!-- modal de remover aluno -->
<div class="modal fade" id="modalRemoverAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 320px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-4 pt-5 pb-4">
                <h5 class="mb-4" style="font-size: 1.2rem; font-weight: 400; color: #000;">
                    Deseja remover<br>esse aluno?
                </h5>

                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">
                        Não
                    </button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">
                        Remover
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para adicionar/remover a borda dourada e alterar o ícone da estrela
    function alternarDestaque(idAluno) {
        const card = document.getElementById(`card-aluno-${idAluno}`);
        const iconeEstrela = document.getElementById(`icone-estrela-${idAluno}`);

        if (card && iconeEstrela) {
            // Alterna a classe da borda
            card.classList.toggle('borda-destaque');

            // Verifica se a estrela está vazia (bi-star)
            if (iconeEstrela.classList.contains('bi-star')) {
                // Muda para preenchida e dourada
                iconeEstrela.classList.remove('bi-star');
                iconeEstrela.classList.add('bi-star-fill', 'estrela-dourada');
            } else {
                // Volta para vazia e cor padrão
                iconeEstrela.classList.remove('bi-star-fill', 'estrela-dourada');
                iconeEstrela.classList.add('bi-star');
            }
        }
    }
</script>

<?php
require_once '../componentes/footer.php';
?>