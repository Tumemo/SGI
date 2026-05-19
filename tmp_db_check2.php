<?php
$mysqli = new mysqli('localhost', 'root', '', 'sgiv2');
if ($mysqli->connect_error) {
    echo 'DB ERR: ' . $mysqli->connect_error . "\n";
    exit(1);
}
$res = $mysqli->query("SELECT t.id_turma, t.nome_turma, COUNT(u.id_usuario) AS usuarios FROM turmas t LEFT JOIN usuarios u ON u.turmas_id_turma = t.id_turma AND u.status_usuario='1' AND u.competidor_usuario='1' GROUP BY t.id_turma ORDER BY t.id_turma");
while($r=$res->fetch_assoc()) {
    echo $r['id_turma'] . ': ' . $r['nome_turma'] . ' => ' . $r['usuarios'] . "\n";
}
$mysqli->close();
