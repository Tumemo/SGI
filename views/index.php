<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./src/styles/style.css">
    <title>SGI - Login</title>
    <style>
        #msg_erro_mobile,
        #msg_erro_desktop {
            font-size: 0.85rem;
            margin-top: 10px;
        }

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <main class="d-md-none">
        <picture>
            <img src="./public/images/banner-login.png" alt="Imagem dos desenvolvedores" class="position-absolute">
            <img src="./public/images/borda-banner-login.png" alt="Borda do banner">
        </picture>

        <form id="form_mobile" class="m-auto mt-3 text-center" style="width: 80%;">
            <input type="text" class="form-control mb-3 ipt-matricula" placeholder="Matrícula (RA ou NIF)" required>
            <input type="password" class="form-control mb-3 ipt-senha" placeholder="Senha" required>

            <p class="small">Esqueci minha senha</p>
            <button type="submit" class="btn btn-danger w-100">Entrar</button>
            <div id="msg_erro_mobile" class="text-danger"></div>
        </form>

        <picture class="d-flex justify-content-center mt-5">
            <img src="./public/images/logo-sesi.png" alt="Logo do sesi">
        </picture>
    </main>

    <main class="d-none d-md-flex vh-100">
        <picture class="w-50 vh-100 position-relative d-block shadow-lg">
            <img src="./public/images/banner-login-desktop2.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: 1;">
            <img src="./public/images/borda-banner-login-desktop.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: 2;">
        </picture>

        <section class="w-50">
            <picture class="d-flex justify-content-center my-5 py-5">
                <img src="./public/images/logo-sesi.png" alt="Logo do sesi">
            </picture>

            <form id="form_desktop" class="m-auto mt-3 text-center d-flex flex-column align-items-center bg-light p-4" style="width: 80%; border-radius: 15px;">
                <h2 class="text-danger my-4">Acesso ao sistema</h2>

                <div class="position-relative mb-3 w-75">
                    <i class="bi bi-person-circle position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="text" class="form-control ps-5 py-2 ipt-matricula" placeholder="Matrícula (RA/NIF)" style="border-radius: 10px;" required>
                </div>

                <div class="position-relative mb-3 w-75">
                    <i class="bi bi-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="password" class="form-control ps-5 py-2 ipt-senha" placeholder="Senha" style="border-radius: 10px;" required>
                </div>

                <p class="small cursor-pointer">Esqueci minha senha</p>
                <button type="submit" class="btn btn-danger w-50">Entrar</button>
                <div id="msg_erro_desktop" class="text-danger mt-2"></div>
            </form>
        </section>
    </main>

    <script>
        async function realizarLogin(e) {
            e.preventDefault();

            const form = e.target;

            const matricula =
                form.querySelector('.ipt-matricula').value.trim();

            const senha =
                form.querySelector('.ipt-senha').value.trim();

            const msgErro =
                form.querySelector('[id^="msg_erro"]');

            msgErro.innerText = '';

            try {

                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        matricula,
                        senha
                    })
                });

                const data = await response.json();

                if (data.status === 'sucesso') {
                    window.location.href = data.redirect;
                } else {
                    msgErro.innerText = data.mensagem;
                }

            } catch (erro) {

                console.error(erro);

                msgErro.innerText =
                    'Erro ao conectar com o servidor.';
            }
        }

        document
            .getElementById('form_mobile')
            .addEventListener('submit', realizarLogin);

        document
            .getElementById('form_desktop')
            .addEventListener('submit', realizarLogin);
    </script>
</body>

</html>