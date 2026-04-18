<?php
$titulo = "Categorias";
$textTop = "Categorias";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>


<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-secondary text-center my-3" style="font-size: 14px;">Selecione uma categoria para adicionar turmas</p>
    <button data-bs-toggle="modal" data-bs-target="#criarTurma" class="bg-white d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
        <i class="bi bi-trophy fs-1"></i>
        <h2 class="m-0 fs-3 ">Categoria I</h2>
        <picture>
            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
        </picture>
    </button>

    <button data-bs-toggle="modal" data-bs-target="#criarTurma" class="bg-white d-flex m-auto justify-content-between align-items-center shadow py-3 px-4 mb-3 border border-1" style="width: 90%;">
        <i class="bi bi-trophy fs-1"></i>
        <h2 class="m-0 fs-3 ">Categoria II</h2>
        <picture>
            <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
        </picture>
    </button>

    <section class="d-flex gap-4 mt-3 position-absolute position-fixed translate-middle" style="width: max-content;  top: 85%; left: 50%; z-index: 10; cursor: pointer;">
        <button data-bs-toggle="modal" data-bs-target="#exampleModal" class="btn btn-outline-danger">Adicionar Categoria</button>
        <a href="./home.php" class="btn btn-danger">Continuar</a>
    </section>
</main>






<!-- main desktop -->
<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block container" style="padding-top: 2rem;">

    <div class="container-fluid px-0">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0">
                <i class="bi bi-bookmark fs-5"></i> Categorias
            </h4>
        </div>

        <div class="row g-4">

            <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
                    <div class="card-body p-0 d-flex flex-column">

                        <h4 class="fw-bold text-dark mb-4 pb-2">Categoria I</h4>

                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                <div class="fs-5 text-dark">5</div>
                            </div>

                            <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                <div class="fs-5 text-dark">18</div>
                            </div>
                        </div>

                        <button class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;">
                            Ver detalhes <i class="bi bi-arrow-right"></i>
                        </button>

                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
                    <div class="card-body p-0 d-flex flex-column">

                        <h4 class="fw-bold text-dark mb-4 pb-2">Categoria II</h4>

                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                <div class="fs-5 text-dark">7</div>
                            </div>

                            <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                <div class="fs-5 text-dark">10</div>
                            </div>
                        </div>

                        <button class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;">
                            Ver detalhes <i class="bi bi-arrow-right"></i>
                        </button>

                    </div>
                </div>
            </div>

        </div>
    </div>
</main>


<!-- modal criar nova turma -->
<div class="modal fade" id="criarTurma" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Nome da turma:</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Turma A" require>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" onchange="mostrarNomeArquivo()">
                        <p style="font-size: 13px;">Adicione aqui o pdf dos alunos da turma criada</p>

                        <label for="arquivoUpload" class="">
                            <i class="bi bi-upload"></i>
                        </label>

                        <span id="nomeArquivo" class="text-muted"></span>
                    </div>
                    <div class=" d-flex justify-content-center gap-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>




<!-- modal criar nova categoria  -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6">Insira o nome da sua nova categoria:</h2>
                <form>
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: interclasse 2026" id="nomeNovaEdicao">
                    </div>
                    <div class="d-flex justify-content-center gap-2 pt-5">
                        <a href="#" class="btn btn-outline-danger " class="btn-close" data-bs-dismiss="modal" aria-label="Close">Cancelar</a>
                        <a href="./novaEdicao_modalidades.php" class="btn btn-danger" id="criarNovaEdicao">Criar</a>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>

<?php

require_once '../componentes/footer.php';
?>