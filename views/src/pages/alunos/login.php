<?php
// Esta tela legada não possuía autenticação: ela apenas levava o visitante a
// home.php. Direcionamos para a única entrada que executa o login real.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ../../../index.php', true, 302);
exit;
