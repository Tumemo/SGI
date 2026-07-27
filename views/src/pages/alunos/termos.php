<?php
$tituloPagina = 'SGI - Termos';
$cssExtra = '
        .termo-clausula { border-left: 4px solid #dc3545; padding-left: 1rem; margin-bottom: 1rem; }
        .regulamento-card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
';
include 'componentes/head.php';
$titulo = 'Termos';
$mostrarVoltar = true;
$urlVoltar = './home.php';
?>

    <main class="container py-4">
        <h1 class="visually-hidden">Termos e Regulamento</h1>

        <!-- Termo de Responsabilidade -->
        <section class="mb-5">
            <h2 class="fs-5 fw-bold mb-3">Termo de Responsabilidade</h2>
            <div class="bg-white rounded-3 p-4 shadow-sm">
                <p class="text-secondary mb-3">Declaro para os devidos fins que aceito e assumo inteira responsabilidade pelos termos abaixo descritos para participação no Interclasse:</p>
                <div class="termo-clausula"><strong>Conduta:</strong> Comprometo-me a agir com respeito, <em>fair play</em> e espírito esportivo durante todas as atividades.</div>
                <div class="termo-clausula"><strong>Regras:</strong> Declaro estar ciente e de acordo com todas as regras oficiais do Interclasse, acatando as decisões da organização e arbitragem.</div>
                <div class="termo-clausula"><strong>Materiais:</strong> Responsabilizo-me pelos materiais esportivos e uniformes que me forem confiados, respondendo por eventuais danos ou extravios.</div>
                <div class="termo-clausula"><strong>Saúde:</strong> Declaro estar em condições físicas adequadas para a prática das modalidades escolhidas, isentando a organização de responsabilidade por acidentes ou lesões decorrentes da participação.</div>
                <div class="termo-clausula"><strong>Imagem:</strong> Autorizo o uso de minha imagem e voz para fins de divulgação do evento nas mídias oficiais da instituição.</div>
                <div class="termo-clausula"><strong>Pontuação:</strong> Aceito o sistema de pontuação e classificação estabelecido, bem como as penalidades previstas no regulamento.</div>
            </div>
        </section>

        <!-- Regulamento do Interclasse -->
        <section>
            <h2 class="fs-5 fw-bold mb-3">Regulamento do Interclasse</h2>
            <div class="regulamento-card">
                <p id="statusRegulamento" class="text-muted mb-0">
                    <span class="spinner-border spinner-border-sm me-2 text-danger" role="status"></span>Carregando regulamento...
                </p>
                
                <!-- Bloco no mesmo padrão visual do Modal -->
                <div id="containerPdfRegulamento" class="card border-danger-subtle bg-danger-subtle bg-opacity-10 d-none">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf-fill fs-3 text-danger"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Regulamento Oficial (PDF)</h6>
                                <small class="text-muted">Clique para ler as regras completas da competição.</small>
                            </div>
                        </div>
                        <a id="btnBaixarPdf" href="#" target="_blank" class="btn btn-danger btn-sm rounded-3 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-download"></i> Baixar / Ler PDF
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
$paginaAtiva = 'termos';
include 'componentes/nav.php';
?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        async function carregarRegulamento() {
            const statusEl = document.getElementById('statusRegulamento');
            const containerPdf = document.getElementById('containerPdfRegulamento');
            const btnPdf = document.getElementById('btnBaixarPdf');

            try {
                // Busca a lista de interclasses com regulamento atrelado
                const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                if (!res.ok) throw new Error('Erro na resposta da API');

                const data = await res.json();
                
                // Trata retorno caso venha um array ou objeto único
                const lista = Array.isArray(data) ? data : [data];
                
                // Busca o interclasse ativo (status_interclasse === '1' ou 1)
                const ativo = lista.find(i => String(i.status_interclasse) === '1') || lista[0];

                if (ativo && ativo.regulamento_interclasse && ativo.regulamento_interclasse.trim() !== '') {
                    btnPdf.href = '../../../../uploads/regulamentos/' + ativo.regulamento_interclasse;
                    
                    statusEl.classList.add('d-none');
                    containerPdf.classList.remove('d-none');
                } else {
                    statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                    statusEl.className = 'text-muted mb-0';
                }
            } catch (error) {
                console.error("Erro ao carregar regulamento:", error);
                statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
                statusEl.className = 'text-danger mb-0';
            }
        }

        document.addEventListener('DOMContentLoaded', carregarRegulamento);
    </script>
</body>
</html>