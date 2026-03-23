<?php 
$titulo = "Home";
$textTop = "";
$btnVoltar = false;
require_once '../componentes/header.php';
?>

<main>
    <div class="m-auto" style="width: 80%;">
        <a href="./novaEdiçao.php" class="btn btn-danger d-flex gap-2 mt-3" style="width: max-content;"><i class="bi bi-plus-circle"></i>Criar Nova Edição</a>
    </div>
    <section class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-4 rounded-3 my-3" style="width: 80%;" id="listarInterclasses">
    </section>
</main>

<script>
    async function listarInterclasses(){
        await axios.get("../../../api/interclasse.php")
        .then(res => console.log(res))
        .catch(error => console.log(error))
    }
</script>

<?php 
require_once '../componentes/navbar.php';
?>