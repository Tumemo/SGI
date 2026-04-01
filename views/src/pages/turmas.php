<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main class="position-relative">
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
require_once '../componentes/navbar.php';
?>