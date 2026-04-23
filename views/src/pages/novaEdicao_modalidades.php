<?php
$titulo = "Nova Edição - Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';

?>

<!-- main mobile -->
<main class=" position-relative d-md-none" style="margin-bottom: 120px;">
    <section class="m-auto d-flex flex-column gap-3 mt-5" style="width: 90%;">
        <section class="rounded bg-white">
            <form class="input-group my-3 m-auto" style="width: 90%;">
                <input type="text" placeholder="Nome da nova modalidades" class="form-control" style="font-size: 0.7rem;">
                <button type="submit" class="btn btn-danger">Adicionar</button>
            </form>
            <section class="my-3 m-auto" style="width: 90%;">
                <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 ps-4 w-100 rounded m-auto">Futsal</h2>
                <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 ps-4 w-100 rounded m-auto">Basquete</h2>
            </section>
        </section>
    </section>
    <a href="./edicao_regulamentos.php" class="btn btn-danger position-absolute position-fixed translate-middle" style="width: max-content;  top: 87%; left: 50%; z-index: 10; cursor: pointer;">Continuar</a>
</main>



<!-- main desktop -->
<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block" style="padding-top: 2rem;">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-4">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trophy fs-5"></i> Modalidades
                </h5>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2" style="color: #ed1c24; border: 1px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <i class="bi bi-plus-circle"></i> Adicionar
                    </button>

                    <a href="./edicao_regulamentos.php" class="text-decoration-none">
                        <button class="btn fw-semibold rounded-3 px-4 py-2 text-white w-100 w-sm-auto" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                            Continuar
                        </button>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 mt-2" id="listarModalidades">

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-dribbble fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Basquete</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-record-circle fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Futebol</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-vinyl fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Vôlei</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-trophy fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Handebol</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-trophy fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Tênis de Mesa</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-trophy fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">Corrida</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            </div>

        </div>
    </div>
</main>




<!-- modal de criar nova modalidade -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4 p-2">

            <div class="modal-header border-0 pb-0 justify-content-center">
                <h5 class="modal-title fw-bold text-center w-100" style="color: #ed1c24;">
                    CRIAR NOVA MODALIDADE
                </h5>
            </div>

            <div class="modal-body pt-3 pb-3">
                <p class="text-dark mb-2 fw-medium" style="font-size: 0.95rem;">
                    Insira o nome da sua nova edição:
                </p>
                <input
                    type="text"
                    class="form-control form-control-lg shadow-sm rounded-3 text-secondary"
                    placeholder="EX: Futsal"
                    style="font-size: 0.95rem; border: 1px solid #dee2e6;"
                    id="nomeNovaEdicao">
            </div>

            <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2">
                <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2" data-bs-dismiss="modal" style="color: #ed1c24; border: 1px solid #ed1c24;">
                    Cancelar
                </button>
                <button type="button" class="btn fw-semibold rounded-3 px-4 py-2 text-white" data-bs-dismiss="modal" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                    Criar
                </button>
            </div>

        </div>
    </div>
</div>





<script>
    // console.log(localStorage.getItem("nomeNovaEdicao"))
    // console.log(localStorage.getItem("anoNovaEdicao"))

    async function ListarModalidades() {
        const response = await fetch('../../../api/modalidades.php');
        const data = await response.json();

        const container = document.getElementById('listarModalidades');
        container.innerHTML = '';

        data.forEach(modalidade => {
            const modalidadeElement = document.createElement('div');
            modalidadeElement.classList.add('col-12', 'col-md-6', 'col-lg-4');
            modalidadeElement.innerHTML = `
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-trophy fs-5 text-dark"></i>
                        <span class="fw-bold text-dark fs-6">${modalidade.nome}</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                </div>
            `;
            container.appendChild(modalidadeElement);
        });
    }
    ListarModalidades();


</script>

<?php
require_once '../componentes/footer.php';
?>