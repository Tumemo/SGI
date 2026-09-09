<?php
// Esta tela legada não possuía autenticação: ela apenas levava o visitante a
// A entrada canônica do login do aluno é mantida nesta rota pública.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ' . \App\Shared\Http\Url::to('login'), true, 302);
exit;
