<?php
$titulo = "Modalidades";
$textTop = "Modalidades";
require_once '../components/header.php';
?>



<main class="px-4 mt-4">

    <form action="#" id="formFormulario">

        <!-- primeiro card do figma, mecher na pontuação dos colocados -->
        <div class="mb-3">
            <ul class="list-group">
                <li class="list-group-item">
                    <h2 class="fs-5">Pontuação por colocação</h2>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">1º lugar</span>
                    <input type="number" name="1lugar" id="1lugar" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">2º lugar</span>
                    <input type="number" name="2lugar" id="2lugar" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">3º lugar</span>
                    <input type="number" name="3lugar" id="3lugar" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
            </ul>
        </div>


        <!-- segundo card do figma, mecher na pontuação dos kilos da arrecadação -->
        <div class="mb-3">
            <ul class="list-group">
                <li class="list-group-item">
                    <h2 class="fs-5">Peso da arrecadação solidária</h2>
                </li>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between px-1">
                        <span class="my-auto">Multiplicador</span>
                        <input type="number" name="multiplicador" id="multiplicador" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    </div>
                    <p class="text-secondary mt-3" style="font-size: 0.94em;">A arrecadação será multiplicada por esse valor</p>
                </li>
            </ul>
        </div>


        <!-- terceiro card do figma, mecher na pontuação das penalidades -->
        <div>
            <ul class="list-group">
                <li class="list-group-item">
                    <h2 class="fs-5">Penalidades</h2>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">Brigas</span>
                    <input type="number" name="brigas" id="brigas" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">Desrespeitar <br> arbitragem</span>
                    <input type="number" name="desreipeito" id="desreipeito" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
                </li>
                <li class="list-group-item d-flex justify-content-around">
                    <span style="width: 33%;">3º lugar</span>
                    <input type="number" name="sla" id="sla" value="10" class="form-control w-25 rounded border border-none shadow-sm text-center" style="background-color: #eaeaea;" onfocus="this.style.backgroundColor='#e0e0e0';">
                    <span>pontos</span>
                </li>
            </ul>
        </div>
    </form>



    <a href="#" class="btn text-white px-5 mt-5 position-absolute start-50 translate-middle" style="background-color: #e30613;">Criar</a>

</main>

<?php
require_once '../components/navbar.php';
?>