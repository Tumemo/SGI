<?php
$titulo = "Home";
$textTop = "";
$btnVoltar = false;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class=" d-md-none">
    <button class="mx-4 btn btn-danger d-flex gap-2 mt-3 align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus-circle"></i>Criar Nova Edição
    </button>
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



<!-- main desktop -->
<main class="d-none d-md-flex">

    <!-- conteudo do home -->
    <section class="mt-4 container">

        <h1 class="fs-2">Edições do interclasse</h1>

        <button class=" btn btn-outline-danger d-flex gap-2 mt-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i>Criar Nova Edição
        </button>

        <!-- table -->
        <div>
            <!-- thead -->
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-4 1875rem">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <!-- tbody -->
            <div class="mt-2">
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-2">
                    <div class="col-4">Interclasse</div>
                    <div class="col-4 text-center">2026</div>
                    <div class="col-4 text-center"><span class="bg-danger rounded-3 text-white px-5 py-1">Ativo</span></div>
                </div>
                
            </div>
        </div>

    </section>
</main>



<!-- script da pagina -->

<script>
    async function listarInterclasses() {
        const listar = document.getElementById('caixaListar')
        try {
            const res = await axios.get("../../../api/interclasse.php")
            if (res.length === 0) {
                listar.innerHTML = `<p class="text-container">Nenhum interclasse encontrado</p>`
                return;
            }

            listar.innerHTML = res.data.map((item) =>
                `
            <a href="./edicao.php" class="text-decoration-none text-dark">
                <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1  " style="width: 90%;" ${item.id_interclasse}>
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
            listar.innerHTML = `<p class="mt-3 text-center text-danger fs-3">Erro ao ver interclasses!!</p>`
        }
    }
    listarInterclasses()
</script>



<!-- modal criar nova edição  -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Edição</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6">Insira o nome da sua nova edição:</h2>
                <form id="formulario">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: interclasse 2026" id="nomeNovaEdicao">
                    </div>
                    <div class="mt-4">
                        <label for="ano" class="form-label fs-6">Ano</label>
                        <input type="text" for="ano" class="form-control" placeholder="Ex: 2026" id="anoNovaEdicao" value="2026">
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-5 pt-5">
                        <a href="./home.php" class="btn btn-outline-danger">Cancelar</a>
                        <!-- <a href="./novaEdicao_modalidades.php" class="btn btn-danger" id="criarNovaEdicao">Criar</a> -->
                        <button type="submit" class="btn btn-danger" id="criarNovaEdicao">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>

<script>
    let data = new Date()
    let dia = data.getDate()
    let mes = data.getMonth() + 1
    let ano = data.getFullYear()
    let dataFormatada = `${ano}-${mes}-${dia}`
    const nomeNovaEdicao = document.getElementById("nomeNovaEdicao")
    const formulario = document.getElementById("formulario")
    formulario.addEventListener("submit",async (evento)=>{
        evento.preventDefault()
        if(nomeNovaEdicao.value !== "" && anoNovaEdicao.value !== "")
        await axios.post("../../../api/interclasse.php",{
            nome_interclasse: nomeNovaEdicao.value.trim(),
            ano_interclasse: dataFormatada,
            pdf_regulamento: "caminho.pdf"
        })
        .then(res => console.log(res))
        .catch(error => console.log(error))
    })

</script>

<?php
require_once '../componentes/footer.php';
?>