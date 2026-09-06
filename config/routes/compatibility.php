<?php

declare(strict_types=1);

// Stable URLs used by installed clients and pending offline mutations.
return [
    'aliases' => [
        '/api/chaveamento.php' => '/api/v1/chaveamentos',
        '/api/upload_turma_pdf.php' => '/api/v1/importacoes/turma-pdf',
        '/api/equipes.php' => '/api/v1/equipes',
        '/api/CriarEquipes.php' => '/api/v1/equipes/gerar',
        '/api/ocorrencias.php' => '/api/v1/ocorrencias',
        '/api/ocorrencias_turmas.php' => '/api/v1/ocorrencias-turmas',
        '/api/artilheiro.php' => '/api/v1/artilheiros',
        '/api/classificacao.php' => '/api/v1/classificacao',
        '/api/interclasse.php' => '/api/v1/edicoes',
        '/api/arrecadacao.php' => '/api/v1/arrecadacao',
        '/api/turmas.php' => '/api/v1/turmas',
        '/api/concordarTermos.php' => '/api/v1/termos',
        '/api/trocar_senha.php' => '/api/v1/senha',
        '/api/auth.php' => '/api/v1/session',
        '/api/logout.php' => '/api/v1/logout',
        '/api/login.php' => '/api/v1/login',
    '/api/categorias.php' => '/api/v1/categorias',
    '/api/locais.php' => '/api/v1/locais',
    '/api/modalidades.php' => '/api/v1/modalidades',
    '/api/ranking.php' => '/api/v1/ranking',
    '/api/tipoModalidade.php' => '/api/v1/tipos-modalidade',
    '/api/foto.php' => '/api/v1/foto',
    ],
    'endpoints' => [
        '/api/historico_turma.php',
        '/api/inscricao.php',
        '/api/jogos.php',
        '/api/lancar_resultado.php',
        '/api/partidas.php',
        '/api/sincronizar_chaveamento.php',
        '/api/usuarios.php',
    ],
];
