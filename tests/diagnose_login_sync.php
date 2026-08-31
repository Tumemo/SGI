<?php
declare(strict_types=1);

/**
 * Diagnóstico de Login e Preload/Sincronização
 * Testa a rota de login para cada perfil (Admin, Mesario, Aluno) e todas as chamadas de sincronização/preload.
 */

$baseUrl = 'http://localhost/SGI';

function testEndpoint(string $url, string $cookieFile): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'raw' => $res];
}

function testLogin(string $user, string $pass): string {
    global $baseUrl;
    $cookie = sys_get_temp_dir() . "/sgi_diag_$user.txt";
    @unlink($cookie);
    $ch = curl_init("$baseUrl/api/login.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['matricula' => $user, 'senha' => $pass]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Login [$user]: HTTP $code -> $res\n";
    return $cookie;
}

echo "=== DIAGNÓSTICO DE PRELOAD E SINCRONIZAÇÃO APÓS LOGIN ===\n\n";

// 1. Diagnóstico do Login do Mesário
$cookieMesario = testLogin('mesario', '123');

// Buscar Interclasse ativo
$rInter = testEndpoint("$baseUrl/api/interclasse.php?regulamento=true", $cookieMesario);
echo "GET api/interclasse.php?regulamento=true -> HTTP " . $rInter['code'] . "\n";
$edicoes = json_decode($rInter['raw'], true) ?? [];
$idAtivo = 0;
if (is_array($edicoes)) {
    foreach ($edicoes as $e) {
        if (($e['status_interclasse'] ?? '') === '1') {
            $idAtivo = (int)$e['id_interclasse'];
            break;
        }
    }
}
echo "Edição Ativa Encontrada: ID $idAtivo\n\n";

if ($idAtivo > 0) {
    $urlsPreload = [
        "$baseUrl/views/src/pages/perfil.php",
        "$baseUrl/views/src/pages/edicao_agenda.php?id=$idAtivo",
        "$baseUrl/views/src/pages/chaveamento_arvore.php?id=$idAtivo",
        "$baseUrl/views/src/pages/ocorrencias.php?id=$idAtivo",
        "$baseUrl/views/src/pages/jogos_lista.php?id=$idAtivo",
        "$baseUrl/views/src/pages/dashboard.php?id=$idAtivo",
        "$baseUrl/api/foto.php?user_id=3",
        "$baseUrl/api/interclasse.php?id=$idAtivo&regulamento=true",
        "$baseUrl/api/modalidades.php",
        "$baseUrl/api/modalidades.php?id_interclasse=$idAtivo",
        "$baseUrl/api/locais.php?id_interclasse=$idAtivo&disponivel=1",
        "$baseUrl/api/locais.php?id_interclasse=$idAtivo",
        "$baseUrl/api/categorias.php?id_interclasse=$idAtivo",
        "$baseUrl/api/turmas.php?id_interclasse=$idAtivo",
        "$baseUrl/api/equipes.php",
        "$baseUrl/api/equipes.php?id_interclasse=$idAtivo",
        "$baseUrl/api/jogos.php?id_interclasse=$idAtivo",
        "$baseUrl/api/jogos.php?x=1&id_interclasse=$idAtivo",
        "$baseUrl/api/chaveamento.php?id_modalidade=1",
        "$baseUrl/api/chaveamento.php?tipo_modalidade=individual&acao=participantes&id_modalidade=1",
        "$baseUrl/api/chaveamento.php?tipo_modalidade=individual&acao=ranking&id_modalidade=1",
        "$baseUrl/api/ocorrencias_turmas.php?id_interclasse=$idAtivo",
    ];

    echo "--- TESTANDO URLS DO PRELOAD DO MESÁRIO ---\n";
    foreach ($urlsPreload as $u) {
        $res = testEndpoint($u, $cookieMesario);
        $status = ($res['code'] >= 200 && $res['code'] < 400) ? "OK" : "ERRO";
        echo "[$status] HTTP " . $res['code'] . " -> $u\n";
    }
}
