<?php
$tituloPagina = 'SGI - Mesário';
$titulo = 'Mesário';
$mostrarVoltar = false;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<style>
    .status-switch.form-check-input:checked {
        background-color: #ed1c24;
        border-color: #ed1c24;
    }
    .status-switch.form-check-input:focus {
        border-color: #ed1c24;
        box-shadow: 0 0 0 0.2rem rgba(237, 28, 36, 0.25);
    }
    .style-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .style-card:hover, .style-card:focus-within { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<!-- main mobile -->
<main class="d-md-none">
    <div id="caixaListar" class="pb-5 mb-2 mt-2">
        <p class="text-center text-muted mt-3">Redirecionando...</p>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-flex main-desktop-layout">
    <section class="mt-4 container">

        <h1 class="fs-2">Edições do interclasse</h1>

        <div>
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-5 fw-medium px-2">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <div class="mt-2" id="listaDesktop">
                 <p class="text-center text-muted mt-5">Redirecionando...</p>
            </div>
        </div>

    </section>
</main>

<script>
    function escaparHTML(string) {
        const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' };
        return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
    }

    function statusAtivo(item) {
        return String(item.status_interclasse) === '1';
    }

    function anoInterclasse(item) {
        return item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
    }

    async function listarInterclasses() {
        const listarMobile = document.getElementById('caixaListar');
        const listarDesktop = document.getElementById('listaDesktop');
        
        try {
            const res = await fetch('../../../../api/interclasse.php?regulamento=true');
            const data = await res.json();
            
            if (!Array.isArray(data) || data.length === 0) {
                const msgVazia = `<p class="text-center text-muted mt-5">Nenhum interclasse encontrado</p>`;
                listarMobile.innerHTML = msgVazia;
                listarDesktop.innerHTML = msgVazia;
                return;
            }

            const listaOrdenada = [...data].sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse));

            let htmlMobile = '';
            let htmlDesktop = '';

            listaOrdenada.forEach((item) => {
                const anoStr = anoInterclasse(item);
                const ativo = statusAtivo(item);
                const statusBadge = ativo
                    ? '<span class="bg-danger rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Ativo</span>'
                    : '<span class="bg-secondary rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Inativo</span>';

                const classeCard = ativo ? '' : 'opacity-75';

                htmlMobile += `
                    <a href="./dashboard.php?id=${item.id_interclasse}" class="text-decoration-none text-dark">
                        <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1 ${classeCard}" style="width: 90%;">
                            <div>
                                <h2 class="m-0 fs-4">${escaparHTML(item.nome_interclasse)}</h2>
                                <p class="text-secondary m-0">${anoStr}</p>
                            </div>
                            <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                        </div>
                    </a>
                `;

                htmlDesktop += `
                    <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-3 align-items-center px-2 border border-1 ${classeCard}" 
                         style="cursor: pointer; transition: background-color 0.2s ease;" 
                         onmouseover="this.style.backgroundColor='#f8f9fa'" 
                         onmouseout="this.style.backgroundColor='#ffffff'"
                         onclick="window.location.href='./dashboard.php?id=${item.id_interclasse}'">
                        
                        <div class="col-4 fw-semibold text-dark text-truncate">${escaparHTML(item.nome_interclasse)}</div>
                        <div class="col-4 text-center text-secondary">${anoStr}</div>
                        <div class="col-4 text-center">${statusBadge}</div>
                    </div>
                `;
            });

            listarMobile.innerHTML = htmlMobile;
            listarDesktop.innerHTML = htmlDesktop;

        } catch (error) {
            console.error(error);
            const msgErro = `<p class="mt-3 text-center text-danger">Erro ao carregar dados da API!</p>`;
            listarMobile.innerHTML = msgErro;
            listarDesktop.innerHTML = msgErro;
        }
    }

    async function redirecionarParaUltimoInterclasse() {
        try {
            const res = await fetch('../../../../api/interclasse.php?regulamento=true');
            const lista = await res.json();
            if (Array.isArray(lista) && lista.length > 0) {
                const ultimo = lista.sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse))[0];
                window.location.replace('./dashboard.php?id=' + ultimo.id_interclasse);
                return;
            }
        } catch (e) {
            console.error('Erro ao redirecionar para último interclasse:', e);
        }
        listarInterclasses();
    }

    document.addEventListener('DOMContentLoaded', redirecionarParaUltimoInterclasse);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
