<?php
$titulo = "Regulamentos";
$textTop = "Regulamentos";
$btnVoltar = true;
$modoView = isset($_GET['modo']) && $_GET['modo'] === 'view';
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
                <input type="number" id="pontos2Mobile" class="w-25 rounded border border-none shadow-sm text-center" value='7'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                3º lugar
                <input type="number" id="pontos3Mobile" class="w-25 rounded border border-none shadow-sm text-center" value='5'>
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
                    <input type="number" id="multiplicadorMobile" class="w-25 rounded border border-none shadow-sm text-center" value='2'>
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
        <?php if ($modoView): ?>
        <button id="btnSalvarMobile" class="btn btn-danger">Salvar</button>
        <?php endif; ?>
        <button id="btnContinuarMobile" class="btn btn-danger">Continuar</button>
    </section>
</main>


<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0" style="max-width: 80%;">

        <div class="mb-5">
            <a href="#" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none" style="border-radius: 6px;" id="btnVoltarPontuacao" data-sgi-header-back="true">
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
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-1', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-1">10</span>
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-1', 1)"><i class="bi bi-chevron-right"></i></button>
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
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-2', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-2">8</span>
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-2', 1)"><i class="bi bi-chevron-right"></i></button>
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
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-3', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-3">5</span>
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-3', 1)"><i class="bi bi-chevron-right"></i></button>
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
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-arr', -1)"><i class="bi bi-chevron-left"></i></button>
                            <span class="fs-2 fw-bold text-dark" id="pontos-arr">2</span>
                            <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" onclick="alterarPontos('pontos-arr', 1)"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none d-md-block fixed-bottom" style="background: linear-gradient(to top, #f8f9fa 70%, rgba(248, 249, 250, 0) 100%); padding: 30px 0;">
        <div class="container-fluid d-flex justify-content-end align-items-center gap-3" style="max-width: 80%;">
            <?php if ($modoView): ?>
            <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm" id="btnSalvarDesktop">
                Salvar
            </button>
            <?php endif; ?>
            <a href="#" class="text-decoration-none <?= $modoView ? 'd-none' : '' ?>" id="btnVoltarPontuacaoRodape" data-sgi-header-back="true">
                <button type="button" class="btn btn-outline-danger bg-white fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Voltar
                </button>
            </a>
            <a href="./edicao_resumo.php" class="text-decoration-none" id="btnContinuarDesktop">
                <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm">
                    Continuar
                </button>
            </a>
        </div>
    </div>
</main>

