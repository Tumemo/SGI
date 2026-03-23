<?php 
$titulo = "Nova Edição - Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/header.php';


?>

<main>
    <section class="m-auto d-flex flex-column gap-3 mt-5" style="width: 90%;">
        <section class="shadow rounded">
            <div class="input-group my-3 m-auto" style="width: 90%;">
                <input type="text" placeholder="Nome da nova modalidades" class="form-control" style="font-size: 0.7rem;">
                <button class="btn btn-danger">Adicionar</button>
            </div>
         <section class="my-3">
                <div class="bg-secondary-subtle rounded m-auto px-4 py-1 mb-3" style="width: 90%;">
                    <h2 class="m-0 fs-6">Futsal</h2>
                </div>
            </section>
        </section>
        <a href="./regulamentos.php" class="btn btn-danger m-auto" style="width: max-content;">Continuar</a>
    </section>
</main>

<?php 

require_once '../componentes/navbar.php';
?>