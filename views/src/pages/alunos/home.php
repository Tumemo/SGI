<?php
$tituloPagina = 'SGI - Aluno Home';
$cssExtra = '
        .style-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .style-card:hover, .style-card:focus-within { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
';
include 'componentes/head.php';
$mostrarSino = true;
$mostrarVoltar = false;
include 'componentes/header.php';
?>

    <main class="container py-4">
        <h1 class="visually-hidden">Painel do Aluno - Interclasses</h1>
        <h2 class="text-secondary fs-6 text-center mb-4 fw-normal">Inscreva-se ou visualize resultados</h2>
        
        <section class="row g-3" id="listaInterclassesAluno" aria-live="polite">
            <div class="col-12 text-center text-muted py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                Carregando competições...
            </div>
        </section>
    </main>

<?php
$paginaAtiva = 'home';
include 'componentes/nav.php';
?>

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
            const href = ativo ? `./inscricao.php?id=${interclasse.id_interclasse}` : `./ranking.php?id=${interclasse.id_interclasse}`;
            
            return `
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="${href}" class="text-decoration-none text-dark card-link d-block h-100">
                        <div class="shadow-sm d-flex justify-content-between align-items-center px-4 py-3 rounded ${classeCard} h-100 style-card">
                            <div>
                                <h3 class="fs-5 mb-1 text-dark fw-semibold">${nomeSanitizado}</h3>
                                <p class="m-0 text-secondary small">
                                    <span class="badge ${ativo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'} me-1">${status}</span> 
                                    - ${ano}
                                </p>
                            </div>
                            <img src="../../../public/icons/arrow-right.svg" alt="" aria-hidden="true" width="24" height="24">
                        </div>
                    </a>
                </div>
            `;
        }

        async function carregarInterclassesAluno() {
            const container = document.getElementById('listaInterclassesAluno');
            
            try {
                const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                
                if (!res.ok) throw new Error('Resposta do servidor não amigável.');
                
                const lista = await res.json();
                
                if (!Array.isArray(lista) || lista.length === 0) {
                    container.innerHTML = `
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                            Nenhum interclasse encontrado no momento.
                        </div>`;
                    return;
                }
                
                container.innerHTML = lista.map((item) => {
                    const isAtivo = item && String(item.status_interclasse) === '1';
                    return cardInterclasse(item, isAtivo);
                }).join('');
                
            } catch (error) {
                console.error('Erro ao buscar dados:', error);
                container.innerHTML = `
                    <div class="col-12 text-center text-danger py-5">
                        <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-2"></i>
                        Erro ao carregar interclasses. Por favor, tente novamente mais tarde.
                    </div>
                `;
            }
        }
        document.addEventListener('DOMContentLoaded', carregarInterclassesAluno);
    </script>
</body>
</html>