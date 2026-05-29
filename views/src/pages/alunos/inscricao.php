<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Inscrição Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .btn-confirmar {
            background-color: #2b2b2b !important;
            color: #fff !important;
            border: none;
            border-radius: 6px;
            padding: 12px 48px;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            transition: background-color 0.2s;
        }
        .btn-confirmar:disabled {
            background-color: #6c757d !important;
            opacity: 0.6;
        }
        .modalidade {
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent !important;
            border-radius: 8px !important;
            color: #000000 !important;
            background-color: #ffffff !important;
        }
        /* Apenas adiciona borda vermelha ao selecionar */
        .modalidade.ativa {
            border: 2px solid #dc3545 !important;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.15) !important;
        }
        .nav-bottom-icon {
            font-size: 2.2rem;
        }
    </style>
</head>
<body class="bg-light" style="background-color: #f8f9fa !important;">
    
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100" style="object-fit: cover; height: 180px;">
        <h2 class="position-absolute top-50 translate-middle text-white start-50 m-0 fw-bold" style="letter-spacing: 0.5px;">Inscrição</h2>
        <a href="./home.php" class="bi bi-arrow-left position-absolute text-white fs-3 text-decoration-none" style="top: 20px; left: 20px; z-index: 10;"></a>
    </header>

    <form id="formInscricao" method="POST" action="processar_inscricao.php">
        <main class="m-auto pb-5" style="width: 90%; margin-bottom: 160px;">
            <h3 class="text-secondary fs-6 text-center my-4" style="color: #9e9e9e !important;">Escolha até 3 modalidades</h3>
            
            <section id="listaModalidades" class="d-flex flex-column gap-3 mx-auto" style="max-width: 400px;">
                <p class="text-center text-muted">(Carregando...)</p>
            </section>
            
            <div class="text-center mt-5 mb-5">
                <button type="submit" class="btn btn-confirmar" id="btnConfirmar" disabled>Confirmar</button>
            </div>
        </main>
    </form>

    <div class="modal fade" id="modalSucesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4 text-center border-0 shadow">
                <h2 class="fs-4 mb-3">Inscrição realizada com sucesso!</h2>
                <a href="./home.php" class="text-decoration-none">
                    <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center m-auto mt-2" style="width: 60px; height: 60px;">
                        <i class="bi bi-check-lg text-white fs-1"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <nav class="fixed-bottom py-3 bg-danger" style="border-top-left-radius: 25px; border-top-right-radius: 25px; z-index: 1030;">
        <ul class="nav justify-content-around align-items-center m-0 p-0">
            <li><a href="./home.php" class="text-white opacity-75 nav-bottom-icon"><i class="bi bi-house-door"></i></a></li>
            <li><a href="./inscricao.php" class="text-white nav-bottom-icon"><i class="bi bi-people-fill"></i></a></li>
            <li><a href="./ranking.php" class="text-white opacity-75 nav-bottom-icon"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./login.php" class="text-white opacity-75 nav-bottom-icon"><i class="bi bi-arrow-right"></i></a></li>
        </ul>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        const maxSelecoes = 3;
        const btnConfirmar = document.getElementById("btnConfirmar");
        const formInscricao = document.getElementById("formInscricao");
        const modal = new bootstrap.Modal(document.getElementById("modalSucesso"));
        const listaModalidades = document.getElementById('listaModalidades');
        const urlParams = new URLSearchParams(window.location.search);
        let idInterclasse = urlParams.get('id');

        function obterIconeExato(nome) {
            const n = nome.toLowerCase().trim();
            if (n === 'basquete' || n === 'vôlei' || n === 'volei') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-basketball me-3 ms-3" viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1m0 1a6 6 0 0 1 5.373 3.31A6.7 6.7 0 0 0 10 5c-.772 0-1.514.125-2.207.354A6.7 6.7 0 0 0 5 4a6.7 6.7 0 0 0-3.373.917A6 6 0 0 1 8 2M1.4 6.273A5.7 5.7 0 0 1 4.354 5.35c.427.528.774 1.12 1.026 1.754A7.4 7.4 0 0 0 2.16 8.784zm.8 3.444A6.7 6.7 0 0 1 5 8.5c.772 0 1.514-.125 2.207-.354A6.7 6.7 0 0 1 10 9a6.7 6.7 0 0 1 3.373-.917A6 6 0 0 1 2.2 9.717zm12.4-.444a5.7 5.7 0 0 1-2.954.923c-.427-.528-.774-1.12-1.026-1.754a7.4 7.4 0 0 0 3.22-1.724zM8 14a6 6 0 0 1-5.373-3.31A6.7 6.7 0 0 0 6 11c.772 0 1.514-.125 2.207-.354A6.7 6.7 0 0 0 11 11a6.7 6.7 0 0 0 3.373-.917A6 6 0 0 1 8 14m5.373-4.69a7.4 7.4 0 0 0-3.22 1.724c-.252-.634-.599-1.226-1.026-1.754 1.01-.222 1.964-.535 2.854-.923zm-7.906-.86a6.4 6.4 0 0 1-1.047-1.79 6.4 6.4 0 0 1 1.047-1.79 7.4 7.4 0 0 0 3.064 3.58 7.4 7.4 0 0 0-3.064 0M8 7.354c-.693-.23-1.435-.354-2.207-.354A6.7 6.7 0 0 0 2.42 7.917a6 6 0 0 1 .018-1.834A6.7 6.7 0 0 1 5.746 5.16c.306.516.55 1.087.717 1.698A7.4 7.4 0 0 0 8 7.354m0 1.292a7.4 7.4 0 0 0 1.537-.496c.166-.61.41-1.182.716-1.698a6.7 6.7 0 0 1 3.327.923 6 6 0 0 1 .018 1.834 6.7 6.7 0 0 1-3.39-.909c-.772 0-1.514.125-2.207.354Z"/></svg>';
            }
            if (n === 'futsal') {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-soccer-ball me-3 ms-3" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M11.5 5.5l-1.5-1.5v-2l1.5 1.5zm-7 0l1.5-1.5v-2L4.5 3.5zm7 5l-1.5 1.5v2l1.5-1.5zm-7 5l1.5 1.5v-2l-1.5-1.5zM8 4.5L6.5 6l1.5 1.5L9.5 6z"/></svg>';
            }
            return '<i class="bi bi-trophy fs-3 me-3 ms-3"></i>';
        }

        function bindSelecao() {
            const botoes = document.querySelectorAll(".modalidade");
            botoes.forEach((botao) => {
                botao.addEventListener("click", () => {
                    const checkbox = botao.querySelector("input[type='checkbox']");
                    const selecionadas = document.querySelectorAll(".modalidade.ativa").length;
                    
                    if (!botao.classList.contains("ativa") && selecionadas >= maxSelecoes) return;

                    // Alterna classe visual
                    botao.classList.toggle("ativa");
                    
                    checkbox.checked = botao.classList.contains("ativa");
                    
                    btnConfirmar.disabled = document.querySelectorAll(".modalidade.ativa").length === 0;
                });
            });
        }

        formInscricao.addEventListener("submit", (e) => {
            e.preventDefault(); 
            modal.show();

        });

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
                <button type="button" class="modalidade btn bg-white shadow-sm d-flex align-items-center py-3 px-4 text-start w-100">
                    <input type="checkbox" name="modalidades[]" value="${mod.id_modalidade}" class="d-none">
                    ${obterIconeExato(mod.nome_modalidade)}
                    <span class="fs-5 fw-medium" style="letter-spacing: 0.3px;">${mod.nome_modalidade}</span>
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