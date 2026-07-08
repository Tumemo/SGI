<?php
$tituloPagina = 'SGI - Pontuações';
$titulo = 'Pontuações';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'pontuacoes';
?>

<style>
    .section-title {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1f2937;
    }

    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }

    .pontuacao-card {
        transition: .2s ease;
        min-height: 180px;
    }

    .pontuacao-card:hover {
        transform: translateY(-4px);
    }

    .pontuacao-numero {
        font-size: 3rem;
        font-weight: 700;
        color: #111827;
    }

    .btn-pontos {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #6b7280;
    }

    .btn-pontos:hover {
        color: #dc2626;
    }
</style>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container-fluid px-3 py-4">
        <a href="./dashboard.php" id="btnVoltarPontuacaoMob"
           class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
            <i class="bi bi-arrow-left-circle"></i>
            <span id="nomeInterclassePontuacaoMob">Interclasse</span>
        </a>

        <h4 class="fw-bold d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-award fs-4 text-dark"></i> Pontuações
        </h4>

        <?php if ($nivelUsuario === 0): ?>
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-danger fw-bold px-4" id="btnSalvarPontuacao" onclick="salvarPontuacao()">
                <i class="bi bi-check-lg me-1"></i> Salvar
            </button>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-5" id="cardsContainerMob"></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout py-4">
    <div class="container-fluid" style="max-width: 92%;">
        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarPontuacao"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

            <h2 class="section-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-award"></i> Pontuações
            </h2>

            <p class="text-muted mb-0">Visualização das pontuações configuradas para as modalidades.</p>
        </div>

        <?php if ($nivelUsuario === 0): ?>
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-danger fw-bold px-4" id="btnSalvarPontuacaoDesktop" onclick="salvarPontuacao()">
                <i class="bi bi-check-lg me-1"></i> Salvar
            </button>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-5" id="cardsContainerDesk"></div>
    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const nivelUsuario = <?= $nivelUsuario ?>;

    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let atual = parseInt(elemento.innerText);
        if (isNaN(atual)) atual = 0;
        if (atual + valor >= 0) {
            elemento.innerText = atual + valor;
        }
    }

    function montarCard(tituloCard, icone, cor, valor, id) {
        if (nivelUsuario === 0) {
            return `
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                         style="border-bottom: 6px solid ${cor};">
                        <div class="d-flex justify-content-between align-items-center">
                            <i class="bi ${icone} fs-4" style="color:${cor};"></i>
                        </div>
                        <div class="text-center">
                            <small class="text-muted fw-semibold">${tituloCard}</small>
                            <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                                <button class="btn-pontos" onclick="alterarPontos('${id}', -1)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="pontuacao-numero" id="${id}">${valor}</span>
                                <button class="btn-pontos" onclick="alterarPontos('${id}', 1)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid ${cor};">
                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi ${icone} fs-4" style="color:${cor};"></i>
                    </div>
                    <div class="text-center">
                        <small class="text-muted fw-semibold">${tituloCard}</small>
                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <span class="pontuacao-numero" id="${id}">${valor}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async function carregar() {
        const mob = document.getElementById('cardsContainerMob');
        const desk = document.getElementById('cardsContainerDesk');

        if (!idInterclasse) {
            try {
                const ativo = await window.SGIInterclasse.getActiveInterclasse();
                idInterclasse = ativo?.id_interclasse || null;
            } catch (_) {}
        }

        if (!idInterclasse) {
            const msg = '<p class="text-muted text-center w-100 mt-4">Nenhum interclasse ativo encontrado.</p>';
            if (mob) mob.innerHTML = msg;
            if (desk) desk.innerHTML = msg;
            return;
        }

        try {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);

            if (dados?.nome_interclasse) {
                const n1 = document.getElementById('nomeInterclassePontuacao');
                const n2 = document.getElementById('nomeInterclassePontuacaoMob');
                if (n1) n1.textContent = dados.nome_interclasse;
                if (n2) n2.textContent = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }

            const p1 = dados?.ponto_1_lugar ?? '--';
            const p2 = dados?.ponto_2_lugar ?? '--';
            const p3 = dados?.ponto_3_lugar ?? '--';
            const arr = dados?.valor_item_arrecadacao ?? '--';

            const cards = [
                montarCard('PONTOS', 'bi-trophy', '#e2b714', p1, 'pontos-1'),
                montarCard('PONTOS', 'bi-award', '#9ca3af', p2, 'pontos-2'),
                montarCard('PONTOS', 'bi-award-fill', '#b87333', p3, 'pontos-3'),
                montarCard('MULTIPLICADOR', 'bi-heart-fill', '#dc2626', arr, 'pontos-arr')
            ];

            const html = cards.join('');
            if (mob) mob.innerHTML = html;
            if (desk) desk.innerHTML = html;
        } catch (error) {
            console.error('Erro ao carregar pontuações:', error);
            const errMsg = '<p class="text-danger mt-4 text-center w-100">Erro ao carregar pontuações.</p>';
            if (mob) mob.innerHTML = errMsg;
            if (desk) desk.innerHTML = errMsg;
        }
    }

    window.salvarPontuacao = async function() {
        const btn = document.getElementById('btnSalvarPontuacao');
        const btnDesktop = document.getElementById('btnSalvarPontuacaoDesktop');
        const pontos1 = parseInt(document.getElementById('pontos-1').innerText);
        const pontos2 = parseInt(document.getElementById('pontos-2').innerText);
        const pontos3 = parseInt(document.getElementById('pontos-3').innerText);
        const pontosArr = parseInt(document.getElementById('pontos-arr').innerText);

        try {
            if (btn) { btn.disabled = true; btn.innerHTML = 'Salvando...'; }
            if (btnDesktop) { btnDesktop.disabled = true; btnDesktop.innerHTML = 'Salvando...'; }

            const formData = new FormData();
            formData.append('ponto_1_lugar', pontos1);
            formData.append('ponto_2_lugar', pontos2);
            formData.append('ponto_3_lugar', pontos3);
            formData.append('valor_item_arrecadacao', pontosArr);

            const resp = await fetch('../../../api/interclasse.php?id=' + idInterclasse, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao salvar.');

            const salvo = '<i class="bi bi-check-lg me-1"></i> Salvo!';
            if (btn) btn.innerHTML = salvo;
            if (btnDesktop) btnDesktop.innerHTML = salvo;
            setTimeout(() => {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar'; }
                if (btnDesktop) { btnDesktop.disabled = false; btnDesktop.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar'; }
            }, 2000);
        } catch (err) {
            alert(err.message);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar'; }
            if (btnDesktop) { btnDesktop.disabled = false; btnDesktop.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar'; }
        }
    };

    window.addEventListener('pageshow', carregar);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
