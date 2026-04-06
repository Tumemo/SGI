<?php 
$titulo = "Nova Edição - Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/header.php';


?>

<main class=" position-relative" style="margin-bottom: 120px;">
    <section class="m-auto d-flex flex-column gap-3 mt-5" style="width: 90%;">
        <section class="shadow rounded">
            <div class="input-group my-3 m-auto" style="width: 90%;">
                <input type="text" placeholder="Nome da nova modalidades" class="form-control" style="font-size: 0.7rem;">
                <button class="btn btn-danger">Adicionar</button>
            </div>
         <section class="my-3 m-auto" style="width: 90%;">
                <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 w-100 rounded m-auto">Futsal</h2>
                <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 w-100 rounded m-auto">Basquete</h2>
            </section>
        </section>
    </section>
    <a href="./regulamentos.php" class="btn btn-danger position-absolute position-fixed translate-middle" style="width: max-content;  top: 87%; left: 50%; z-index: 10; cursor: pointer;">Continuar</a>
</main>
<script>
    // console.log(localStorage.getItem("nomeNovaEdicao"))
    // console.log(localStorage.getItem("anoNovaEdicao"))
</script>
<?php 

require_once '../componentes/navbar.php';
?>