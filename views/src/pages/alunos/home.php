<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Aluno Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <header class="position-relative">
        <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100">
        <a href="./notificacoes.php" class="position-absolute text-white fs-3" style="top: 8%; right: 6%;">
            <i class="bi bi-bell"></i>
        </a>
    </header>
    <main class="container py-4" style="margin-bottom: 100px;">
        <h3 class="text-secondary fs-6 text-center mb-4">Inscreva-se ou visualize resultados</h3>
        <section class="row g-3" id="listaInterclassesAluno">
            <p class="text-center text-muted">(Carregando...)</p>
        </section>
    </main>
    <nav class="fixed-bottom py-2 bg-danger">
        <ul class="nav justify-content-around fs-1">
            <li><a href="./home.php" class="text-white"><i class="bi bi-house"></i></a></li>
            <li><a href="./inscricao.php" class="text-white"><i class="bi bi-people"></i></a></li>
            <li><a href="./ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
            <li><a href="./login.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
        </ul>
    </nav>
    <script>
        function cardInterclasse(interclasse, ativo) {
            const ano = interclasse.ano_interclasse ? String(interclasse.ano_interclasse).split('-')[0] : 'N/A';
            const status = ativo ? 'Em andamento' : 'Inativo';
            const classe = ativo ? 'bg-white' : 'bg-secondary-subtle opacity-75';
            const href = ativo ? `./inscricao.php?id=${interclasse.id_interclasse}` : `./ranking.php?id=${interclasse.id_interclasse}`;
            return `
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="${href}" class="text-decoration-none text-dark">
                        <div class="shadow-sm d-flex justify-content-between px-4 py-3 rounded ${classe}">
                            <section>
                                <h2 class="fs-5 mb-0">${interclasse.nome_interclasse}</h2>
                                <p class="m-0 text-secondary">(${status}) - ${ano}</p>
                            </section>
                            <img src="../../../public/icons/arrow-right.svg" alt="Seta">
                        </div>
                    </a>
                </div>
            `;
        }

        async function carregarInterclassesAluno() {
            const container = document.getElementById('listaInterclassesAluno');
            try {
                const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                const lista = await res.json();
                if (!Array.isArray(lista) || lista.length === 0) {
                    container.innerHTML = '<p class="text-center text-muted">Nenhum interclasse encontrado.</p>';
                    return;
                }
                container.innerHTML = lista.map((item) => cardInterclasse(item, String(item.status_interclasse) === '1')).join('');
            } catch (error) {
                console.error(error);
                container.innerHTML = '<p class="text-center text-danger">Erro ao carregar interclasses.</p>';
            }
        }

        window.addEventListener('load', carregarInterclassesAluno);
    </script>
</body>
</html>
