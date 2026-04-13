<?php
$titulo = "Arrecadação";
$textTop = "Arrecadação";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="d-md-none">
    <div class="card shadow m-auto mt-4" style="width: 20rem;">
        <div class="card-header">
            Pontos da arrecadação
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item justify-content-around d-flex align-items-center">
                3º Ensino Médio
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                2º Ensino Médio
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                3º Ensino Médio
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
        </ul>
    </div>
</main>



<!-- main desktop -->

<?php

require_once '../componentes/navbar.php';
?>