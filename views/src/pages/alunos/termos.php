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
include 'componentes/header.php';
?>

    <main class="container py-4">
        <h1 class="visually-hidden">Termos e Regulamento</h1>

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

        <section>
            <h2 class="fs-5 fw-bold mb-3">Regulamento do Interclasse</h2>
            <div class="regulamento-card">
                <p id="statusRegulamento" class="text-muted mb-0">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>Carregando...
                </p>
                <div id="linkRegulamento" class="d-none">
                    <p class="text-secondary mb-3">Clique no botão abaixo para visualizar ou baixar o regulamento completo do Interclasse.</p>
                    <a id="downloadRegulamento" href="#" target="_blank" class="btn btn-danger px-4">
                        <i class="bi bi-file-earmark-pdf-fill me-2"></i>Baixar Regulamento (PDF)
                    </a>
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
            const linkDiv = document.getElementById('linkRegulamento');
            const downloadLink = document.getElementById('downloadRegulamento');

            try {
                const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                const lista = await res.json();

                const ativo = (Array.isArray(lista) ? lista : []).find(i => String(i.status_interclasse) === '1');

                if (ativo && ativo.regulamento_interclasse) {
                    downloadLink.href = '../../../../uploads/regulamentos/' + ativo.regulamento_interclasse;
                    statusEl.classList.add('d-none');
                    linkDiv.classList.remove('d-none');
                } else {
                    statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                    statusEl.className = 'text-muted mb-0';
                }
            } catch (error) {
                statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
                statusEl.className = 'text-danger mb-0';
            }
        }

        document.addEventListener('DOMContentLoaded', carregarRegulamento);
    </script>
</body>
</html>
