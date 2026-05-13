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


<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <a href="./edicao_modalidades.php" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none" style="border-radius: 6px;" id="btnVoltarPontuacao">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>

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
            <a href="./edicao_modalidades.php" class="text-decoration-none" id="btnVoltarPontuacaoRodape">
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

    // --- Lógica do Mobile ---

    // 1. Pegar o ID do Interclasse que veio pela URL da página de modalidades
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';

    // Transfere o ID para o botão voltar do desktop (opcional, para não perder o fluxo)
    if(idInterclasse){
        if (modo === 'view') {
            const parent = document.getElementById('btnContinuarDesktop')?.parentElement;
            if (parent) parent.classList.add('d-none');
            document.getElementById('btnContinuarMobile')?.classList.add('d-none');
            document.getElementById('btnVoltarPontuacao').href = `./dashboard.php?id=${idInterclasse}`;
            document.getElementById('btnVoltarPontuacao').classList.add('d-none');
            document.getElementById('btnVoltarPontuacaoRodape')?.classList.add('d-none');
        } else {
            document.getElementById('btnContinuarDesktop').href = `./edicao_resumo.php?id=${idInterclasse}`;
            document.getElementById('btnVoltarPontuacao').href = `./edicao_modalidades.php?id=${idInterclasse}`;
        }
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                document.getElementById('nomeInterclassePontuacao').innerText = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);
    }

    function salvarPontuacaoLocal() {
        if (!idInterclasse) return;
        const dados = {
            p1: Number(document.getElementById('pontos-1')?.innerText || document.getElementById('pontos1Mobile')?.value || 10),
            p2: Number(document.getElementById('pontos-2')?.innerText || document.getElementById('pontos2Mobile')?.value || 8),
            p3: Number(document.getElementById('pontos-3')?.innerText || document.getElementById('pontos3Mobile')?.value || 5),
            arr: Number(document.getElementById('pontos-arr')?.innerText || document.getElementById('multiplicadorMobile')?.value || 2),
            pb: Number(document.getElementById('penalidadeBrigasMobile')?.value || 0),
            pa: Number(document.getElementById('penalidadeArbitragemMobile')?.value || 0),
            pe: Number(document.getElementById('penalidadeExtraMobile')?.value || 0)
        };
        localStorage.setItem(`sgi_pontuacao_${idInterclasse}`, JSON.stringify(dados));
    }

    function carregarPontuacaoLocal() {
        if (!idInterclasse) return;
        const salvo = localStorage.getItem(`sgi_pontuacao_${idInterclasse}`);
        if (!salvo) return;
        const dados = JSON.parse(salvo);
        if (document.getElementById('pontos-1')) document.getElementById('pontos-1').innerText = dados.p1 ?? 10;
        if (document.getElementById('pontos-2')) document.getElementById('pontos-2').innerText = dados.p2 ?? 8;
        if (document.getElementById('pontos-3')) document.getElementById('pontos-3').innerText = dados.p3 ?? 5;
        if (document.getElementById('pontos-arr')) document.getElementById('pontos-arr').innerText = dados.arr ?? 2;
        if (document.getElementById('pontos1Mobile')) document.getElementById('pontos1Mobile').value = dados.p1 ?? 10;
        if (document.getElementById('pontos2Mobile')) document.getElementById('pontos2Mobile').value = dados.p2 ?? 8;
        if (document.getElementById('pontos3Mobile')) document.getElementById('pontos3Mobile').value = dados.p3 ?? 5;
        if (document.getElementById('multiplicadorMobile')) document.getElementById('multiplicadorMobile').value = dados.arr ?? 2;
        if (document.getElementById('penalidadeBrigasMobile')) document.getElementById('penalidadeBrigasMobile').value = dados.pb ?? 0;
        if (document.getElementById('penalidadeArbitragemMobile')) document.getElementById('penalidadeArbitragemMobile').value = dados.pa ?? 0;
        if (document.getElementById('penalidadeExtraMobile')) document.getElementById('penalidadeExtraMobile').value = dados.pe ?? 0;
    }

    // 2. Interceptar o clique do botão "Continuar" do Mobile
    document.getElementById('btnContinuarMobile').addEventListener('click', async (e) => {
        e.preventDefault();

        if (!idInterclasse) {
            alert("ID do interclasse não encontrado na URL! Volte e crie o interclasse novamente.");
            return;
        }

        // Captura dos valores mantida para futura integração
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

        // Sem integração de API aqui por enquanto (conforme fluxo atual do projeto)
        console.info("Dados de pontuação prontos para envio futuro:", dadosRegulamento);
        salvarPontuacaoLocal();
        window.location.href = modo === 'view'
            ? `./dashboard.php?id=${idInterclasse}`
            : `./edicao_resumo.php?id=${idInterclasse}`;
    });

    document.getElementById('btnContinuarDesktop')?.addEventListener('click', salvarPontuacaoLocal);
    carregarPontuacaoLocal();
</script>

<?php
require_once '../componentes/footer.php';
?>