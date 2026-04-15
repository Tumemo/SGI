<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - <?php echo $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body>
    
<header class="position-relative">
    <?php 
    if($btnVoltar){
        echo '<a href="javascript:history.back()"><span class="position-absolute m-4 translate-middle" style="z-index: 10; " id="btnVoltar"><i class="bi bi-arrow-left fs-1 text-white"></i></span></a>';
    } else{
        echo "";
    }
    ?>
    <!-- header mobile -->
    <section class="d-md-none" style="height: 120px;">
        <a href="perfil.php"><span class="position-absolute m-4 translate-middle text-white fs-2" style="z-index: 10; top: 3%; right: -20px;" id="btnVoltar"><i class="bi bi-person-gear"></i></span></a>
        <img src="../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100 object-fit-cover" style="height: 100%;">
        <h1 class="position-absolute top-50 start-50 translate-middle text-white w-100 text-center"><?php echo $textTop ?></h1>
    </section>

    <!-- header desktop -->
    <section class="d-none d-md-flex shadow-lg" style="height: 150px;">
        <img src="../../public/images/banner-global-desktop.png" alt="Imagens de alunos do SESI" class="w-100 object-fit-cover">
        <img src="../../public/images/banner-global-desktop-frente.png" alt="sombra da imagem de alunos do SESI" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
    </section>
</header>