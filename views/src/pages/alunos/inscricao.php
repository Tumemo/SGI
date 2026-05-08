<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Inscrição Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100">
        <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0">Inscrição</h2>
        <a href="./home.php" class="bi bi-arrow-left position-absolute z-3 text-white fs-3 text-decoration-none" style="top: 5%; left: 5%;"></a>
    </header>
    <main class="m-auto" style="width: 90%; margin-bottom: 120px;">
        <h3 class="text-secondary fs-6 text-center my-4">Escolha até 3 modalidades</h3>
        <section id="listaModalidades" class="d-flex flex-column gap-3">
            <p class="text-center text-muted">(Carregando...)</p>
        </section>
        <div class="text-center mt-5">
            <button class="btn btn-dark px-5" id="btnConfirmar" disabled>Confirmar</button>
        </div>
    </main>

    <div class="modal fade" id="modalSucesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4 text-center">
                <h2 class="fs-4">Inscrição realizada com sucesso!</h2>
                <a href="./home.php" class="text-decoration-none">
                    <div class="bg-danger rounded-circle px-3 py-1 m-auto mt-2" style="width: max-content;">
                        <i class="bi bi-check text-white fs-2"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <nav class="fixed-bottom py-2 bg-danger">
        <ul class="nav justify-content-around fs-1">
            <li><a href="./home.php" class="text-white"><i class="bi bi-house"></i></a></li>
            <li><a href="./inscricao.php" class="text-white"><i class="bi bi-people"></i></a></li>
            <li><a href="./ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./login.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
        </ul>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        const maxSelecoes = 3;
        const btnConfirmar = document.getElementById("btnConfirmar");
        const modal = new bootstrap.Modal(document.getElementById("modalSucesso"));
        const listaModalidades = document.getElementById('listaModalidades');
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');

        function bindSelecao() {
            const botoes = document.querySelectorAll(".modalidade");
            botoes.forEach((botao) => {
                botao.addEventListener("click", () => {
                    const selecionadas = document.querySelectorAll(".modalidade.ativa").length;
                    if (!botao.classList.contains("ativa") && selecionadas >= maxSelecoes) return;

                    botao.classList.toggle("ativa");
                    botao.classList.toggle("bg-danger");
                    botao.classList.toggle("text-white");
                    btnConfirmar.disabled = document.querySelectorAll(".modalidade.ativa").length === 0;
                });
            });
        }

        btnConfirmar.addEventListener("click", () => modal.show());

        async function carregarModalidades() {
            if (!idInterclasse) {
                const ativo = await fetch('../../../../api/interclasse.php?regulamento=true').then(r => r.json());
                const edicaoAtiva = (ativo || []).find((i) => String(i.status_interclasse) === '1');
                idInterclasse = edicaoAtiva?.id_interclasse || null;
            }
            if (!idInterclasse) {
                listaModalidades.innerHTML = '<p class="text-center text-muted">Nenhum interclasse ativo no momento.</p>';
                btnConfirmar.disabled = true;
                return;
            }
            const dadosInterclasse = await fetch(`../../../../api/interclasse.php?id_interclasse=${idInterclasse}&regulamento=true`).then(r => r.json());
            const atual = Array.isArray(dadosInterclasse) ? dadosInterclasse[0] : null;
            if (!atual || String(atual.status_interclasse) !== '1') {
                listaModalidades.innerHTML = '<p class="text-center text-muted">Inscrição fechada: interclasse inativo.</p>';
                btnConfirmar.disabled = true;
                return;
            }
            const modalidades = await fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`).then(r => r.json());
            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                listaModalidades.innerHTML = '<p class="text-center text-muted">Sem modalidades abertas.</p>';
                return;
            }
            listaModalidades.innerHTML = modalidades.map((mod) => `
                <button type="button" class="modalidade btn bg-white shadow-sm d-flex gap-3 align-items-center py-3 px-4 text-start">
                    <i class="bi bi-trophy fs-3 ms-4"></i>
                    <span class="fs-4">${mod.nome_modalidade}</span>
                </button>
            `).join('');
            bindSelecao();
        }

        carregarModalidades().catch((e) => {
            console.error(e);
            listaModalidades.innerHTML = '<p class="text-center text-danger">Erro ao carregar modalidades.</p>';
            btnConfirmar.disabled = true;
        });
    </script>
</body>
</html>
