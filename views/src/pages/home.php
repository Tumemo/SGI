<?php
$titulo = "Home";
$textTop = "";
$btnVoltar = false;
require_once '../componentes/header.php';
?>

<main>
    <div class="m-auto" style="width: 90%;">
        <a href="./novaEdicao.php" class="btn btn-danger d-flex gap-2 mt-3" style="width: max-content;"><i class="bi bi-plus-circle"></i>Criar Nova Edição</a>
    </div>
    <div id="caixaListar" class="pb-5 mb-2"></div>


    <!-- Dado estatico caso nescessario -->

    <!-- <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3" style="width: 80%;">
        <a href="./edicao.php" class="text-decoration-none text-dark">
            <div>
                <h2 class="m-0">Interclasse ???</h2>
                <p class="text-secondary m-0">(Em Andamento)</p>
            </div>
        </a>
        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
    </div> -->
</main>

<script>
    async function listarInterclasses() {
        const listar = document.getElementById('caixaListar')
        try {
            const res = await axios.get("../../../api/interclasse.php")
            if (res.length === 0) {
                listar.innerHTML = `<p class="text-container">Nenhum interclasse encontrado</p>`
                return;
            }
            console.log(res)

            listar.innerHTML = res.data.map((item) =>
            `
            <a href="./edicao.php" class="text-decoration-none text-dark">
                <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3" style="width: 90%;" ${item.id_interclasse}>
                        <div>
                            <h2 class="m-0 fs-3">${item.nome_interclasse}</h2>
                            <p class="text-secondary m-0">${item.ano_interclasse}</p>
                        </div>
                        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                        </div>
                        </a>
            `
            ).join('')


        } catch (error) {
            console.log(error)
            listar.innerHTML = `<p class="text-container">Erro!! ${error}</p>`
        }
    }
    listarInterclasses()
</script>

<?php
require_once '../componentes/navbar.php';
?>