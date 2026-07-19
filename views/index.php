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
        // Função unificada e corrigida para processar o envio dos dados
        async function realizarLogin(e) {
            e.preventDefault();
            
            const form = e.target; // Captura exatamente o formulário enviado (evita misturar mobile com desktop)
            const msgErro = form.querySelector('[id^="msg_erro"]');
            
            // Limpa mensagens de erro antigas
            msgErro.innerText = ""; 

            // Captura os elementos de input especificamente de DENTRO do formulário atual
            const matriculaInput = form.querySelector('.ipt-matricula');
            const senhaInput = form.querySelector('.ipt-senha');

            // Monta os dados limpando espaços vazios acidentais nas pontas (.trim())
            const payload = {
                matricula: matriculaInput.value.trim(),
                senha: senhaInput.value.trim()
            };

            try {
                // Utilizando a rota absoluta corrigida que encontrou o arquivo com sucesso
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.status === 'sucesso') {
                    // Redireciona para a página correspondente ao nível devolvida pelo PHP
                    window.location.href = data.redirect;
                } else {
                    // Mostra a mensagem exata retornada pelo PHP (ex: "Matrícula ou Senha incorretos.")
                    msgErro.innerText = data.mensagem || "Erro ao realizar o login.";
                }
            } catch (err) {
                msgErro.innerText = "Erro ao conectar com o servidor.";
            }
        }

        // Registra os eventos de escuta nos dois formulários da página
        document.getElementById('form_mobile').addEventListener('submit', realizarLogin);
        document.getElementById('form_desktop').addEventListener('submit', realizarLogin);
    </script>
</body>
</html>