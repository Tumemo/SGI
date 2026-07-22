<?php
$tituloPagina = 'SGI - Aluno Home';
$cssExtra = '
    .style-card { 
        transition: all 0.25s ease-in-out; 
        border: 1px solid rgba(0,0,0,0.08);
    }
    .style-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 0.5rem 1.25rem rgba(0,0,0,0.1) !important; 
        border-color: #dc3545;
    }
';

include 'componentes/head.php';

// Navegação Lateral/Inferior
$paginaAtiva = 'home';
include 'componentes/nav.php';

// Banner Superior
$mostrarSino = true;
$mostrarVoltar = false;
$titulo = 'Interclasses';
include 'componentes/header.php';
?>

<main class="container py-4 flex-grow-1">
    <h1 class="visually-hidden">Painel do Aluno - Interclasses</h1>
    
    <!-- Subtítulo -->
    <div class="text-center mb-4">
        <h2 class="text-secondary fs-5 fw-normal">Inscreva-se ou visualize resultados das competições</h2>
        <hr class="w-25 mx-auto border-danger opacity-50 my-3">
    </div>

    <!-- Lista de Interclasses -->
    <section class="row g-3 g-md-4 justify-content-center" id="listaInterclassesAluno" aria-live="polite">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm text-danger me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando competições...
        </div>
    </section>
</main>

<!-- Modal do Termo de Responsabilidade -->
<div class="modal fade" id="modalTermo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-text me-2"></i>Termo de Responsabilidade
                </h5>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted">Declaro para os devidos fins que aceito e assumo inteira responsabilidade pelos termos abaixo descritos para participação no Interclasse:</p>
                
                <ol class="ps-3 text-secondary lh-lg mb-3">
                    <li class="mb-2"><strong>Conduta:</strong> Comprometo-me a agir com respeito, <em>fair play</em> e espírito esportivo durante todas as atividades.</li>
                    <li class="mb-2"><strong>Regras:</strong> Declaro estar ciente e de acordo com todas as regras oficiais do Interclasse, acatando as decisões da organização e arbitragem.</li>
                    <li class="mb-2"><strong>Materiais:</strong> Responsabilizo-me pelos materiais esportivos e uniformes que me forem confiados, respondendo por eventuais danos ou extravios.</li>
                    <li class="mb-2"><strong>Saúde:</strong> Declaro estar em condições físicas adequadas para a prática das modalidades escolhidas, isentando a organização de responsabilidade por acidentes ou lesões decorrentes da participação.</li>
                    <li class="mb-2"><strong>Imagem:</strong> Autorizo o uso de minha imagem e voz para fins de divulgação do evento nas mídias oficiais da instituição.</li>
                    <li class="mb-2"><strong>Pontuação:</strong> Aceito o sistema de pontuação e classificação estabelecido, bem como as penalidades previstas no regulamento.</li>
                </ol>

                <div id="avisoRecusa" class="alert alert-danger d-none m-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Não é possível continuar sem aceitar os termos.
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-end gap-2 bg-light px-4 py-3">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="btnRecusarTermo">Recusar</button>
                <button type="button" class="btn btn-danger px-4 fw-semibold" id="btnAceitarTermo">Aceitar e Continuar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function escaparHTML(string) {
        const mapa = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;'
        };
        return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
    }

    function cardInterclasse(interclasse, ativo) {
        const nomeSanitizado = escaparHTML(interclasse.nome_interclasse);
        const ano = interclasse.ano_interclasse ? escaparHTML(String(interclasse.ano_interclasse).split('-')[0]) : 'N/A';

        const status = ativo ? 'Em andamento' : 'Inativo';
        const classeCard = ativo ? 'bg-white' : 'bg-white opacity-75';
        const href = ativo ? `./modalidade.php?id=${interclasse.id_interclasse}` : `./ranking.php?id=${interclasse.id_interclasse}`;

        return `
            <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                <a href="${href}" class="text-decoration-none text-dark card-link d-block h-100">
                    <div class="shadow-sm d-flex justify-content-between align-items-center p-4 rounded-3 ${classeCard} h-100 style-card">
                        <div>
                            <h3 class="fs-5 mb-2 text-dark fw-bold">${nomeSanitizado}</h3>
                            <p class="m-0 text-secondary small d-flex align-items-center gap-2">
                                <span class="badge ${ativo ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'}">${status}</span> 
                                <span class="fw-semibold text-muted">• ${ano}</span>
                            </p>
                        </div>
                        <i class="bi bi-chevron-right text-danger fs-4 ms-3"></i>
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
                        <i class="bi bi-folder-x fs-1 d-block text-secondary mb-2"></i>
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

    async function initModalTermo() {
        const modalElement = document.getElementById('modalTermo');
        const modalTermo = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: false });

        const btnAceitar = document.getElementById('btnAceitarTermo');
        const btnRecusar = document.getElementById('btnRecusarTermo');
        const avisoRecusa = document.getElementById('avisoRecusa');

        try {
            const checagem = await fetch('../../../../api/concordarTermos.php', { method: 'GET' });
            if (checagem.status === 401) return;

            const resCheck = await checagem.json();
            if (resCheck.success && resCheck.termo_aceito === true) return;
        } catch (e) {
            console.error("Erro ao verificar status dos termos:", e);
        }

        modalTermo.show();

        btnAceitar.addEventListener('click', async function() {
            btnAceitar.disabled = true;
            btnRecusar.disabled = true;
            btnAceitar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';

            try {
                const res = await fetch('../../../../api/concordarTermos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (res.status === 401) {
                    window.location.href = '../../../..';
                    return;
                }

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
                btnRecusar.disabled = false;
                btnAceitar.textContent = 'Aceitar e Continuar';
            }
        });

        btnRecusar.addEventListener('click', function() {
            avisoRecusa.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Acesso bloqueado:</strong> É necessário aceitar os termos de responsabilidade para participar e utilizar o painel.
            `;
            avisoRecusa.classList.remove('d-none');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarInterclassesAluno();
        initModalTermo();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>