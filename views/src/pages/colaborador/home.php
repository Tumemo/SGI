<?php
$tituloPagina = 'SGI - Colaborador - Home';
$titulo = 'Home';
$mostrarVoltar = false;
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="d-md-none p-3" style="padding-top: 1rem; padding-bottom: 5rem;">
    <h2 class="d-flex align-items-center gap-2 text-dark mb-4" style="font-weight: 400; font-size: 1.1rem;">
        <i class="bi bi-house-door-fill"></i> Início
    </h2>
    <div id="caixaListarMobile" class="pb-5 mb-2 mt-2">
        <p class="text-center text-muted mt-3">Carregando...</p>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <section class="mt-4 container" style="max-width: 800px;">
        <h1 class="fs-2">Interclasse Ativo</h1>
        <div id="caixaListarDesktop" class="mt-4">
            <p class="text-center text-muted mt-5">Carregando...</p>
        </div>
    </section>
</main>

<script>
    async function carregarInterclasseAtivo() {
        const mobile = document.getElementById('caixaListarMobile');
        const desktop = document.getElementById('caixaListarDesktop');

        try {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();

            if (!ativo) {
                const msg = '<p class="text-center text-muted mt-5">Nenhum interclasse ativo no momento.</p>';
                mobile.innerHTML = msg;
                desktop.innerHTML = msg;
                return;
            }

            const ano = ativo.ano_interclasse ? ativo.ano_interclasse.split('-')[0] : 'N/A';
            const statusBadge = '<span class="badge bg-danger">Ativo</span>';

            const htmlMobile = `
                <a href="./dashboard.php?id=${ativo.id_interclasse}" class="text-decoration-none text-dark">
                    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1" style="width: 90%;">
                        <div>
                            <h2 class="m-0 fs-4">${ativo.nome_interclasse}</h2>
                            <p class="text-secondary m-0">${ano}</p>
                            <span class="badge bg-danger mt-2">Ativo</span>
                        </div>
                        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                    </div>
                </a>
            `;

            const htmlDesktop = `
                <div class="row bg-white shadow rounded-3 py-4 fs-5 align-items-center px-4 border border-1"
                     style="cursor: pointer; transition: background-color 0.2s ease;"
                     onmouseover="this.style.backgroundColor='#f8f9fa'"
                     onmouseout="this.style.backgroundColor='#ffffff'"
                     onclick="window.location.href='./dashboard.php?id=${ativo.id_interclasse}'">
                    <div class="col-6 fw-semibold text-dark text-truncate">${ativo.nome_interclasse}</div>
                    <div class="col-3 text-center text-secondary">${ano}</div>
                    <div class="col-3 text-center">${statusBadge}</div>
                </div>
            `;

            mobile.innerHTML = htmlMobile;
            desktop.innerHTML = htmlDesktop;

        } catch (error) {
            console.error(error);
            const msgErro = '<p class="text-center text-danger mt-5">Erro ao carregar dados.</p>';
            mobile.innerHTML = msgErro;
            desktop.innerHTML = msgErro;
        }
    }

    window.addEventListener('load', carregarInterclasseAtivo);
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
