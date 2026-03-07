<?php
$titulo = "Nova Edição";
$textTop = "Nova Edição";
require_once '../components/header.php';
?>

<main class=" px-4 mt-4 h-100 position-absolute">
    <!-- n sei oq é isso, mas é a parte de nome em cima do card, no topo da tela -->
    <input type="text" name="nome" id="nome" placeholder="Nome" class="form-control mb-3">

    <!-- card no meio da tela -->
    <div class="p-3 bg-white shadow rounded border" style="min-height: 50vh;">

        <!-- caixa de adicionar nova turma -->
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Nome da nova turma (ex: 1º ano)" aria-label="Recipient’s username" aria-describedby="basic-addon2">
            <span class="input-group-text text-white" id="basic-addon2" style="background-color: #e30613;">Adicionar</span>
        </div>


        <!-- div das turmas -->
        <div class=" ">


        <!-- turmas -->
            <div class=" border rounded justify-content-between mb-3 px-2 align-items-center py-2 input-group flex shadow-sm" style="background-color: #eaeaea;">
                1ºano EM
                <input type="file" class="d-none" id="customFile">

                <label class="border-0 px-4 py-1 opacity-75 m-0" for="customFile" style="background-color: #d4d4d4; color: #555;">
                    Enviar o arquivo +
                </label>
            </div>


            <div class=" border rounded justify-content-between mb-3 px-2 align-items-center py-2 input-group flex shadow-sm" style="background-color: #eaeaea;">
                1ºano EM
                <input type="file" class="d-none" id="customFile">

                <label class="border-0 px-4 py-1 opacity-75" for="customFile" style="background-color: #d4d4d4; color: #555;">
                    Enviar o arquivo +
                </label>
            </div>


            <div class=" border rounded justify-content-between mb-3 px-2 align-items-center py-2 input-group flex shadow-sm" style="background-color: #eaeaea;">
                1ºano EM
                <input type="file" class="d-none" id="customFile">

                <label class="border-0 px-4 py-1 opacity-75" for="customFile" style="background-color: #d4d4d4; color: #555;">
                    Enviar o arquivo +
                </label>
            </div>



        </div>
    </div>

    <a href="#" class="btn text-white px-5 m-auto mt-5 position-relative bottom-0 start-50 translate-middle" style="background-color: #e30613;">Continuar</a>
</main>


<?php
require_once '../components/navbar.php';
?>