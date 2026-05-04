<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="position-relative d-md-none">
    <p class="text-center mt-3 text-secondary">Editar detalhes da turma</p>
    <section>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3 border border-1" style="width: 90%;">
                <h2 class="m-0 fs-4 text">3º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3 border border-" style="width: 90%;">
                <h2 class="m-0 fs-4">2º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
        <a href="#" class="text-decoration-none text-black">
            <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3 border border-" style="width: 90%;">
                <h2 class="m-0 fs-4">1º ano EM</h2>
                <picture>
                    <img src="../../public/icons/arrow-right.svg" alt="Seta para direita">
                </picture>
            </div>
        </a>
    </section>


    <!-- Botão de adição (Abre modal de criar nova turma)-->
    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed position-absolute" style="height: 60px; width: 60px; top: 80%; left: 80%; z-index: 10; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </button>
</main>






<!-- main desktop-->
<main class="d-none d-md-flex flex-column main-desktop-layout">

    <button type="button" class="btn btn-danger d-flex align-items-center mb-4 border-0 shadow-sm" style="border-radius: 4px; padding: 8px 15px;">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>
        <span class="fw-bold" style="font-size: 0.9rem;">Interclasse 2026</span>
    </button>

    <h1 class="fw-bold text-dark mb-5 d-flex align-items-center gap-2 fs-2">
        <i class="bi bi-people-fill"></i>
        <span>Turmas</span>
    </h1>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">3º Médio</h5>
                    <button type="button" class="btn btn-danger p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; border-radius: 6px;">
                        <i class="bi bi-mortarboard-fill text-white"></i>
                    </button>
                </div>

                <div class="d-flex gap-3 mb-4">

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">EQUIPES</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">06</div>
                    </div>

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">ALUNOS</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">30</div>
                    </div>

                </div>

                <div class="mt-auto">
                    <a href="./modalidades_alunos.php" class="text-decoration-none">
                        <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                            VER DETALHES <i class="bi bi-arrow-right"></i>
                        </button>
                    </a>
                </div>

            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">2º Médio</h5>
                    <button type="button" class="btn btn-danger p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; border-radius: 6px;">
                        <i class="bi bi-mortarboard-fill text-white"></i>
                    </button>
                </div>

                <div class="d-flex gap-3 mb-4">

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">EQUIPES</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">06</div>
                    </div>

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">ALUNOS</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">30</div>
                    </div>

                </div>

                <div class="mt-auto">
                    <a href="./modalidades_alunos.php" class="text-decoration-none">
                        <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                            VER DETALHES <i class="bi bi-arrow-right"></i>
                        </button>
                    </a>
                </div>

            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">1º Médio</h5>
                    <button type="button" class="btn btn-danger p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; border-radius: 6px;">
                        <i class="bi bi-mortarboard-fill text-white"></i>
                    </button>
                </div>

                <div class="d-flex gap-3 mb-4">

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">EQUIPES</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">06</div>
                    </div>

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">ALUNOS</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">30</div>
                    </div>

                </div>

                <div class="mt-auto">
                    <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                        VER DETALHES <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">9º Ano</h5>
                    <button type="button" class="btn btn-danger p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; border-radius: 6px;">
                        <i class="bi bi-mortarboard-fill text-white"></i>
                    </button>
                </div>

                <div class="d-flex gap-3 mb-4">

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">EQUIPES</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">06</div>
                    </div>

                    <div class="bg-white border rounded-3 p-2 text-center w-50 shadow-sm">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">ALUNOS</small>
                        <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;">30</div>
                    </div>

                </div>

                <div class="mt-auto">
                    <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                        VER DETALHES <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

            </div>
        </div>



    </div>

</main>










<!-- Modal (criar nova turma) -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                        <p style="font-size: 14px;">Adicione aqui o pdf dos alunos da turma criada</p>

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

<script>
    function mostrarNomeArquivo() {
        const input = document.getElementById('arquivoUpload');
        const span = document.getElementById('nomeArquivo');

        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = "Nenhum arquivo selecionado";
        }
    }
</script>



<?php
require_once '../componentes/footer.php';
?>