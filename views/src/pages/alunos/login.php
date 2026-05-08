<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <title>SGI - Login Aluno</title>
    <style>
        .form-control:focus { box-shadow: 0 0 10px 2px red; border: none; }
    </style>
</head>
<body>
    <main class="d-md-none">
        <picture>
            <img src="../../../public/images/banner-login.png" alt="Imagem dos alunos" class="position-absolute">
            <img src="../../../public/images/borda-banner-login.png" alt="Borda do banner">
        </picture>
        <form action="#" method="post" class="m-auto mt-3 text-center" style="width: 80%;">
            <input type="email" class="form-control mb-3" placeholder="email">
            <input type="password" class="form-control mb-3" placeholder="senha">
            <p>Esqueci minha senha</p>
            <a href="./home.php" class="btn btn-danger w-100">Entrar</a>
        </form>
        <picture class="d-flex justify-content-center mt-5">
            <img src="../../../public/images/logo-sesi.png" alt="Logo do SESI">
        </picture>
    </main>
</body>
</html>
