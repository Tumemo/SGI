<?php 
$titulo = "Nova Edição";
$textTop = "Nova Edição";
$btnVoltar = true;
require_once '../componentes/header.php';
?>


<main>
    <section class="m-auto mt-4 shadow px-3 pb-3 pt-3 rounded border border-1" style="width: 80%;">
        <h2 class="fs-6">Insira o nome da sua nova edição:</h2>
        <form>
            <div>
                <input type="text" class="form-control" placeholder="Ex: interclasse 2026">
            </div>
            <div class="mt-4">
                <label for="ano" class="form-label fs-6">Ano</label>
                <input type="text" for="ano" class="form-control" placeholder="Ex: 2026">
            </div>
            <div class="d-flex justify-content-center gap-2 mt-5 pt-5">
                <a href="./home.php" class="btn btn-outline-danger">Cancelar</a>
                <a href="./novaEdicao_modalidades.php" class="btn btn-danger">Criar</a>
            </div>
        </form>
    </section>
</main>

<?php 

require_once '../componentes/navbar.php';
?>