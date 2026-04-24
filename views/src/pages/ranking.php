<?php
$titulo = "Ranking";
$textTop = "Ranking";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Estilos para manter o design igual à imagem do 4º lugar em diante */
    .btn-categoria {
        transition: all 0.2s;
    }
    .btn-categoria.ativo {
        background-color: #e5e7eb !important;
        border-color: #9ca3af !important;
        font-weight: bold;
        color: #000 !important;
    }
    .card-ranking-2 {
        border: 2px solid #0ea5e9 !important;
    }
    .barra-fundo { 
        background-color: #e5e7eb; 
        height: 6px; 
        border-radius: 4px; 
        overflow: hidden; 
        margin-top: 8px; 
    }
    .barra-progresso { 
        background-color: #ef4444; 
        height: 100%; 
    }
</style>

<main class="m-auto d-md-none" style="width: 90%; margin-bottom: 120px;">
    
    <div id="mensagemMobile" class="mt-4"></div>

    <section class="mt-3">
        <div id="botoesCategoriasMobile" class="d-flex flex-wrap gap-2">
            </div>
    </section>

    <section class="mt-4" id="listaRankingMobile">
        </section>
</main>

<main class="d-none d-md-block" style="margin-left: 80px; background-color: #f2f2f2;">
    <div class="container-fluid bg-white p-5 d-flex" style="min-height: calc(100vh - 220px);">

        <div class="ps-4 mb-5" style="min-width: 300px;">
            <div class="d-inline-flex align-items-center bg-danger text-white px-3 py-2 rounded-1 mb-3 shadow-sm" style="cursor: pointer; font-size: 0.9rem;" onclick="window.history.back()">
                <i class="bi bi-arrow-left-circle-fill me-2"></i>
                <span class="fw-bold" id="nomeInterclasseDesktop">Interclasse Ativo</span>
            </div>
            <h1 class="fw-bold text-dark mt-2">Ranking</h1>

            <div id="botoesCategoriasDesktop" class="d-flex flex-wrap gap-2 mt-4 mb-5">
                </div>
        </div>

        <div class="container" style="max-width: 900px;">
            <div id="mensagemDesktop"></div>
            <div id="listaRankingDesktop">
                </div>
        </div>
        
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    let dadosCategorias = [];

    async function carregarRankingAtivo() {
        const msgMobile = document.getElementById('mensagemMobile');
        const msgDesktop = document.getElementById('mensagemDesktop');

        msgMobile.innerHTML = '<p class="text-center text-muted">Carregando ranking...</p>';
        msgDesktop.innerHTML = '<p class="text-center text-muted">Carregando ranking...</p>';

        try {
            // Rota da sua API
            const res = await axios.get('../../../api/ranking.php');
            const data = res.data;

            msgMobile.innerHTML = '';
            msgDesktop.innerHTML = '';

            if (!data || data.em_andamento === false || !data.categorias || data.categorias.length === 0) {
                const alerta = `
                    <div class="alert alert-warning text-center mt-4 border-0 shadow-sm">
                        <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
                        Nenhum interclasse em andamento no momento.
                    </div>
                `;
                msgMobile.innerHTML = alerta;
                msgDesktop.innerHTML = alerta;
                
                // Esconde o nome do interclasse no desktop se não houver
                document.getElementById('nomeInterclasseDesktop').innerText = "Nenhum Ativo";
                return;
            }

            // Atualiza o nome do interclasse no botão do desktop
            if(data.nome_interclasse) {
                document.getElementById('nomeInterclasseDesktop').innerText = data.nome_interclasse;
            }

            dadosCategorias = data.categorias;
            renderizarCategorias();
            
            if (dadosCategorias.length > 0) {
                selecionarCategoria(dadosCategorias[0].id_categoria);
            }

        } catch (error) {
            console.error("Erro ao buscar ranking:", error);
            const alertaErro = `<p class="text-danger text-center mt-4 fw-bold">Erro ao carregar o ranking.</p>`;
            msgMobile.innerHTML = alertaErro;
            msgDesktop.innerHTML = alertaErro;
        }
    }

    function renderizarCategorias() {
        const divMobile = document.getElementById('botoesCategoriasMobile');
        const divDesktop = document.getElementById('botoesCategoriasDesktop');
        
        let htmlMobile = '';
        let htmlDesktop = '';

        dadosCategorias.forEach(cat => {
            // Botão Mobile (Mantendo o estilo do seu código original)
            htmlMobile += `
                <button 
                    id="btn-cat-mob-${cat.id_categoria}" 
                    class="btn-categoria rounded-2 fs-6 btn border border-3 py-1 px-2 shadow-sm" 
                    style="height: 32px;"
                    onclick="selecionarCategoria(${cat.id_categoria})"
                >
                    ${cat.nome_categoria}
                </button>
            `;

            // Botão Desktop (Mantendo o estilo do seu código original)
            htmlDesktop += `
                <button 
                    type="button"
                    id="btn-cat-desk-${cat.id_categoria}" 
                    class="btn-categoria btn btn-light border rounded-pill px-3 py-1 text-secondary shadow-sm" 
                    style="font-size: 0.8rem; background-color: #efefef;"
                    onclick="selecionarCategoria(${cat.id_categoria})"
                >
                    ${cat.nome_categoria}
                </button>
            `;
        });

        divMobile.innerHTML = htmlMobile;
        divDesktop.innerHTML = htmlDesktop;
    }

    function selecionarCategoria(idCategoria) {
        document.querySelectorAll('.btn-categoria').forEach(btn => btn.classList.remove('ativo'));
        
        const btnMob = document.getElementById(`btn-cat-mob-${idCategoria}`);
        const btnDesk = document.getElementById(`btn-cat-desk-${idCategoria}`);
        if(btnMob) btnMob.classList.add('ativo');
        if(btnDesk) btnDesk.classList.add('ativo');

        const categoriaAtual = dadosCategorias.find(c => c.id_categoria === idCategoria);
        if (!categoriaAtual) return;

        const turmas = categoriaAtual.turmas || [];
        turmas.sort((a, b) => b.pontos - a.pontos);
        renderizarTurmas(turmas);
    }

    function renderizarTurmas(turmas) {
        const divMobile = document.getElementById('listaRankingMobile');
        const divDesktop = document.getElementById('listaRankingDesktop');

        if (turmas.length === 0) {
            const msgVazia = `<p class="text-center text-muted my-4">Nenhuma sala pontuou nesta categoria.</p>`;
            divMobile.innerHTML = msgVazia;
            divDesktop.innerHTML = msgVazia;
            return;
        }

        let htmlMobile = '';
        let htmlDesktop = '';
        const maiorPontuacao = turmas[0].pontos > 0 ? turmas[0].pontos : 1;

        turmas.forEach((turma, index) => {
            const posicao = index + 1;
            const porcentagem = (turma.pontos / maiorPontuacao) * 100;

            // ================== RENDER MOBILE ==================
            if (posicao === 1) {
                htmlMobile += `
                <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-award fs-1" style="color: #a38c41;"></i>
                        <h2 class="m-0 fs-4">1º <span class="fs-6">${turma.nome}</span></h2>
                    </div>
                    <h2 class="m-0 fs-3">${turma.pontos} pts</h2>
                </div>`;
            } else if (posicao === 2) {
                htmlMobile += `
                <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow card-ranking-2" style="height: 60px;">
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-award fs-1" style="color: #8a8a8a;"></i>
                        <h2 class="m-0 fs-4">2º <span class="fs-6">${turma.nome}</span></h2>
                    </div>
                    <h2 class="m-0 fs-3">${turma.pontos} pts</h2>
                </div>`;
            } else if (posicao === 3) {
                htmlMobile += `
                <div class="d-flex justify-content-between align-items-center px-4 mb-3 border border-2 shadow" style="height: 60px;">
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-award fs-1" style="color: #be844f;"></i>
                        <h2 class="m-0 fs-4">3º <span class="fs-6">${turma.nome}</span></h2>
                    </div>
                    <h2 class="m-0 fs-3">${turma.pontos} pts</h2>
                </div>`;
            } else {
                if (posicao === 4) htmlMobile += `<hr class="border border-1 border-dark mb-3">`;
                htmlMobile += `
                <div class="px-4 py-2 mb-3 border border-2 shadow">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3 align-items-center">
                            <h2 class="m-0 fs-4 text-muted">${posicao}º <span class="fs-6 text-dark">${turma.nome}</span></h2>
                        </div>
                        <h2 class="m-0 fs-5">${turma.pontos} pts</h2>
                    </div>
                    <div class="barra-fundo w-100">
                        <div class="barra-progresso" style="width: ${porcentagem}%;"></div>
                    </div>
                </div>`;
            }

            // ================== RENDER DESKTOP ==================
            if (posicao === 1) {
                htmlDesktop += `
                <div class="card border shadow-sm mb-3 p-3">
                    <div class="d-flex align-items-center justify-content-between px-4">
                        <div class="d-flex align-items-center gap-5">
                            <i class="bi bi-award-fill text-warning fs-1"></i>
                            <span class="fs-2 fw-bold">1º</span>
                            <span class="fw-bold text-secondary ms-4">${turma.nome}</span>
                        </div>
                        <span class="fw-bold fs-5">${turma.pontos} pts</span>
                    </div>
                </div>`;
            } else if (posicao === 2) {
                htmlDesktop += `
                <div class="card border shadow-sm mb-3 p-3 card-ranking-2">
                    <div class="d-flex align-items-center justify-content-between px-4">
                        <div class="d-flex align-items-center gap-5">
                            <i class="bi bi-award-fill fs-1" style="color: #C0C0C0 !important;"></i>
                            <span class="fs-2 fw-bold">2º</span>
                            <span class="fw-bold text-secondary ms-4">${turma.nome}</span>
                        </div>
                        <span class="fw-bold fs-5">${turma.pontos} pts</span>
                    </div>
                </div>`;
            } else if (posicao === 3) {
                htmlDesktop += `
                <div class="card border shadow-sm mb-3 p-3">
                    <div class="d-flex align-items-center justify-content-between px-4">
                        <div class="d-flex align-items-center gap-5">
                            <i class="bi bi-award-fill fs-1" style="color: #CD7F32;"></i>
                            <span class="fs-2 fw-bold">3º</span>
                            <span class="fw-bold text-secondary ms-4">${turma.nome}</span>
                        </div>
                        <span class="fw-bold fs-5">${turma.pontos} pts</span>
                    </div>
                </div>`;
            } else {
                htmlDesktop += `
                <div class="card border shadow-sm mb-3 p-4">
                    <div class="d-flex align-items-center justify-content-between px-4 mb-2">
                        <div class="d-flex align-items-center gap-5">
                            <span class="fs-3 fw-bold text-muted" style="width: 45px; text-align: center;">${posicao}º</span>
                            <span class="fw-bold text-secondary ms-4">${turma.nome}</span>
                        </div>
                        <span class="fw-bold text-muted">${turma.pontos} pts</span>
                    </div>
                    <div class="px-4 w-100">
                        <div class="barra-fundo w-100">
                            <div class="barra-progresso" style="width: ${porcentagem}%;"></div>
                        </div>
                    </div>
                </div>`;
            }
        });

        divMobile.innerHTML = htmlMobile;
        divDesktop.innerHTML = htmlDesktop;
    }

    window.onload = carregarRankingAtivo;
</script>
<?php
require_once '../componentes/footer.php';
?>