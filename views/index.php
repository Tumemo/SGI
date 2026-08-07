<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./src/styles/style.css">
    <title>SGI - Login</title>
</head>

<body>
    <!-- VERSÃO MOBILE CENTRALIZADA -->
    <main class="d-md-none min-vh-100 d-flex flex-column justify-content-center align-items-center p-3 text-center">
        <picture class="w-100 mb-3 text-center">
            <img src="./public/images/banner-login.png" alt="Imagem dos desenvolvedores" class="img-fluid mb-2">
            <img src="./public/images/borda-banner-login.png" alt="Borda do banner" class="img-fluid d-block mx-auto">
        </picture>

        <form id="form_mobile" class="w-100 my-auto" style="max-width: 360px;">
            <input type="text" class="form-control mb-3 ipt-matricula" placeholder="Matrícula (RA ou NIF)" required>
            <input type="password" class="form-control mb-3 ipt-senha" placeholder="Senha" required>
            <button type="submit" class="btn btn-danger w-100">Entrar</button>
            <div id="msg_erro_mobile" class="text-danger mt-2"></div>
        </form>

        <picture class="mt-4 w-100 d-flex justify-content-center">
            <img src="./public/images/logo-SGI-SESI.png" alt="Logo do sesi" class="img-fluid" style="max-width: 250px;">
        </picture>
    </main>

    <!-- VERSÃO DESKTOP CENTRALIZADA -->
    <main class="d-none d-md-flex vh-100">
        <picture class="w-50 vh-100 position-relative d-block shadow-lg">
            <img src="./public/images/banner-login-desktop2.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: 1;">
            <img src="./public/images/borda-banner-login-desktop.png" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: 2;">
        </picture>

        <section class="w-50 h-100 d-flex flex-column justify-content-center align-items-center p-4">
            <picture class="mb-4">
                <img src="./public/images/logo-SGI-SESI.png" alt="Logo do sesi" class="img-fluid" style="max-width: 280px;">
            </picture>

            <form id="form_desktop" class="text-center d-flex flex-column align-items-center bg-light p-4 w-100" style="max-width: 420px; border-radius: 15px;">
                <h2 class="text-danger mb-4">Acesso ao sistema</h2>

                <div class="position-relative mb-3 w-100">
                    <i class="bi bi-person-circle position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="text" class="form-control ps-5 py-2 ipt-matricula" placeholder="Matrícula (RA/NIF)" style="border-radius: 10px;" required>
                </div>

                <div class="position-relative mb-3 w-100">
                    <i class="bi bi-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="password" class="form-control ps-5 py-2 ipt-senha" placeholder="Senha" style="border-radius: 10px;" required>
                </div>

                <button type="submit" class="btn btn-danger w-100 mt-2">Entrar</button>
                <div id="msg_erro_desktop" class="text-danger mt-2"></div>
            </form>
        </section>
    </main>

    <script>
        async function realizarLogin(e) {
            e.preventDefault();

            const form = e.target;
            const msgErro = form.querySelector('[id^="msg_erro"]');

            msgErro.innerText = "";

            const matriculaInput = form.querySelector('.ipt-matricula');
            const senhaInput = form.querySelector('.ipt-senha');

            const payload = {
                matricula: matriculaInput.value.trim(),
                senha: senhaInput.value.trim()
            };

            try {
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.status === 'sucesso') {
                    window.location.href = data.redirect;
                } else {
                    msgErro.innerText = data.mensagem || "Erro ao realizar o login.";
                }
            } catch (err) {
                msgErro.innerText = "Erro ao conectar com o servidor.";
            }
        }

        document.getElementById('form_mobile').addEventListener('submit', realizarLogin);
        document.getElementById('form_desktop').addEventListener('submit', realizarLogin);
    </script>
</body>

</html>