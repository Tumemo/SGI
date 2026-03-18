<?php
$titulo = "Home";
$textTop = '';
require_once '../components/header.php';
?>

<!-- Página Home, onde terá as edições de interclasse -->
<main class="px-4 mt-4 py-5">
    <!-- Botão para criar uma nova Edição de interclasse -->
    <a href="./novaEdicao.php" class="btn btn-danger p-2 mb-3"><i class="bi bi-plus-circle me-2"></i>Criar Nova Adição</a>
    <!-- Lista onde ficará todas as edições, e seus respectivos status -->
    <section id="listInter">

    </section>
</main>


<script>
    // Função para listar Interclasse
    async function listarInterclasses() {
        try {
            // Faz a chamada para API Interclasse
            const url = await fetch('../../../api/view/interclasse.php');
            // Converte  a resposta da API para JSON
            const res = await url.json();
            // Pega o elemento HTML com a classe listInter
            const listaInterclasse = document.getElementById('listInter');
            // Lista todas interclasses de forma dinamica
            listaInterclasse.innerHTML = res.map((item, key) => (
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
            // Mostra o erro no console e no site aparece um erro em velho
            console.log("Erro: ".error)
            document.getElementById('listInter').innerHTML = `<p class="text-danger fs-4 m-auto">Erro ao carregar os Interclasses!!</p>`
        }
    }
    // Chama a função listarInterclasse
    listarInterclasses()
</script>

<?php
require_once '../components/navbar.php'
?>