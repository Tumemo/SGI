<?php
$titulo = "Home";
$textTop = '';
require_once '../components/header.php';
?>

<main class="px-4 mt-4">
    <a href="./novaEdicao.php" class="btn btn-danger p-2 mb-3"><i class="bi bi-plus-circle me-2"></i>Criar Nova Adição</a>
    <section id="listInter">


        <!-- ===================================================================== -->
        <!-- dados estaticos utilizado antes de fazer a integração com a API -->
        <!-- ===================================================================== -->


        <!--<a href="./edicao.php" class="text-decoration-none text-black">
             <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
                <div>
                    <h2 class="fs-2">Interclasse 2026</h2>
                    <h3 class="fs-6 text-secondary">(Em Andamamento)</h3>
                </div>
                <div>
                    <img src="../../public/icons/arrow.svg" alt="icone de seta">
                </div>
            </div>
        </a>
        <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
            <div>
                <h2 class="fs-2">Interclasse 2025</h2>
                <h3 class="fs-6 text-secondary">(Finalizado)</h3>
            </div>
            <div>
                <img src="../../public/icons/arrow.svg" alt="icone de seta">
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3">
            <div>
                <h2 class="fs-2">Interclasse 2024</h2>
                <h3 class="fs-6 text-secondary">(Finalizado)</h3>
            </div>
            <div>
                <img src="../../public/icons/arrow.svg" alt="icone de seta">
            </div>
        </div> -->
    </section>
</main>


<script>
    async function listarInterclasses() {
        try {
            const url = await fetch('http://localhost/SGI/api/view/interclasse.php');
            const res = await url.json();
            const obj = document.getElementById('listInter');

            obj.innerHTML = res.map((item, key) => (
                `
            <a href="./edicao.php" class="text-decoration-none text-black" ${key.id_interclasse}>
                <div class="d-flex justify-content-between align-items-center shadow p-3 w-100 mb-3 rounded">
                    <div>
                        <h2 class="fs-2">${item.nome_interclasse}</h2>
                        <h3 class="fs-6 text-secondary">${item.ano_interclasse}</h3>
                    </div>
                    <div>
                        <img src="../../public/icons/arrow.svg" alt="icone de seta">
                    </div>
                </div>
            </a>
            `
            )).join('')
        } catch (error) {
            console.log("Erro: ".error)
            document.getElementById('listInter').innerHTML = `<p class="text-danger fs-4 m-auto">Erro ao carregar os Interclasses!!</p>`
        }
    }

    listarInterclasses()
</script>

<?php
require_once '../components/navbar.php'
?>