<script>
    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);
        let pontosAtuais = parseInt(elemento.innerText, 10);
        if (pontosAtuais + valor >= 0) {
            elemento.innerText = pontosAtuais + valor;
        }
    }

    function lerValoresPontuacao() {
        return {
            p1: Number(document.getElementById('pontos-1')?.innerText || document.getElementById('pontos1Mobile')?.value || 10),
            p2: Number(document.getElementById('pontos-2')?.innerText || document.getElementById('pontos2Mobile')?.value || 7),
            p3: Number(document.getElementById('pontos-3')?.innerText || document.getElementById('pontos3Mobile')?.value || 5),
            arr: Number(document.getElementById('pontos-arr')?.innerText || document.getElementById('multiplicadorMobile')?.value || 2)
        };
    }

    function aplicarValoresNaTela(valores) {
        if (document.getElementById('pontos-1')) document.getElementById('pontos-1').innerText = valores.p1;
        if (document.getElementById('pontos-2')) document.getElementById('pontos-2').innerText = valores.p2;
        if (document.getElementById('pontos-3')) document.getElementById('pontos-3').innerText = valores.p3;
        if (document.getElementById('pontos-arr')) document.getElementById('pontos-arr').innerText = valores.arr;
        if (document.getElementById('pontos1Mobile')) document.getElementById('pontos1Mobile').value = valores.p1;
        if (document.getElementById('pontos2Mobile')) document.getElementById('pontos2Mobile').value = valores.p2;
        if (document.getElementById('pontos3Mobile')) document.getElementById('pontos3Mobile').value = valores.p3;
        if (document.getElementById('multiplicadorMobile')) document.getElementById('multiplicadorMobile').value = valores.arr;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';

    if (idInterclasse) {
        if (modo === 'view') {
            document.getElementById('btnContinuarDesktop')?.classList.add('d-none');
            document.getElementById('btnContinuarMobile')?.classList.add('d-none');
        } else {
            document.getElementById('btnContinuarDesktop').href = `./edicao_resumo.php?id=${idInterclasse}`;
        }
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                document.getElementById('nomeInterclassePontuacao').innerText = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);
    }

    async function carregarPontuacao() {
        if (!idInterclasse) return;
        try {
            const resp = await fetch(`../../../api/interclasse.php?id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const raw = await resp.text();
            let dados;
            try {
                dados = JSON.parse(raw);
            } catch {
                console.error('Resposta inválida ao carregar pontuação:', raw);
                return;
            }
            const interclasse = Array.isArray(dados) ? dados[0] : dados;
            if (!interclasse) return;

            aplicarValoresNaTela({
                p1: interclasse.ponto_1_lugar ?? 10,
                p2: interclasse.ponto_2_lugar ?? 7,
                p3: interclasse.ponto_3_lugar ?? 5,
                arr: interclasse.valor_item_arrecadacao ?? 2
            });
        } catch (e) {
            console.error('Erro ao carregar pontuação:', e);
        }
    }

    async function salvarPontuacao() {
        if (!idInterclasse) {
            alert('ID do interclasse não encontrado na URL!');
            return false;
        }

        const valores = lerValoresPontuacao();
        const formData = new FormData();
        formData.append('ponto_1_lugar', valores.p1);
        formData.append('ponto_2_lugar', valores.p2);
        formData.append('ponto_3_lugar', valores.p3);
        formData.append('valor_item_arrecadacao', valores.arr);

        try {
            const resp = await fetch(`../../../api/interclasse.php?id=${encodeURIComponent(idInterclasse)}`, {
                method: 'POST',
                body: formData
            });
            const raw = await resp.text();
            let result;
            try {
                result = JSON.parse(raw);
            } catch {
                console.error('Resposta inválida ao salvar pontuação:', raw);
                alert('Erro no servidor ao salvar. Verifique o console (F12).');
                return false;
            }

            if (result.success) {
                window.SGIInterclasse?.invalidateCache?.();
                return true;
            }
            alert('Erro ao salvar: ' + (result.message || 'Erro desconhecido'));
            return false;
        } catch (e) {
            console.error('Erro ao salvar pontuação:', e);
            alert('Erro ao salvar pontuação.');
            return false;
        }
    }

    if (window.SGIInterclasse?.registerBeforeLeave) {
        window.SGIInterclasse.registerBeforeLeave(async () => {
            await salvarPontuacao();
        });
    }

    document.getElementById('btnContinuarMobile')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnContinuarMobile');
        btn.innerHTML = 'Salvando...';
        btn.disabled = true;
        const salvo = await salvarPontuacao();
        if (salvo) {
            window.location.href = modo === 'view'
                ? `./dashboard.php?id=${idInterclasse}`
                : `./edicao_resumo.php?id=${idInterclasse}`;
        } else {
            btn.innerHTML = 'Continuar';
            btn.disabled = false;
        }
    });

    document.getElementById('btnContinuarDesktop')?.addEventListener('click', async (e) => {
        e.preventDefault();
        const salvo = await salvarPontuacao();
        if (salvo) {
            window.location.href = `./edicao_resumo.php?id=${idInterclasse}`;
        }
    });

    function feedbackSalvar(mobile) {
        const btn = mobile
            ? document.getElementById('btnSalvarMobile')
            : document.getElementById('btnSalvarDesktop');
        if (!btn) return;
        const original = btn.innerText;
        btn.innerText = 'Salvo!';
        btn.disabled = true;
        setTimeout(() => {
            btn.innerText = original;
            btn.disabled = false;
        }, 2000);
    }

    document.getElementById('btnSalvarMobile')?.addEventListener('click', async () => {
        const salvo = await salvarPontuacao();
        if (salvo) feedbackSalvar(true);
    });

    document.getElementById('btnSalvarDesktop')?.addEventListener('click', async () => {
        const salvo = await salvarPontuacao();
        if (salvo) feedbackSalvar(false);
    });

    document.addEventListener('DOMContentLoaded', () => {
        carregarPontuacao();
        window.SGIInterclasse.setupBackLinks(`./dashboard.php?id=${idInterclasse || ''}`);
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
