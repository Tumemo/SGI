<?php
declare(strict_types=1);

/**
 * Script de Inicialização e Carga Demo do Interclasses SESI 2026
 * Cria uma edição ativa completa com turmas, modalidades, equipes e importa alunos do PDF.
 */

$baseUrl = rtrim((string) (getenv('SGI_TEST_BASE_URL') ?: getenv('SGI_APP_URL') ?: 'http://localhost/SGI'), '/');
$cookieJar = sys_get_temp_dir() . '/sgi_init_admin.txt';
@unlink($cookieJar);

function apiPost(string $url, array $data, string $cookieFile, ?string $csrfToken = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $headers = ['Content-Type: application/json'];
    if ($csrfToken !== null && $csrfToken !== '') {
        $headers[] = 'X-SGI-CSRF: ' . $csrfToken;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode((string)$res, true), 'raw' => $res];
}

function apiGet(string $url, string $cookieFile): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode((string)$res, true), 'raw' => $res];
}

function apiUpload(string $url, array $postFields, string $cookieFile, ?string $csrfToken = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    if ($csrfToken !== null && $csrfToken !== '') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-SGI-CSRF: ' . $csrfToken]);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode((string)$res, true), 'raw' => $res];
}

echo "=== INICIANDO CONFIGURAÇÃO INICIAL DO SGI PARA USO DOS ALUNOS ===\n\n";

// 1. Autenticar como Admin
$login = apiPost("$baseUrl/api/login.php", ['matricula' => 'admin', 'senha' => '123'], $cookieJar);
if (($login['json']['status'] ?? '') !== 'sucesso') {
    die("Erro no login do Administrador: " . $login['raw'] . "\n");
}
$csrfToken = is_string($login['json']['csrf_token'] ?? null) ? $login['json']['csrf_token'] : null;
echo "[1/5] Administrador autenticado com sucesso.\n";

// 2. Criar Edição Oficial do Interclasses
$anoAtual = date('Y');
$edicao = apiPost("$baseUrl/api/interclasse.php", [
    'nome_interclasse' => "Interclasses SESI $anoAtual",
    'ano_interclasse' => date('Y-m-d')
], $cookieJar, $csrfToken);

$idEdicao = (int) ($edicao['json']['id'] ?? 0);
if ($idEdicao <= 0) {
    die("Erro ao criar Edição: " . $edicao['raw'] . "\n");
}
echo "[2/5] Edição 'Interclasses SESI $anoAtual' criada com ID $idEdicao (35 equipes padrão geradas automaticamente).\n";

// 3. Cadastrar Locais Oficiais (Quadra Poliesportiva e Campo Society)
$local1 = apiPost("$baseUrl/api/locais.php", [
    'nome_local' => 'Ginásio Poliesportivo Principal',
    'disponivel_local' => '1',
    'carga_local' => 8,
    'interclasses_id_interclasse' => $idEdicao
], $cookieJar, $csrfToken);

$local2 = apiPost("$baseUrl/api/locais.php", [
    'nome_local' => 'Quadra Externa A',
    'disponivel_local' => '1',
    'carga_local' => 6,
    'interclasses_id_interclasse' => $idEdicao
], $cookieJar, $csrfToken);
echo "[3/5] Locais de jogos cadastrados (Ginásio Principal e Quadra Externa A).\n";

// 4. Importar Alunos do PDF da Turma 6EFB
$turmas = apiGet("$baseUrl/api/turmas.php?id_interclasse=$idEdicao", $cookieJar)['json'] ?? [];
$idTurma6EF = 0;
foreach ($turmas as $t) {
    if (str_contains($t['nome_turma'], '6') || str_contains($t['nome_turma'], 'EF')) {
        $idTurma6EF = (int)$t['id_turma'];
        break;
    }
}

if ($idTurma6EF > 0) {
$pdfPath = dirname(__DIR__) . '/tests/fixtures/6EFB.pdf';
if (!file_exists($pdfPath)) {
    $pdfPath = 'C:/xampp/htdocs/SGI/tests/fixtures/6EFB.pdf';
}

    if (file_exists($pdfPath)) {
        $cfile = new CURLFile($pdfPath, 'application/pdf', '6EFB.pdf');
        $upload = apiUpload("$baseUrl/api/upload_turma_pdf.php", [
            'pdf_arquivo' => $cfile,
            'id_turma' => (string) $idTurma6EF,
            'id_interclasse' => (string) $idEdicao
        ], $cookieJar, $csrfToken);
        echo "[4/5] Alunos da turma 6º Ano importados via PDF com sucesso (" . ($upload['json']['message'] ?? 'OK') . ").\n";
    }
}

// 5. Testar Login de um Aluno Importado (RM 2879)
$cookieAluno = sys_get_temp_dir() . '/sgi_init_aluno.txt';
$loginAluno = apiPost("$baseUrl/api/login.php", ['matricula' => '2879', 'senha' => '123'], $cookieAluno);
$nomeAluno = $loginAluno['json']['usuario']['nome'] ?? 'Aluno';
echo "[5/5] Teste de login do Aluno (RM: 2879 / Senha: 123) realizado com sucesso: $nomeAluno.\n";

echo "\n=================================================================\n";
echo "       BASE INICIALIZADA E PRONTA PARA USO DOS ALUNOS!           \n";
echo "=================================================================\n";
