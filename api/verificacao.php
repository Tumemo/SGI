<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function verificarLogin()
{
    return isset($_SESSION['id_usuario']) && isset($_SESSION['nivel_usuario']);
}

function exigirNivel($nivel)
{
if (!verificarLogin() || $_SESSION['nivel_usuario'] != $nivel) {
        header('location: application');
        exit;
    } 
}
 
function exigirAdmin()
{
    exigirNivel(0);
}

function exigirColaborador()
{
    exigirNivel(1);
}

function exigirMesario()
{
    exigirNivel(2);
}

function exigirCompetidor()
{
    exigirNivel(3);
}