<?php
$titulo = "Pontuações";
$textTop = "Pontuações";
$btnVoltar = true;



require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    body {
        background: #f4f6f9;
    }

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

<main class="main-desktop-layout py-4">

    <div class="container-fluid" style="max-width: 92%;">

        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarPontuacao"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

            <h2 class="section-title d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-award"></i>
                Gerenciamento de Pontuações
            </h2>

            <p class="text-muted mb-0">
                Configure as pontuações das modalidades.
            </p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Pontuações</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-danger fw-bold px-4" id="btnSalvarPontuacao" onclick="salvarPontuacao()">
                    <i class="bi bi-check-lg me-1"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn btn-dark fw-bold px-4 d-none">
                    Continuar <i class="bi bi-arrow-right-circle ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #e2b714;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-trophy fs-4" style="color:#e2b714;"></i>

                        
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-1">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #9ca3af;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award fs-4" style="color:#9ca3af;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-2">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #b87333;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award-fill fs-4" style="color:#b87333;"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-3">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #dc2626;">

                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-heart-fill fs-4 text-danger"></i>
                    </div>

                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            MULTIPLICADOR
                        </small>

                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', -1)">
                                <i class="bi bi-dash"></i>
                            </button>

                            <span class="pontuacao-numero" id="pontos-arr">
                                --
                            </span>

                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>



    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';
    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let atual = parseInt(elemento.innerText);
        if (isNaN(atual)) atual = 0;
        if (atual + valor >= 0) {
            elemento.innerText = atual + valor;
        }
    }

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclassePontuacao').innerText = dados?.nome_interclasse || 'Interclasse';
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);

        const btnBack = document.getElementById('btnVoltarPontuacao');
        if (btnBack) {
            btnBack.href = `./dashboard.php?id=${idInterclasse}`;
        }

        if (dados) {
            document.getElementById('pontos-1').innerText = dados.ponto_1_lugar || 0;
            document.getElementById('pontos-2').innerText = dados.ponto_2_lugar || 0;
            document.getElementById('pontos-3').innerText = dados.ponto_3_lugar || 0;
            document.getElementById('pontos-arr').innerText = dados.valor_item_arrecadacao || 0;
        }
        return idInterclasse;
    }

    window.salvarPontuacao = async function() {
        const btn = document.getElementById('btnSalvarPontuacao');
        const pontos1 = parseInt(document.getElementById('pontos-1').innerText);
        const pontos2 = parseInt(document.getElementById('pontos-2').innerText);
        const pontos3 = parseInt(document.getElementById('pontos-3').innerText);
        const pontosArr = parseInt(document.getElementById('pontos-arr').innerText);

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const formData = new FormData();
            formData.append('ponto_1_lugar', pontos1);
            formData.append('ponto_2_lugar', pontos2);
            formData.append('ponto_3_lugar', pontos3);
            formData.append('valor_item_arrecadacao', pontosArr);

            const resp = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao salvar.');

            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvo!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
            }, 2000);
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
        }
    };

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        const btnContinuar = document.getElementById('btnContinuarPontuacao');
        if (btnContinuar) {
            btnContinuar.href = `./edicao_resumo.php?id=${idInterclasse}&modo=create`;
            if (modo === 'create') btnContinuar.classList.remove('d-none');
        }
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
