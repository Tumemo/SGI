<?php 
$titulo = "Nova Edição";
$textTop = "Nova Edição";
$btnVoltar = true;
require_once '../componentes/header.php';
?>


<main>
    <section class="m-auto mt-4 shadow px-3 pb-5 pt-3 rounded" style="width: 80%;">
        <h2 class="fs-4">Insira o nome da sua nova edição:</h2>
        <form>
            <div>
                <input type="text" class="form-control">
            </div>
            <div>
                <label for="ano" class="form-label fs-3">Ano</label>
                <input type="text" class="form-control">
            </div>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <button class="btn btn-outline-danger">Cancelar</button>
                <a href="./novaEdicao_modalidades.php" class="btn btn-danger">Criar</a>
            </div>
        </form>
    </section>
</main>

<?php 

require_once '../componentes/navbar.php';
?>