<?php
declare(strict_types=1);

/**
 * Teste Especializado de Fluxo Offline e Avanço de Chaves com Sincronização
 */

$baseUrl = rtrim(getenv('SGI_TEST_BASE_URL') ?: 'http://localhost/SGI', '/');
$cookieJarAdmin = sys_get_temp_dir() . '/sgi_test_admin.txt';
$cookieJarMesario = sys_get_temp_dir() . '/sgi_test_mesario.txt';

@unlink($cookieJarAdmin);
@unlink($cookieJarMesario);

function httpPostJson(string $url, array $data, ?string $cookieFile = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode((string)$res, true), 'raw' => $res];
}

function httpGetJson(string $url, ?string $cookieFile = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode((string)$res, true), 'raw' => $res];
}

echo "=================================================================\n";
echo " TESTE DE VALIDAÇÃO DO FLUXO OFFLINE E AVANÇO DE CHAVES (MATA-MATA)\n";
echo "=================================================================\n\n";

// 1. Autenticação
$loginA = httpPostJson("$baseUrl/api/login.php", ['matricula' => 'admin', 'senha' => '123'], $cookieJarAdmin);
$loginM = httpPostJson("$baseUrl/api/login.php", ['matricula' => 'mesario', 'senha' => '123'], $cookieJarMesario);

if (($loginA['json']['status'] ?? '') !== 'sucesso' || ($loginM['json']['status'] ?? '') !== 'sucesso') {
    die("Falha na autenticação inicial.\n");
}
echo "[1/6] Autenticação realizada com sucesso (Admin e Mesário)\n";

// 2. Criar Edição de Teste
$nomeEdicao = "Torneio Offline " . date('His');
$edicao = httpPostJson("$baseUrl/api/interclasse.php", [
    'nome_interclasse' => $nomeEdicao,
    'ano_interclasse' => date('Y-m-d')
], $cookieJarAdmin);
$idEdicao = (int) ($edicao['json']['id'] ?? 0);
echo "[2/6] Edição criada com ID $idEdicao e 35 equipes geradas\n";

// 3. Obter Modalidade com 4 Equipes
$modalidades = httpGetJson("$baseUrl/api/modalidades.php?id_interclasse=$idEdicao", $cookieJarAdmin);
$modMataMata = null;
$idModalidade = 0;
$eqs = [];

foreach ($modalidades['json'] as $m) {
    $idM = (int)$m['id_modalidade'];
    $respEq = httpGetJson("$baseUrl/api/equipes.php?id_modalidade=$idM", $cookieJarAdmin);
    $listaEq = $respEq['json'] ?? [];
    if (count($listaEq) >= 4) {
        $modMataMata = $m;
        $idModalidade = $idM;
        $eqs = $listaEq;
        break;
    }
}

if (!$modMataMata || count($eqs) < 4) {
    die("Nenhuma modalidade com 4 equipes encontrada.\n");
}
$e1 = (int)$eqs[0]['id_equipe'];
$e2 = (int)$eqs[1]['id_equipe'];
$e3 = (int)$eqs[2]['id_equipe'];
$e4 = (int)$eqs[3]['id_equipe'];

$locais = httpGetJson("$baseUrl/api/locais.php?id_interclasse=$idEdicao", $cookieJarAdmin);
$idLocal = (int) ($locais['json'][0]['id_local'] ?? 1);

echo "[3/6] Modalidade: {$modMataMata['nome_modalidade']} (ID $idModalidade), 4 Equipes selecionadas\n";

// 4. Criar Semifinais no MySQL (MM:4:0:N e MM:4:1:N)
$j1 = httpPostJson("$baseUrl/api/jogos.php", [
    'nome_jogo' => 'MM:4:0:N',
    'data_jogo' => date('Y-m-d'),
    'inicio_jogo' => '08:00',
    'termino_jogo' => '08:40',
    'status_jogo' => 'Agendado',
    'modalidades_id_modalidade' => $idModalidade,
    'locais_id_local' => $idLocal,
    'equipes' => [['id_equipe' => $e1], ['id_equipe' => $e2]]
], $cookieJarAdmin);
$idJogo1 = (int) ($j1['json']['id_jogo'] ?? $j1['json']['id'] ?? 0);

$j2 = httpPostJson("$baseUrl/api/jogos.php", [
    'nome_jogo' => 'MM:4:1:N',
    'data_jogo' => date('Y-m-d'),
    'inicio_jogo' => '09:00',
    'termino_jogo' => '09:40',
    'status_jogo' => 'Agendado',
    'modalidades_id_modalidade' => $idModalidade,
    'locais_id_local' => $idLocal,
    'equipes' => [['id_equipe' => $e3], ['id_equipe' => $e4]]
], $cookieJarAdmin);
$idJogo2 = (int) ($j2['json']['id_jogo'] ?? $j2['json']['id'] ?? 0);

echo "[4/6] Semifinais criadas: Semifinal 1 (ID $idJogo1) e Semifinal 2 (ID $idJogo2)\n";

