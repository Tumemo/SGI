<?php
$textTop = "Nova Edição";
$titulo = "Turmas";
require_once '../components/header.php';
?>

<main class="px-4 mt-4">

    <h5 class="fs-6 text-secondary mb-3 text-center mt-3">Editar detalhes das turmas</h5>
    <section class="text-center" id="listTurmas">
        
    </section>

</main>

<script>
    async function listarTurmas() {
        try {
            const url = await fetch('http://localhost/SGI/api/view/turmas.php');
            const res = await url.json();
            const obj = document.getElementById('listTurmas');

            obj.innerHTML = res.map((item, key) => (
                `
                <a href="#" class="text-decoration-none text-black" ${key.id_turma}>
                    <div class="d-flex ps-4 justify-content-between align-items-center shadow-sm p-3 mb-3 rounded sombras">
                        <h2 class="m-0 font-normal">${item.nome_turma}</h2>
                        <img src="../../public/icons/arrow.svg" alt="icone seta">
                    </div>
                </a>
                `
            )).join('');

        } catch (error) {
            console.log("Error: " . error);
            document.getElementById('listTurmas').innerHTML = `<p class="text-danger fs-4 m-auto">Erro ao carregar as Turmas!!</p>`;
        }


    }

    listarTurmas()
</script>


<?php
require_once '../components/navbar.php'
?>