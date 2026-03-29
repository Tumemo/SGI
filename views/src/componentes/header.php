<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - <?php echo $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
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
    <img src="../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class=" w-100">
    <h1 class="position-absolute top-50 start-50 translate-middle text-white"><?php echo $textTop ?></h1>
</header>