<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGM - Login</title>
    <link rel="stylesheet" href="./node_modules/bootstrap/dist/css/bootstrap.min.css">
    <style>
        .form-control:focus {
            border-color: #e30613;
            box-shadow: 0px 0px 15px #e306159b;
            transition: 0.2s;
        }
    </style>
</head>
<body>
    <header class="position-relative">
        <img class="position-absolute" src="./public/images/banner-login.png" alt="Imagem de criancas brincando">
        <img src="./public/images/borda-banner-login.png" alt="Borda do banner login">
    </header>
    <main>
        <form class="w-75 m-auto mt-4">
            <div class="mb-3">
                <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="email">
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" id="exampleInputPassword1" placeholder="senha">
            </div>
            </div>
            <a href="./src/pages/home.php" class="btn w-100 text-white my-3" style="background-color: #e30613;">Entrar</a>
            <div class="w-100 d-flex justify-content-center mt-1">
                <img src="./public/images/sesi-logo.png" alt="Logo do SESI">
            </div>
            </form>
    </main>
</body>
</html>