<?php
$textTop = "Modalidades";
$titulo = "Modalidades";
require_once '../components/header.php';

?>
<main>
    <h5 class="fs-6 text-secondary mb-3 text-center mt-3">Escolha uma modalidade para editar</h5>
    <section class="px-4 mt-3 text-center" id="listModalidades">
        <!-- <a href="./modalidades.php" class="text-decoration-none text-black">
            <div class="d-flex ps-4 justify-content-between align-items-center shadow p-3 mb-3">
                <i class="bi bi-trophy fs-2"></i>
                <h2 class="m-0">Modalidades</h2>
                <img src="../../public/icons/arrow.svg" alt="icone seta">
            </div>
        </a> -->

    </section>
</main>


<script>
    async function listarModalidades() {
        try {
            const url = await fetch('../../../api/view/modalidades.php');
            const res = await url.json();
            const obj = document.getElementById('listModalidades');

            obj.innerHTML = res.map((item, key) => (
                `
                <a href="./modalidades.php" class="text-decoration-none text-black" ${key.id_modalidade}>
                    <div class="d-flex ps-4 justify-content-between align-items-center shadow p-3 mb-3">
                        <i class="bi bi-trophy fs-2"></i>
                        <h2 class="m-0 fs-3">${item.nome_modalidade}</h2>
                        <img src="../../public/icons/arrow.svg" alt="icone seta">
                    </div>
                </a>
                `
            )).join('')

        } catch (error) {
            console.log("Erro ao encontrar os dados: " + error);
            document.getElementById('listModalidades').innerHTML = `<p class="text-danger fs-4 m-auto">Erro ao carregar as modalidades!!</p>`;
        }
    }

    listarModalidades();
</script>
<?php
require_once '../components/navbar.php';
?>