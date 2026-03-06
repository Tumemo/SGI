<?php
    $title = "Nova Edição";
    $textTop = "Nova Edição";
    require_once '../components/header.php';
?>

<main class=" px-4 mt-4">
    <input type="text" name="nome" id="nome" placeholder="Nome" class="form-control mb-3">
    <div class="p-3 bg-white shadow rounded border">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Recipient’s username" aria-label="Recipient’s username" aria-describedby="basic-addon2">
            <span class="input-group-text text-white" id="basic-addon2" style="background-color: #e30613;">Adicionar</span>
        </div>

        <div>

            <div class=" shadow-sm">


                <div class=" border rounded justify-content-between mb-3 px-3 align-items-center py-2 input-group flex">
                    1ºano EM 
                    <div class="flex">
                        <input type="file" id="upload-1" class="d-none">
                        <label for="upload" class="btn btn-secondary border-0 px-4 py-2 opacity-75" style="background-color: #ccc; color: #555;">
                            Enviar o arquivo +
                        </label>
                    </div>
                </div>


                <div class=" border rounded justify-content-between mb-3 px-3 align-items-center py-2 input-group flex">
                    1ºano EM 
                    <div class="flex">
                        <input type="file" id="upload-1" class="d-none">
                        <label for="upload" class="btn btn-secondary border-0 px-4 py-2 opacity-75" style="background-color: #ccc; color: #555;">
                            Enviar o arquivo +
                        </label>
                    </div>
                </div>


                <div class=" border rounded justify-content-between mb-3 px-3 align-items-center py-2 input-group flex">
                    1ºano EM 
                    <div class="flex">
                        <input type="file" id="upload-1" class="d-none">
                        <label for="upload" class="btn btn-secondary border-0 px-4 py-2 opacity-75" style="background-color: #ccc; color: #555;">
                            Enviar o arquivo +
                        </label>
                    </div>
                </div>


            </div>
        </div>
    </div>
    <a href="" class="btn text-white px-5 mt-4" style="background-color: #e30613;">Continuar</a>
</main>

    
<?php 
    require_once '../components/navbar.php';
?>
