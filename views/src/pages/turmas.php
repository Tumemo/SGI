<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Editar detalhes da turma</p>
    <section id="listaTurmasMobile">
        <p class="text-center text-muted mt-4">(Carregando...)</p>
    </section>


    <!-- Botão de adição (Abre modal de criar nova turma)-->
    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed position-absolute" style="height: 60px; width: 60px; top: 80%; left: 80%; z-index: 10; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </button>
</main>






<!-- main desktop-->
<main class="d-none d-md-flex flex-column main-desktop-layout">

    <a href="./home.php" data-back-link="true" class="btn btn-danger d-inline-flex align-items-center mb-4 border-0 shadow-sm text-decoration-none" style="border-radius: 4px; padding: 8px 15px;">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>
        <span class="fw-bold" style="font-size: 0.9rem;" id="nomeInterclasseTurmas">Interclasse</span>
    </a>

    <h1 class="fw-bold text-dark mb-5 d-flex align-items-center gap-2 fs-2">
        <i class="bi bi-people-fill"></i>
        <span>Turmas</span>
    </h1>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4" id="listaTurmasDesktop"></div>

</main>










<!-- Modal (criar nova turma) -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Nome da turma:</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Turma A" require>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" onchange="mostrarNomeArquivo()">
                        <p style="font-size: 14px;">Adicione aqui o pdf dos alunos da turma criada</p>

                        <label for="arquivoUpload" class="">
                            <i class="bi bi-upload"></i>
                        </label>

                        <span id="nomeArquivo" class="text-muted"></span>
                    </div>
                    <div class=" d-flex justify-content-center gap-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>

<script>
    function mostrarNomeArquivo() {
        const input = document.getElementById('arquivoUpload');
        const span = document.getElementById('nomeArquivo');

        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = "Nenhum arquivo selecionado";
        }
    }
</script>

<script>
    async function carregarTurmasAtivas() {
        const listaMobile = document.getElementById('listaTurmasMobile');
        const listaDesktop = document.getElementById('listaTurmasDesktop');

        try {
            const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
            if (!interclasseAtivo) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhum interclasse ativo.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhum interclasse ativo.</p>';
                return;
            }

            document.getElementById('nomeInterclasseTurmas').innerText = interclasseAtivo.nome_interclasse;
            window.SGIInterclasse.updatePageTitle(interclasseAtivo.nome_interclasse);

            const [categoriasRes, turmasRes] = await Promise.all([
                fetch(`../../../api/categorias.php?id_interclasse=${interclasseAtivo.id_interclasse}`),
                fetch(`../../../api/turmas.php?id_interclasse=${interclasseAtivo.id_interclasse}`)
            ]);

            const categorias = await categoriasRes.json();
            const turmas = await turmasRes.json();
            const mapaCategorias = new Map((categorias || []).map((cat) => [String(cat.id_categoria), cat.nome_categoria]));
            const turmasComCategoria = [];

            for (const categoria of categorias || []) {
                const resTurmasCategoria = await fetch(`../../../api/turmas.php?id_interclasse=${interclasseAtivo.id_interclasse}&id_categoria=${categoria.id_categoria}`);
                const listaTurmasCategoria = await resTurmasCategoria.json();
                (listaTurmasCategoria || []).forEach((turma) => {
                    turmasComCategoria.push({
                        ...turma,
                        id_categoria: categoria.id_categoria
                    });
                });
            }
            const listaFinal = turmasComCategoria.length ? turmasComCategoria : (turmas || []);

            if (!listaFinal?.length) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma turma cadastrada.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma turma cadastrada.</p>';
                return;
            }

            listaMobile.innerHTML = listaFinal.map((turma) => `
                <a href="./modalidades_alunos.php?id=${interclasseAtivo.id_interclasse}&id_turma=${turma.id_turma}" class="text-decoration-none text-black">
                    <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                        <div>
                            <h2 class="m-0 fs-5">${turma.nome_turma}</h2>
                            <small class="text-muted">${mapaCategorias.get(String(turma.id_categoria || turma.categorias_id_categoria)) || 'Categoria vinculada'}</small>
                        </div>
                        <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
                    </div>
                </a>
            `).join('');

            listaDesktop.innerHTML = listaFinal.map((turma) => `
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">${turma.nome_turma}</h5>
                            <button type="button" class="btn btn-danger p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 35px; height: 35px; border-radius: 6px;">
                                <i class="bi bi-mortarboard-fill text-white"></i>
                            </button>
                        </div>
                        <div class="mb-4 text-muted">${mapaCategorias.get(String(turma.id_categoria || turma.categorias_id_categoria)) || 'Categoria vinculada'}</div>
                        <div class="mt-auto">
                            <a href="./modalidades_alunos.php?id=${interclasseAtivo.id_interclasse}&id_turma=${turma.id_turma}" class="text-decoration-none">
                                <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error(error);
            listaMobile.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
        }
    }

    window.addEventListener('load', carregarTurmasAtivas);
</script>



<?php
require_once '../componentes/footer.php';
?>