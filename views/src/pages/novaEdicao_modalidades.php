<?php
$titulo = "Modalidades";
$textTop = "Modalidades";
require_once '../components/header.php';
?>

<main class="px-4 mt-4">
    <!-- titulo de cima da pagina -->
    <p class="text-center text-secondary">Adicione as modalidades da edição</p>

    <!-- card no meio da tela -->
    <div class="p-3 bg-white shadow rounded border" style="min-height: 50vh;">
        <!-- caixa de pesquisa -->
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Nome da nova turma (ex: 1º ano)" aria-label="Recipient’s username" aria-describedby="basic-addon2">
            <span class="input-group-text text-white" id="basic-addon2" style="background-color: #e30613;">Adicionar</span>
        </div>


        <!-- div das modalidades -->
        <div class=" ">

            <!-- modalidade -->
            <div class=" border rounded justify-content-between mb-3 px-3 align-items-center py-2 input-group flex shadow-sm" style="background-color: #eaeaea;">
                Futebol
            </div>





        </div>
    </div>



    <a href="#" class="btn text-white px-5 mt-5 position-absolute start-50 translate-middle" style="background-color: #e30613;">Criar</a>

</main>

<?php
require_once '../components/navbar.php';
?>