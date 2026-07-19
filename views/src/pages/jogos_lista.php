<?php
$tituloPagina = 'SGI - Mesário - Jogos';
$titulo = 'Jogos';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>



<main class="container py-4 main-desktop-layout">
    <h1 class="fs-2 mb-1" id="listaTitulo">Jogos</h1>
    <p class="text-muted mb-4" id="listaSubtitulo">Carregando...</p>

    <div class="row g-3" id="listaJogos">
        <div class="col-12 text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            Carregando jogos...
        </div>
    </div>
</main>

<script>
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');

    async function carregarDados() {
        if (!idInterclasse) {
            document.getElementById('listaJogos').innerHTML = '<div class="col-12 text-center text-danger py-5">Nenhum interclasse informado.</div>';
            return;
        }

        try {
            const [dadosInter, resJogos] = await Promise.all([
                window.SGIInterclasse.getInterclasseById(idInterclasse),
                fetch(`../../../api/jogos.php?x=1&id_interclasse=${idInterclasse}`)
            ]);

            if (dadosInter) {
                document.getElementById('listaTitulo').textContent = `Jogos - ${dadosInter.nome_interclasse}`;
                document.getElementById('listaSubtitulo').textContent = 'Selecione um jogo para gerenciar o placar';
                window.SGIInterclasse.updatePageTitle(dadosInter.nome_interclasse);
            }

            const jogos = await resJogos.json();
            const lista = Array.isArray(jogos) ? jogos : [];
            const container = document.getElementById('listaJogos');

            if (lista.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nenhum jogo encontrado para este interclasse.</div>';
                return;
            }

            container.innerHTML = lista.map(j => {
                const status = j.status_jogo || 'Agendado';
                const statusClass = status === 'Concluido' ? 'bg-success text-white' :
                                    status === 'Iniciado' || status === 'Pausado' ? 'bg-warning text-dark' :
                                    'bg-secondary text-white';
                const data = j.data_jogo ? new Date(j.data_jogo + 'T' + (j.inicio_jogo || '00:00')).toLocaleDateString('pt-BR') : '---';
                const horario = j.inicio_jogo ? j.inicio_jogo.slice(0, 5) : '---';
                const equipes = j.equipes_nomes || '---';
                return `
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="./jogos.php?id_jogo=${j.id_jogo}" class="text-decoration-none text-dark">
                            <div class="card shadow-sm jogo-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fw-bold">${j.nome_modalidade || '---'}</span>
                                    <span class="status-badge ${statusClass}">${status}</span>
                                </div>
                                <p class="mb-1 small text-muted">${equipes}</p>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><i class="bi bi-calendar3 me-1"></i>${data}</span>
                                    <span><i class="bi bi-clock me-1"></i>${horario}</span>
                                </div>
                                ${j.nome_local ? `<p class="mt-1 mb-0 small text-muted"><i class="bi bi-geo-alt me-1"></i>${j.nome_local}</p>` : ''}
                            </div>
                        </a>
                    </div>
                `;
            }).join('');

        } catch (e) {
            console.error(e);
            document.getElementById('listaJogos').innerHTML = '<div class="col-12 text-center text-danger py-5">Erro ao carregar jogos.</div>';
        }
    }

    document.addEventListener('DOMContentLoaded', carregarDados);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
