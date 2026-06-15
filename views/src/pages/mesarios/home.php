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
    .style-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .style-card:hover, .style-card:focus-within { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<!-- mobile -->
<main class="d-md-none container py-4">
    <h1 class="visually-hidden">Painel do Mesário - Interclasses</h1>
    <h2 class="text-secondary fs-6 text-center mb-4 fw-normal">Gerencie os jogos das competições</h2>

    <section class="row g-3" id="listaInterclassesMesarioMobile" aria-live="polite">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando competições...
        </div>
    </section>
</main>

<!-- desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <h1 class="fs-2 mb-1">Mesário</h1>
    <p class="text-muted mb-4">Gerencie os jogos das competições</p>

    <section class="row g-3" id="listaInterclassesMesarioDesktop" aria-live="polite">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando competições...
        </div>
    </section>
</main>

<script>
    function escaparHTML(string) {
        const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' };
        return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
    }

    function cardInterclasse(interclasse, ativo) {
        const nomeSanitizado = escaparHTML(interclasse.nome_interclasse);
        const ano = interclasse.ano_interclasse ? escaparHTML(String(interclasse.ano_interclasse).split('-')[0]) : 'N/A';
        const status = ativo ? 'Em andamento' : 'Inativo';
        const classeCard = ativo ? 'bg-white' : 'bg-secondary-subtle opacity-75';

        if (!ativo) {
            return `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="shadow-sm d-flex justify-content-between align-items-center px-4 py-3 rounded ${classeCard} h-100 style-card" style="cursor: default;">
                        <div>
                            <h3 class="fs-5 mb-1 text-dark fw-semibold">${nomeSanitizado}</h3>
                            <p class="m-0 text-secondary small">
                                <span class="badge bg-secondary-subtle text-secondary me-1">${status}</span>
                                - ${ano}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./dashboard.php?id=${interclasse.id_interclasse}" class="text-decoration-none text-dark card-link d-block h-100">
                    <div class="shadow-sm d-flex justify-content-between align-items-center px-4 py-3 rounded ${classeCard} h-100 style-card">
                        <div>
                            <h3 class="fs-5 mb-1 text-dark fw-semibold">${nomeSanitizado}</h3>
                            <p class="m-0 text-secondary small">
                                <span class="badge bg-success-subtle text-success me-1">${status}</span>
                                - ${ano}
                            </p>
                        </div>
                        <img src="../../../public/icons/arrow-right.svg" alt="" aria-hidden="true" width="24" height="24">
                    </div>
                </a>
            </div>
        `;
    }

    async function carregarInterclasses() {
        const mobile = document.getElementById('listaInterclassesMesarioMobile');
        const desktop = document.getElementById('listaInterclassesMesarioDesktop');

        try {
            const res = await fetch('../../../../api/interclasse.php?regulamento=true');
            if (!res.ok) throw new Error('Resposta do servidor não amigável.');
            const lista = await res.json();

            if (!Array.isArray(lista) || lista.length === 0) {
                const msg = `<div class="col-12 text-center text-muted py-5">
                    <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                    Nenhum interclasse encontrado no momento.
                </div>`;
                mobile.innerHTML = msg;
                desktop.innerHTML = msg;
                return;
            }

            const html = lista.map((item) => {
                const isAtivo = item && String(item.status_interclasse) === '1';
                return cardInterclasse(item, isAtivo);
            }).join('');

            mobile.innerHTML = html;
            desktop.innerHTML = html;
        } catch (error) {
            console.error('Erro ao buscar dados:', error);
            const msg = `<div class="col-12 text-center text-danger py-5">
                <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-2"></i>
                Erro ao carregar interclasses. Por favor, tente novamente mais tarde.
            </div>`;
            mobile.innerHTML = msg;
            desktop.innerHTML = msg;
        }
    }

    document.addEventListener('DOMContentLoaded', carregarInterclasses);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
