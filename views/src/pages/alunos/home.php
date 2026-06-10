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

    <div class="modal fade" id="modalTermo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold">Termo de Responsabilidade</h5>
                </div>
                <div class="modal-body">
                    <p>Declaro para os devidos fins que aceito e assumo inteira responsabilidade pelos termos abaixo descritos para participação no Interclasse:</p>
                    <ol class="ps-3">
                        <li class="mb-2"><strong>Conduta:</strong> Comprometo-me a agir com respeito, <em>fair play</em> e espírito esportivo durante todas as atividades.</li>
                        <li class="mb-2"><strong>Regras:</strong> Declaro estar ciente e de acordo com todas as regras oficiais do Interclasse, acatando as decisões da organização e arbitragem.</li>
                        <li class="mb-2"><strong>Materiais:</strong> Responsabilizo-me pelos materiais esportivos e uniformes que me forem confiados, respondendo por eventuais danos ou extravios.</li>
                        <li class="mb-2"><strong>Saúde:</strong> Declaro estar em condições físicas adequadas para a prática das modalidades escolhidas, isentando a organização de responsabilidade por acidentes ou lesões decorrentes da participação.</li>
                        <li class="mb-2"><strong>Imagem:</strong> Autorizo o uso de minha imagem e voz para fins de divulgação do evento nas mídias oficiais da instituição.</li>
                        <li class="mb-2"><strong>Pontuação:</strong> Aceito o sistema de pontuação e classificação estabelecido, bem como as penalidades previstas no regulamento.</li>
                    </ol>
                    <div id="avisoRecusa" class="alert alert-danger d-none" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Não é possível continuar sem aceitar os termos.
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-3 pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" id="btnRecusarTermo">Recusar</button>
                    <button type="button" class="btn btn-danger px-4" id="btnAceitarTermo">Aceitar</button>
                </div>
            </div>
        </div>
    </div>

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
        function initModalTermo() {
            const modalTermo = new bootstrap.Modal(document.getElementById('modalTermo'));
            const btnAceitar = document.getElementById('btnAceitarTermo');
            const btnRecusar = document.getElementById('btnRecusarTermo');
            const avisoRecusa = document.getElementById('avisoRecusa');

            modalTermo.show();

            btnAceitar.addEventListener('click', async function () {
                btnAceitar.disabled = true;
                btnAceitar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';

                try {
                    const res = await fetch('../../../../api/concordarTermos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    const data = await res.json();

                    if (data.success) {
                        avisoRecusa.classList.add('d-none');
                        modalTermo.hide();
                    } else {
                        avisoRecusa.textContent = data.message || 'Erro ao salvar aceite. Tente novamente.';
                        avisoRecusa.classList.remove('d-none');
                    }
                } catch (error) {
                    avisoRecusa.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
                    avisoRecusa.classList.remove('d-none');
                } finally {
                    btnAceitar.disabled = false;
                    btnAceitar.textContent = 'Aceitar';
                }
            });

            btnRecusar.addEventListener('click', function () {
                avisoRecusa.classList.remove('d-none');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            carregarInterclassesAluno();
            initModalTermo();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>