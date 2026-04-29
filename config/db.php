<?php
$host = "sgi";
$user = "root";
$pass = "root";
$db = "sgi";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error){
    die("Falha na conexão: " . $conn->connect_error);
}

// print("Conexão bem-sucedida!");

$conn->set_charset("utf8mb4");  

