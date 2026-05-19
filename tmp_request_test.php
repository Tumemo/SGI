<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['acao' => 'listar_por_turma', 'id_turma' => '2'];
include __DIR__ . '/api/usuarios.php';
