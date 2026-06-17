<?php
$tituloPagina = 'SGI - Colaborador - Pontuações';
$titulo = 'Pontuações';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$paginaAtiva = 'pontuacoes';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <section id="listaPontuacoesMobile" class="d-flex flex-column align-items-center w-100 mt-4">
        <p class="text-muted small">(Carregando pontuações...)</p>
    </section>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div style="border-radius: 12px;">
        <div class="mb-5">
            <a class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;" href="./dashboard.php">
                <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
            </a>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0 fs-5">
                <i class="bi bi-award fs-4 text-dark"></i> Pontuações
            </h4>
        </div>

        <div class="table-responsive" id="listaPontuacoesDesktop">
            <p class="text-muted">(Carregando pontuações...)</p>
        </div>
    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = null;

    async function carregarPontuacoes() {
        const divMobile = document.getElementById('listaPontuacoesMobile');
        const divDesktop = document.getElementById('listaPontuacoesDesktop');

        try {
            const response = await axios.get('../../../../api/pontuacao.php', {
                params: { id_interclasse: idInterclasse }
            });
            let dados = response.data;
            if (!Array.isArray(dados)) {
                if (dados?.data && Array.isArray(dados.data)) {
                    dados = dados.data;
                } else {
                    dados = [];
                }
            }

            if (divMobile) divMobile.innerHTML = '';
            if (divDesktop) divDesktop.innerHTML = '';

            if (!dados || dados.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma pontuação encontrada.</p>';
                if (divMobile) divMobile.innerHTML = msgVazia;
                if (divDesktop) divDesktop.innerHTML = msgVazia;
                return;
            }

            if (divMobile) {
                dados.forEach((item) => {
                    const pontos1 = item.pontos_1 || item.p1 || 0;
                    const pontos2 = item.pontos_2 || item.p2 || 0;
                    const pontos3 = item.pontos_3 || item.p3 || 0;
                    const total = item.total_pontos || item.total || (pontos1 + pontos2 + pontos3);

                    divMobile.innerHTML +=
                        '<div class="bg-white w-100 shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="max-width: 92%;">' +
                            '<div class="d-flex align-items-center gap-2 mb-2">' +
                                '<i class="bi bi-award fs-5 text-danger"></i>' +
                                '<h3 class="m-0 fs-6 fw-bold text-truncate">' + esc(item.nome_modalidade || '---') + '</h3>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                                '<span class="text-muted small">Turma:</span>' +
                                '<span class="fw-medium small">' + esc(item.nome_turma || '---') + '</span>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                                '<span class="text-muted small">1º lugar:</span>' +
                                '<span class="fw-bold text-success small">' + pontos1 + ' pts</span>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                                '<span class="text-muted small">2º lugar:</span>' +
                                '<span class="fw-bold text-primary small">' + pontos2 + ' pts</span>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center mb-1">' +
                                '<span class="text-muted small">3º lugar:</span>' +
                                '<span class="fw-bold text-warning small">' + pontos3 + ' pts</span>' +
                            '</div>' +
                            '<hr class="my-1">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<span class="fw-bold small">Total:</span>' +
                                '<span class="fw-bold fs-6">' + total + ' pts</span>' +
                            '</div>' +
                        '</div>';
                });
            }

            if (divDesktop) {
                let html = '<table class="table table-striped table-hover align-middle mb-0">' +
                    '<thead class="table-dark">' +
                        '<tr>' +
                            '<th class="py-3">Modalidade</th>' +
                            '<th class="py-3">Turma / Equipe</th>' +
                            '<th class="py-3 text-center">1º</th>' +
                            '<th class="py-3 text-center">2º</th>' +
                            '<th class="py-3 text-center">3º</th>' +
                            '<th class="py-3 text-center">Total</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>';

                dados.forEach((item) => {
                    const pontos1 = item.pontos_1 || item.p1 || 0;
                    const pontos2 = item.pontos_2 || item.p2 || 0;
                    const pontos3 = item.pontos_3 || item.p3 || 0;
                    const total = item.total_pontos || item.total || (pontos1 + pontos2 + pontos3);

                    html +=
                        '<tr>' +
                            '<td class="py-3">' + esc(item.nome_modalidade || '---') + '</td>' +
                            '<td class="py-3">' + esc(item.nome_turma || '---') + '</td>' +
                            '<td class="py-3 text-center fw-bold text-success">' + pontos1 + '</td>' +
                            '<td class="py-3 text-center fw-bold text-primary">' + pontos2 + '</td>' +
                            '<td class="py-3 text-center fw-bold text-warning">' + pontos3 + '</td>' +
                            '<td class="py-3 text-center fw-bold fs-5">' + total + '</td>' +
                        '</tr>';
                });

                html += '</tbody></table>';
                divDesktop.innerHTML = html;
            }
        } catch (error) {
            console.error('Erro ao carregar pontuações:', error);
            const errMsg = '<p class="text-danger mt-4 text-center w-100">Erro ao carregar pontuações.</p>';
            if (divMobile) divMobile.innerHTML = errMsg;
            if (divDesktop) divDesktop.innerHTML = errMsg;
        }
    }

    window.addEventListener('load', async () => {
        idInterclasse = await window.SGIInterclasse.resolveId();
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo encontrado.');
            window.location.href = 'home.php';
            return;
        }
        await carregarPontuacoes();
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
