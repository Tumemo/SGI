<?php

session_start();

function verificarAdmin(){
    $_SESSION['nivel'];

    if($_SESSION['nivel'] == '0'){

    }else{

    }
}
// 0 admin
// 1 colaborador ou comissao
// 2 mesario
// 3 competidor
