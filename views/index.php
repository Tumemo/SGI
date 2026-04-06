<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>SGI - Login</title>
    <style>
        .form-control:focus {
            box-shadow: 0px 0px 10px 2px red;
            border: none;
        }
    </style>
</head>

<body>
    <!-- login mobile -->
    <main class="d-md-none">
        <picture>
            <img src="./public/images/banner-login.png" alt="Imagem dos desenvolvedores" class="position-absolute">
            <img src="./public/images/borda-banner-login.png" alt="Borda do banner">
        </picture>

        <form action="#" method="post" class="m-auto mt-3 text-center" style="width: 80%;">
            <input type="email" class="form-control mb-3" placeholder="email">
            <input type="password" class="form-control mb-3" placeholder="senha">
            <p>Esqueci minha senha</p>
            <a href="./src/pages/home.php" class="btn btn-danger w-100">Entrar</a>
        </form>
        <picture class="d-flex justify-content-center mt-5">
            <img src="./public/images/logo-sesi.png" alt="Logo do sesi">
        </picture>
    </main>


    <!-- login desktop -->
    <main class="d-none d-md-flex vh-100">

        <picture class="w-50 vh-100 position-relative d-block shadow-lg">

            <img src="./public/images/banner-login-desktop.png"
                alt="Imagem dos desenvolvedores"
                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover "
                style="z-index: 1;">

            <img src="./public/images/borda-banner-login-desktop.png"
                alt="Borda do banner"
                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                style="z-index: 2;">

        </picture>

        <section class="w-50">
            <picture class="d-flex justify-content-center my-5 py-5">
                <img src="./public/images/logo-sesi.png" alt="Logo do sesi">
            </picture>
            <form action="#" method="post" class="m-auto mt-3 text-center d-flex flex-column align-items-center bg-light" style="width: 80%;">
                <h2 class="text-danger my-5">Acesso ao sistema</h2>
                <div class="position-relative mb-3 w-75">
                    <i class="bi bi-person-circle position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="email" class="form-control ps-5 py-2" placeholder="Email" style="border-radius: 10px;">
                </div>

                <div class="position-relative mb-3 w-75">
                    <i class="bi bi-lock position-absolute top-50 start-0 translate-middle-y ms-3 text-dark"></i>
                    <input type="password" class="form-control ps-5 py-2" placeholder="NIF/RM" style="border-radius: 10px;">
                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 text-secondary" style="cursor: pointer;"></i>
                </div>
                <p>Esqueci minha senha</p>
                <a href="./src/pages/home.php" class="btn btn-danger w-50">Entrar</a>
            </form>
        </section>
    </main>
</body>

</html>