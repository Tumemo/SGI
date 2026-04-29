<?php
$titulo = "Regulamentos";
$textTop = "Regulamentos";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="mt-4 d-flex justify-content-center align-items-center flex-column gap-4 d-md-none" style="margin-bottom: 70px;">
    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Pontuação por colocação
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item justify-content-around d-flex align-items-center">
                1º lugar
                <input type="number" id="pontos1Mobile" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                2º lugar
                <input type="number" id="pontos2Mobile" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                3º lugar
                <input type="number" id="pontos3Mobile" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
        </ul>
    </div>

    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Peso da arrecadação solidária
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <div class="d-flex justify-content-between mt-2">
                    Multiplicador
                    <input type="number" id="multiplicadorMobile" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                </div>
                <p class="text-body-tertiary mt-2 mb-0" style="font-size: 14px;">A arrecadação será multiplicada por esse valor</p>
            </li>
        </ul>
    </div>

    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Penalidades
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">Brigas</span>
                <input type="number" id="penalidadeBrigasMobile" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">Desrespeitar arbitragem</span>
                <input type="number" id="penalidadeArbitragemMobile" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">3º lugar</span>
                <input type="number" id="penalidadeExtraMobile" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
        </ul>
    </div>
    <section class="d-flex gap-4">
        <button id="btnContinuarMobile" class="btn btn-danger">Continuar</button>
    </section>
</main>


<main class="bg-light flex-grow-1 p-4 p-md-5 d-none d-md-block" style="padding-top: 2rem; padding-bottom: 120px; position: relative;">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm" style="border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-trophy fs-5"></i> Pontuações
            </h5>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="bg-white border-0 shadow-sm rounded-3 p-4 position-relative d-flex flex-column h-100" style="border-bottom: 6px solid #e2b714 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <i class="bi bi-medal fs-4" style="color: #e2b714;"></i>
                        <span class="badge px-3 py-2 text-white" style="background-color: #e2b714; font-size: 0.75rem;">1º LUGAR</span>
                    </div>
                    <div class="text-center mt-auto">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">PONTOS ATRIBUÍDOS</small>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-1', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-1">10</span>
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-1', 1)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="bg-white border-0 shadow-sm rounded-3 p-4 position-relative d-flex flex-column h-100" style="border-bottom: 6px solid #a8a8a8 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <i class="bi bi-medal fs-4" style="color: #a8a8a8;"></i>
                        <span class="badge px-3 py-2 text-white" style="background-color: #a8a8a8; font-size: 0.75rem;">2º LUGAR</span>
                    </div>
                    <div class="text-center mt-auto">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">PONTOS ATRIBUÍDOS</small>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-2', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-2">8</span>
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-2', 1)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="bg-white border-0 shadow-sm rounded-3 p-4 position-relative d-flex flex-column h-100" style="border-bottom: 6px solid #b87333 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <i class="bi bi-medal fs-4" style="color: #b87333;"></i>
                        <span class="badge px-3 py-2 text-white" style="background-color: #b87333; font-size: 0.75rem;">3º LUGAR</span>
                    </div>
                    <div class="text-center mt-auto">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">PONTOS ATRIBUÍDOS</small>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-3', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-3">5</span>
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-3', 1)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="bg-white border-0 shadow-sm rounded-3 p-4 position-relative d-flex flex-column h-100" style="border-bottom: 6px solid var(--bs-danger) !important;">
                    <div class="d-flex justify-content-end align-items-center mb-4">
                        <span class="badge px-3 py-2 text-white bg-danger" style="font-size: 0.75rem;">ARRECADAÇÃO</span>
                    </div>
                    <div class="text-center mt-auto">
                        <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">PONTOS ATRIBUÍDOS</small>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-arr', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-arr">2</span>
                            <button class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-arr', 1)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, #f8f9fa 70%, rgba(248, 249, 250, 0) 100%); padding: 30px 0;">
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3" style="max-width: 80%;">
            <a href="./novaEdicao_modalidades.php" class="text-decoration-none">
                <button class="btn btn-outline-danger bg-white fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Voltar
                </button>
            </a>
            <a href="./edicao_resumo.php" class="text-decoration-none" id="btnContinuarDesktop">
                <button class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Continuar
                </button>
            </a>
        </div>
    </div>
</main>

<script>
    // --- Lógica do Desktop (Mantida) ---
    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let pontosAtuais = parseInt(elemento.innerText);

        if (pontosAtuais + valor >= 0) {
            elemento.innerText = pontosAtuais + valor;
        }
    }

    // --- NOVA INTEGRAÇÃO: Lógica do Mobile ---

    // 1. Pegar o ID do Interclasse que veio pela URL da página de modalidades
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');

    // Transfere o ID para o botão voltar do desktop (opcional, para não perder o fluxo)
    if(idInterclasse){
        document.getElementById('btnContinuarDesktop').href = `./edicao_resumo.php?id=${idInterclasse}`;
    }

    // 2. Interceptar o clique do botão "Continuar" do Mobile
    document.getElementById('btnContinuarMobile').addEventListener('click', async (e) => {
        e.preventDefault();

        if (!idInterclasse) {
            alert("ID do interclasse não encontrado na URL! Volte e crie o interclasse novamente.");
            return;
        }

        // 3. Capturar todos os valores digitados no mobile
        const dadosRegulamento = {
            id_interclasse: idInterclasse,
            pontos_1_lugar: document.getElementById('pontos1Mobile').value,
            pontos_2_lugar: document.getElementById('pontos2Mobile').value,
            pontos_3_lugar: document.getElementById('pontos3Mobile').value,
            multiplicador_arrecadacao: document.getElementById('multiplicadorMobile').value,
            penalidade_brigas: document.getElementById('penalidadeBrigasMobile').value,
            penalidade_arbitragem: document.getElementById('penalidadeArbitragemMobile').value,
            penalidade_extra: document.getElementById('penalidadeExtraMobile').value
        };

        const btnMobile = document.getElementById('btnContinuarMobile');
        btnMobile.innerHTML = "Salvando...";
        btnMobile.disabled = true;

        try {
            // ATENÇÃO: Se você ainda não tiver a API regulamentos.php criada, o fetch vai dar erro,
            // mas o bloco "catch" abaixo garante que você será redirecionado mesmo assim!
            const response = await fetch('../../../api/regulamentos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosRegulamento)
            });

            const result = await response.json();

            if (result.success) {
                // Deu certo! Manda pra página de resumo passando o ID do interclasse
                window.location.href = `./edicao_resumo.php?id=${idInterclasse}`;
            } else {
                alert("Erro ao salvar: " + (result.message || "Verifique a API."));
                btnMobile.innerHTML = "Continuar";
                btnMobile.disabled = false;
            }
        } catch (error) {
            console.error("Erro na requisição ou API não existe ainda:", error);
            
            // FALLBACK: Como não sei se a API já existe, forçamos o redirecionamento
            // para que você possa continuar testando o visual/fluxo da sua aplicação.
            window.location.href = `./edicao_resumo.php?id=${idInterclasse}`;
        }
    });
</script>

<?php
require_once '../componentes/footer.php';
?>