<?php
$tituloPagina = 'SGI - Mesário - Categorias';
$titulo = 'Categorias';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<style>
    .categoria-item--selected {
        border: 2px solid #ed1c24 !important;
        box-shadow: 0 0.25rem 0.75rem rgba(237, 28, 36, 0.12) !important;
    }
</style>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <a href="./dashboard.php" id="btnVoltarCatMobile" class="btn btn-danger btn-sm mt-3 ms-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <p class="text-secondary text-center my-3" style="font-size: 14px;">Visualize as categorias da competição</p>

    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarCatDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0">
                <i class="bi bi-bookmark fs-5"></i> Categorias
            </h4>
        </div>

        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>
    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    function selecionarCategoria(idCategoria, el) {
        document.querySelectorAll('.categoria-item').forEach((item) => {
            item.classList.remove('categoria-item--selected');
        });
        el.classList.add('categoria-item--selected');
    }

    if (!idInterclasse) {
        window.SGIInterclasse.getActiveInterclasse().then(ativo => {
            if (ativo) {
                window.location.href = `./categorias.php?id=${ativo.id_interclasse}&modo=view`;
                return;
            }
            document.getElementById('listaCategoriasMobile').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('listaCategoriasDesktop').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
        });
    } else {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                document.getElementById('nomeInterclasseCategoria').innerText = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);

        ['btnVoltarCatMobile', 'btnVoltarCatDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
    }

    async function carregarCategorias() {
        const divMobile = document.getElementById('listaCategoriasMobile');
        const divDesktop = document.getElementById('listaCategoriasDesktop');

        try {
            const respostas = await Promise.allSettled([
                fetch(`../../../../api/categorias.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../../api/turmas.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../../api/equipes.php`).then(r => r.json()),
                fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../../api/jogos.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../../api/partidas.php`).then(r => r.json()),
            ]);

            const extrair = (res, padrao) => (res.status === 'fulfilled' && Array.isArray(res.value)) ? res.value : padrao;

            const categorias = extrair(respostas[0], []);
            const listaTurmas = extrair(respostas[1], []);
            const listaEquipes = extrair(respostas[2], []);
            const listaModalidades = extrair(respostas[3], []);
            const listaJogos = extrair(respostas[4], []);
            const listaPartidas = extrair(respostas[5], []);

            const turmaParaCategoria = {};
            listaTurmas.forEach(t => { turmaParaCategoria[t.id_turma] = Number(t.categorias_id_categoria); });

            const modalidadeParaCategoria = {};
            listaModalidades.forEach(m => { modalidadeParaCategoria[m.id_modalidade] = Number(m.categorias_id_categoria); });

            const jogoParaModalidade = {};
            listaJogos.forEach(j => { jogoParaModalidade[j.id_jogo] = Number(j.modalidades_id_modalidade); });

            const qtdEquipes = {};
            listaEquipes.forEach(e => {
                const catId = turmaParaCategoria[e.turmas_id_turma];
                if (catId) qtdEquipes[catId] = (qtdEquipes[catId] || 0) + 1;
            });

            const qtdPartidas = {};
            listaPartidas.forEach(p => {
                const modId = jogoParaModalidade[p.id_jogo];
                if (modId) {
                    const catId = modalidadeParaCategoria[modId];
                    if (catId) qtdPartidas[catId] = (qtdPartidas[catId] || 0) + 1;
                }
            });

            divMobile.innerHTML = '';
            divDesktop.innerHTML = '';

            if (!categorias.length) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma categoria cadastrada ainda.</p>';
                divMobile.innerHTML = msgVazia;
                divDesktop.innerHTML = msgVazia;
                return;
            }

            categorias.forEach((categoria) => {
                const cId = Number(categoria.id_categoria);
                const eq = qtdEquipes[cId] || 0;
                const pt = qtdPartidas[cId] || 0;

                divMobile.innerHTML += `
                    <a href="./turmas.php?id=${idInterclasse}&id_categoria=${cId}" class="categoria-item text-decoration-none text-dark bg-white d-flex m-auto justify-content-between align-items-center shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                        <i class="bi bi-trophy fs-3"></i>
                        <h2 class="m-0 fs-5 text-truncate px-3 w-100 text-start">${categoria.nome_categoria}</h2>
                        <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
                    </a>
                `;

                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="categoria-item card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                        <div class="fs-5 text-dark">${eq}</div>
                                    </div>
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                        <div class="fs-5 text-dark">${pt}</div>
                                    </div>
                                </div>
                                <a class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;" href="./turmas.php?id=${idInterclasse}&id_categoria=${cId}">
                                    VER MAIS <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });

            document.querySelectorAll('.categoria-item').forEach((btn) => {
                btn.addEventListener('click', (ev) => {
                    if (ev.target.closest('a[href]')) return;
                    ev.preventDefault();
                    selecionarCategoria(btn.dataset.id, btn);
                });
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            divMobile.innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar categorias.</p>';
            divDesktop.innerHTML = '<p class="text-danger mt-4">Erro ao carregar categorias.</p>';
        }
    }

    if (idInterclasse) {
        window.addEventListener('load', carregarCategorias);
    }
</script>

<?php
require_once '../../componentes/footer.php';
?>
