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
<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0" style="max-width: 1000px;">

        <div class="mb-5">
            <button class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> Interclasse 2026
            </button>

            <div class="d-flex justify-content-Between aligni-items-center align-items-center">
                <h4 class="fw-bold text-dark mb-0">Arrecadações</h4>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button class="btn bg-white fw-semibold rounded-3 px-4 py-2" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <button class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                        Salvar
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>

                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button">
                            <i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i>
                        </button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button">
                            <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">9º EF A</span>
                    <div class="d-flex align-items-center rounded-3 px-2 shadow-sm" style="background-color: #f4f5f7; border: 1px solid #e9ecef; height: 38px;">
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-left" style="font-size: 0.75rem;"></i></button>
                        <input type="text" class="form-control border-0 bg-transparent text-center shadow-none p-0 mx-2" value="" style="width: 35px; font-weight: bold; color: #333;" readonly>
                        <button class="btn btn-sm border-0 p-0 text-muted shadow-none" type="button"><i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i></button>
                    </div>
                </div>
            </div>

        </div>



    </div>
</main>

<?php

require_once '../componentes/footer.php';
?>