<?php
$titulo = "Perfil";
$textTop = "Perfil";
$btnVoltar = true;
require_once '../componentes/header.php';
?>

<main class="position-relative">
    <section class="mt-4">
        <div class="rounded-circle bg-secondary m-auto shadow" style="width: 130px; height: 130px;"></div>
        <form action="#" method="post" class="m-auto mt-3 text-center" style="width: 90%;">
            <div class="mb-3 text-start ps-2">
                <label for="form" class="form-label">Nome:</label>
                <input type="text" class="form-control" placeholder="nome">
            </div>
            <div class="mb-3 text-start ps-2">
                <label for="form" class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="email">
            </div>
            <div class="mb-5 text-start ps-2">
                <label for="form" class="form-label">Senha</label>
                <input type="password" class="form-control" placeholder="senha">
            </div>
            <input type="submit" value="Editar perfil" class="btn btn-danger px-2">
        </form>
    </section>
</main>


<?php

require_once '../componentes/navbar.php';
?>