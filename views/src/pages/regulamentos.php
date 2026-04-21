<?php
$titulo = "Regulamentos";
$textTop = "Regulamentos";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="mt-4 d-flex justify-content-center align-items-center flex-column gap-4 position-relative d-md-none" style="margin-bottom: 70px;">
    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Pontuação por colocação
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item justify-content-around d-flex align-items-center">
                1º lugar
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                2º lugar
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                3º lugar
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                Pontos
            </li>
        </ul>
    </div>


    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Peso da arrecadação solidária
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <div class="d-flex justify-content-between mt-2">
                    Multiplicador
                    <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10'>
                </div>
                <p class="text-body-tertiary mt-2 mb-0" style="font-size: 14px;">A arrecadação será multiplicada por esse valor</p>
            </li>
        </ul>
    </div>



    <div class="card shadow" style="width: 20rem;">
        <div class="card-header">
            Penalidades
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">Brigas</span>
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">Desrespeitar arbitragem</span>
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
            <li class="list-group-item justify-content-around d-flex align-items-center">
                <span class="text-center" style="width: 33%;">3º lugar</span>
                <input type="number" name="#" id="#" class="w-25 rounded border border-none shadow-sm text-center" value='10' style="width: 33%;">
                <span class="text-center" style="width: 33%;">Pontos</span>
            </li>
        </ul>
    </div>
    <section class="d-flex gap-2" style="cursor: pointer;">
        <a href="./edicao_resumo.php" class="btn btn-danger">Confirmar</a>
    </section>
</main>



<?php

require_once '../componentes/footer.php';
?>