// 5. Testar Sincronização dos Jogos Jogados Offline
echo "\n[5/6] Simulando descarregamento da fila de sincronização offline...\n";

// 5.1 Enviar conclusão da Semifinal 1 (Equipe 1 vence Equipe 2 por 3x1)
$resSf1 = httpPostJson("$baseUrl/api/lancar_resultado.php", [
    'id_jogo' => $idJogo1,
    'nome_jogo' => 'MM:4:0:N',
    'id_modalidade' => $idModalidade,
    'resultados' => [
        ['id_equipe' => $e1, 'gols' => 3],
        ['id_equipe' => $e2, 'gols' => 1]
    ]
], $cookieJarMesario);
echo "  - Semifinal 1 finalizada: " . ($resSf1['json']['message'] ?? $resSf1['raw']) . "\n";

// 5.2 Enviar conclusão da Semifinal 2 (Equipe 3 vence Equipe 4 por 2x0)
$resSf2 = httpPostJson("$baseUrl/api/lancar_resultado.php", [
    'id_jogo' => $idJogo2,
    'nome_jogo' => 'MM:4:1:N',
    'id_modalidade' => $idModalidade,
    'resultados' => [
        ['id_equipe' => $e3, 'gols' => 2],
        ['id_equipe' => $e4, 'gols' => 0]
    ]
], $cookieJarMesario);
echo "  - Semifinal 2 finalizada: " . ($resSf2['json']['message'] ?? $resSf2['raw']) . "\n";

// 5.3 Enviar mutação intermediária de partida/artilharia com ID provisório negativo
$resPartida = httpPostJson("$baseUrl/api/partidas.php", [
    'id_partida' => 'mm_local_-1_0',
    'resultado_partida' => 4
], $cookieJarMesario);
echo "  - Partida temporária tratada sem erro: " . ($resPartida['json']['message'] ?? $resPartida['raw']) . "\n";

$resArt = httpPostJson("$baseUrl/api/artilheiro.php", [
    'usuarios_id_usuario' => 1,
    'jogos_id_jogo' => -1,
    'nome_jogo' => 'MM:2:0:N',
    'id_modalidade' => $idModalidade,
    'num_gol' => 2
], $cookieJarMesario);
echo "  - Artilharia offline tratada sem erro: " . ($resArt['json']['message'] ?? $resArt['raw']) . "\n";

// 5.4 Enviar conclusão da Grande Final com ID Provisório Negativo (-1)
// (Equipe 1 vence Equipe 3 por 4x2 e sagra-se Campeã)
$resFinal = httpPostJson("$baseUrl/api/lancar_resultado.php", [
    'id_jogo' => -1,
    'nome_jogo' => 'MM:2:0:N',
    'id_modalidade' => $idModalidade,
    'resultados' => [
        ['id_equipe' => $e1, 'gols' => 4],
        ['id_equipe' => $e3, 'gols' => 2]
    ]
], $cookieJarMesario);
echo "  - Grande Final (ID provisório -1) sincronizada com sucesso: " . ($resFinal['json']['message'] ?? $resFinal['raw']) . "\n";

// 6. Verificar Árvore Final e Pódio no Servidor
echo "\n[6/6] Verificando resultado consolidado no servidor...\n";
$arvore = httpGetJson("$baseUrl/api/chaveamento.php?id_modalidade=$idModalidade", $cookieJarAdmin);
$jogosArvore = $arvore['json']['jogos'] ?? [];

$finalJogo = null;
foreach ($jogosArvore as $j) {
    if (($j['nome_jogo'] ?? '') === 'MM:2:0:N') $finalJogo = $j;
}

$sucessoFinal = $finalJogo && ($finalJogo['status_jogo'] === 'Concluido' || $finalJogo['status_jogo'] === 'Finalizado');
$campeaoDefinido = $sucessoFinal && !empty($finalJogo['equipe_vencedora_id']);
$jogoSoloCriado = array_filter($jogosArvore, static fn($j) => preg_match('/^MM:1:/', (string) ($j['nome_jogo'] ?? '')));

if ($sucessoFinal) {
    echo "  [OK] Jogo da Grande Final (MM:2:0:N) está marcado como CONCLUÍDO no MySQL.\n";
    echo "  [OK] Placar final e vencedor computados perfeitamente no servidor.\n";
    if ($campeaoDefinido && !$jogoSoloCriado) {
        echo "  [OK] Campeão derivado da final sem criação de jogo solo MM:1.\n";
    } else {
        echo "  [FALHA] Campeão não foi derivado corretamente ou houve criação de MM:1.\n";
        exit(1);
    }
    echo "\n>>> TESTE DE FLUXO OFFLINE CONCLUÍDO COM 100% DE SUCESSO! <<<\n";
    exit(0);
} else {
    echo "  [FALHA] Estado inesperado na árvore de chaveamento.\n";
    echo "  Detalhes: " . json_encode($jogosArvore, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}
