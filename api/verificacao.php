<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function verificarAdmin(){
    if(isset($_SESSION['nivel']) && $_SESSION['nivel'] == '0'){
        echo json_encode([
            "success" => true,
            "mensagem" => "admin liberado"
        ]);
        exit;
    }else{
        echo json_encode([
            "success" => false,
            "mensagem" => "admin bloqueado"
        ]);
        exit;
    }
}


function verificarColaborador(){
    if(isset($_SESSION['nivel']) && $_SESSION['nivel'] == '1'){
        echo json_encode([
            "success" => true,
            "mensagem" => "colaborador liberado"
        ]);
        exit;
    }else{
        echo json_encode([
            "success" => false,
            "mensagem" => "colaborador bloqueado"
        ]);
        exit;
    }
}


function verificarMesario(){
    if(isset($_SESSION['nivel']) && $_SESSION['nivel'] == '2'){
        echo json_encode([
            "success" => true,
            "mensagem" => "mesario liberado"
        ]);
        exit;
    }else{
        echo json_encode([
            "success" => false,
            "mensagem" => "mesario bloqueado"
        ]);
        exit;
    }
}

function verificarCompetidor(){
    if(isset($_SESSION['nivel']) && $_SESSION['nivel'] == '3'){
        echo json_encode([
            "success" => true,
            "mensagem" => "competidor liberado"
        ]);
        exit;
    }else{
        echo json_encode([
            "success" => false,
            "mensagem" => "competidor bloqueado"
        ]);
        exit;
    }
}