<?php 
$titulo = "Home";
$textTop = "";
$btnVoltar = false;
require_once '../componentes/header.php';
?>

<main>
    <div class="m-auto" style="width: 80%;">
        <a href="./novaEdicao.php" class="btn btn-danger d-flex gap-2 mt-3" style="width: max-content;"><i class="bi bi-plus-circle"></i>Criar Nova Edição</a>
    </div>
    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3" style="width: 80%;">
        <a href="./edicao.php" class="text-decoration-none text-dark">
            <div>
                <h2 class="m-0">Interclasse ???</h2>
                <p class="text-secondary m-0">(Em Andamento)</p>
            </div>
        </a>
        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
    </div>
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