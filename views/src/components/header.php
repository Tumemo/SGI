<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - <?= $titulo ?></title>
    <link rel="stylesheet" href="../../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f2f2f2;
        }

        .form-control:focus {
            border-color: #e30613;
            box-shadow: 0px 0px 15px #e306159b;
            transition: 0.2s;
        }
    </style>
</head>

<body style="width: 100dvw; height: 100dvh;">
    <header class="position-relative w-100">
        <img class="w-100" src="../../public/images/banner-global.png" alt="Crianças em volta da bola" class="w-100">
        <h2 class="position-absolute text-white top-50 start-50 translate-middle"><?= $textTop ?></h2>
    </header>