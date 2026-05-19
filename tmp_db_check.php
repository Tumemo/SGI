<?php
$mysqli = new mysqli('localhost', 'root', '', 'sgiv2');
if ($mysqli->connect_error) {
    echo 'DB ERR: ' . $mysqli->connect_error . "\n";
    exit(1);
}
$res = $mysqli->query("SELECT COUNT(*) AS total FROM usuarios WHERE status_usuario = '1' AND competidor_usuario = '1' AND turmas_id_turma IS NOT NULL AND turmas_id_turma != 0");
$row = $res->fetch_assoc();
echo 'competidor com turma: ' . $row['total'] . "\n";
$res2 = $mysqli->query("SELECT turmas_id_turma, COUNT(*) AS cnt FROM usuarios WHERE status_usuario = '1' AND competidor_usuario = '1' GROUP BY turmas_id_turma ORDER BY cnt DESC LIMIT 20");
while ($r = $res2->fetch_assoc()) {
    echo $r['turmas_id_turma'] . ': ' . $r['cnt'] . "\n";
}
$mysqli->close();
