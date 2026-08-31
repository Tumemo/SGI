<?php
declare(strict_types=1);

/**
 * Suite Completa de Testes E2E Automatizados para o SGI
 * Executa testes reais via HTTP contra o servidor local (XAMPP).
 */

$baseUrl = 'http://localhost/SGI';
$cookieJarAdmin = sys_get_temp_dir() . '/sgi_cookie_admin.txt';
$cookieJarColab = sys_get_temp_dir() . '/sgi_cookie_colab.txt';
$cookieJarMesario = sys_get_temp_dir() . '/sgi_cookie_mesario.txt';
$cookieJarAluno = sys_get_temp_dir() . '/sgi_cookie_aluno.txt';

@unlink($cookieJarAdmin);
@unlink($cookieJarColab);
@unlink($cookieJarMesario);
@unlink($cookieJarAluno);

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$failures = [];

function httpReq(string $url, string $method = 'GET', $data = null, ?string $cookieFile = null, array $headers = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data) && isset($headers['Content-Type']) && str_contains($headers['Content-Type'], 'json')) {
            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif (is_string($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    } elseif ($method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            $payload = is_array($data) ? json_encode($data) : $data;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    }

    $formattedHeaders = [];
    foreach ($headers as $k => $v) {
        $formattedHeaders[] = "$k: $v";
    }
    if (!empty($formattedHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
    }

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $json = null;
    if ($body && is_string($body)) {
        $json = json_decode($body, true);
    }

    return [
        'code' => $httpCode,
        'body' => $body,
        'json' => $json,
        'error' => $err
    ];
}

function testAssert(string $testName, bool $condition, string $details = ''): void {
    global $totalTests, $passedTests, $failedTests, $failures;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $testName\n";
    } else {
        $failedTests++;
        $failures[] = "$testName: $details";
        echo "  [FAIL] $testName - $details\n";
    }
}

echo "========================================================\n";
echo " INICIANDO AUDITORIA E TESTE E2E COMPLETO DO SGI\n";
echo "========================================================\n\n";

// =========================================================================
// SUITE 1: Autenticação e Perfis de Usuário
// =========================================================================
echo ">> SUITE 1: Autenticação e Controle de Sessão\n";

// 1.1 Login Admin
$res = httpReq("$baseUrl/api/login.php", 'POST', ['matricula' => 'admin', 'senha' => '123'], $cookieJarAdmin, ['Content-Type' => 'application/json']);
testAssert("Login do Administrador (admin / 123)", $res['code'] === 200 && ($res['json']['status'] ?? '') === 'sucesso', $res['body']);

// 1.2 Login Colaborador
$res = httpReq("$baseUrl/api/login.php", 'POST', ['matricula' => 'colab', 'senha' => '123'], $cookieJarColab, ['Content-Type' => 'application/json']);
testAssert("Login do Colaborador (colab / 123)", $res['code'] === 200 && ($res['json']['status'] ?? '') === 'sucesso', $res['body']);

// 1.3 Login Mesário
$res = httpReq("$baseUrl/api/login.php", 'POST', ['matricula' => 'mesario', 'senha' => '123'], $cookieJarMesario, ['Content-Type' => 'application/json']);
testAssert("Login do Mesário (mesario / 123)", $res['code'] === 200 && ($res['json']['status'] ?? '') === 'sucesso', $res['body']);

// 1.4 Login Aluno existente
$res = httpReq("$baseUrl/api/login.php", 'POST', ['matricula' => '2879', 'senha' => '123'], $cookieJarAluno, ['Content-Type' => 'application/json']);
testAssert("Login do Aluno Competidor (RM 2879 / 123)", $res['code'] === 200 && ($res['json']['status'] ?? '') === 'sucesso', $res['body']);

// 1.5 Rejeição de Senha Incorreta
$res = httpReq("$baseUrl/api/login.php", 'POST', ['matricula' => 'admin', 'senha' => 'senha_errada_xyz'], null, ['Content-Type' => 'application/json']);
testAssert("Rejeição de credenciais incorretas (HTTP 401)", $res['code'] === 401 && ($res['json']['status'] ?? '') === 'erro', $res['body']);


// =========================================================================
// SUITE 2: Gestão de Edições (Interclasses)
// =========================================================================
echo "\n>> SUITE 2: Criação e Gerenciamento de Edições (Interclasses)\n";

$novoNomeEdicao = "Interclasse E2E " . date('Ymd_His');
$res = httpReq("$baseUrl/api/interclasse.php", 'POST', [
    'nome_interclasse' => $novoNomeEdicao,
    'ano_interclasse' => date('Y-m-d')
], $cookieJarAdmin, ['Content-Type' => 'application/json']);

$novoIdInterclasse = (int) ($res['json']['id'] ?? 0);
testAssert("Criação de nova edição de Interclasse", $res['code'] === 200 && ($res['json']['success'] ?? false) === true && $novoIdInterclasse > 0, $res['body']);
testAssert("Geração automática de equipes padrão (esperado >= 35)", ($res['json']['equipes_padrao_garantidas'] ?? 0) >= 35, $res['body']);

// 2.2 Listagem de Interclasses
$res = httpReq("$baseUrl/api/interclasse.php?regulamento=true", 'GET', null, $cookieJarAdmin);
testAssert("Listagem de edições cadastradas", $res['code'] === 200 && is_array($res['json']) && count($res['json']) > 0, $res['body']);

// 2.3 Atualização de Pontuações de Pódio e Arrecadação
$res = httpReq("$baseUrl/api/interclasse.php?id=$novoIdInterclasse", 'POST', [
    'ponto_1_lugar' => 15,
    'ponto_2_lugar' => 10,
    'ponto_3_lugar' => 7,
    'valor_item_arrecadacao' => 3
], $cookieJarAdmin);
testAssert("Atualização das configurações de pontuação do Interclasse", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);


// =========================================================================
// SUITE 3: Categorias, Turmas, Modalidades e Equipes
// =========================================================================
echo "\n>> SUITE 3: Estrutura Escolar e Esportiva (Categorias, Turmas, Modalidades)\n";

// 3.1 Consulta de Categorias da Nova Edição
$res = httpReq("$baseUrl/api/categorias.php?id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
$categorias = $res['json'] ?? [];
testAssert("Consulta de categorias criadas automaticamente (esperado 2)", count($categorias) === 2, $res['body']);
$cat1Id = (int) ($categorias[0]['id_categoria'] ?? 0);
$cat2Id = (int) ($categorias[1]['id_categoria'] ?? 0);

// 3.2 Consulta de Turmas da Nova Edição
$res = httpReq("$baseUrl/api/turmas.php?id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
$turmas = $res['json'] ?? [];
testAssert("Consulta de turmas criadas automaticamente (esperado 7)", count($turmas) === 7, $res['body']);
$turmaTesteId = (int) ($turmas[0]['id_turma'] ?? 0);

// 3.3 Consulta de Modalidades da Nova Edição
$res = httpReq("$baseUrl/api/modalidades.php?id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
$modalidades = $res['json'] ?? [];
testAssert("Consulta de modalidades criadas automaticamente (esperado >= 10)", count($modalidades) >= 10, $res['body']);
$modMataMataId = (int) ($modalidades[0]['id_modalidade'] ?? 0);

// 3.4 Consulta de Equipes Padrão
$res = httpReq("$baseUrl/api/equipes.php?id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
$equipes = $res['json'] ?? [];
testAssert("Consulta de equipes padrão geradas (esperado >= 35)", count($equipes) >= 35, $res['body']);


// =========================================================================
// SUITE 4: Upload e Importação de Alunos via PDF
// =========================================================================
echo "\n>> SUITE 4: Importação e Gestão de Alunos\n";

// 4.1 Upload do PDF para a Turma Teste
$caminhoPdf = file_exists(dirname(__DIR__) . '/docs/lista_alunos/6EFB.pdf')
    ? dirname(__DIR__) . '/docs/lista_alunos/6EFB.pdf'
    : 'C:/xampp/htdocs/SGI/docs/lista_alunos/6EFB.pdf';

if (file_exists($caminhoPdf) && $turmaTesteId > 0) {
    $cfile = new CURLFile($caminhoPdf, 'application/pdf', '6EFB.pdf');
    $postFields = [
        'pdf_arquivo' => $cfile,
        'id_turma' => (string) $turmaTesteId,
        'id_interclasse' => (string) $novoIdInterclasse
    ];
    $res = httpReq("$baseUrl/api/upload_turma_pdf.php", 'POST', $postFields, $cookieJarAdmin);
    testAssert("Upload e extração automática de alunos via PDF", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);

    // 4.2 Listar alunos importados na turma
    $res = httpReq("$baseUrl/api/usuarios.php?acao=listar_competidores&id_turma=$turmaTesteId&id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
    $alunos = $res['json']['competidores'] ?? [];
    testAssert("Listagem de competidores cadastrados na turma (esperado > 0)", count($alunos) > 0, $res['body']);
    $primeiroAlunoRm = $alunos[0]['matricula_usuario'] ?? '';
    $primeiroAlunoId = (int) ($alunos[0]['id_usuario'] ?? 0);
} else {
    testAssert("Upload de PDF para a turma", false, "Arquivo 6EFB.pdf não encontrado em docs/lista_alunos/");
}


// =========================================================================
// SUITE 5: Locais e Agendamento de Jogos (Agenda & Conflitos)
// =========================================================================
echo "\n>> SUITE 5: Locais, Jogos e Validação de Conflitos\n";

// 5.1 Criar Local de Jogo
$res = httpReq("$baseUrl/api/locais.php", 'POST', [
    'nome_local' => 'Quadra Poliesportiva Principal',
    'disponivel_local' => '1',
    'carga_local' => 5,
    'interclasses_id_interclasse' => $novoIdInterclasse
], $cookieJarAdmin, ['Content-Type' => 'application/json']);
testAssert("Cadastro de local de jogo (Quadra)", $res['code'] === 201 && ($res['json']['success'] ?? false) === true, $res['body']);
$idLocal = (int) ($res['json']['id_local'] ?? 0);

// 5.2 Agendar Jogo
$hoje = date('Y-m-d');
$res = httpReq("$baseUrl/api/jogos.php", 'POST', [
    'nome_jogo' => 'MM:4:0:1',
    'data_jogo' => $hoje,
    'inicio_jogo' => '08:00',
    'termino_jogo' => '08:45',
    'status_jogo' => 'Agendado',
    'duracao_jogo' => 2400,
    'modalidades_id_modalidade' => $modMataMataId,
    'locais_id_local' => $idLocal,
    'equipes' => [
        ['id_equipe' => (int)$equipes[0]['id_equipe']],
        ['id_equipe' => (int)$equipes[1]['id_equipe']]
    ]
], $cookieJarAdmin, ['Content-Type' => 'application/json']);
$idJogoCriado = (int) ($res['json']['id_jogo'] ?? $res['json']['id'] ?? 0);
testAssert("Agendamento de partida (MM:4:0:1) com duas equipes", in_array($res['code'], [200, 201], true) && ($res['json']['success'] ?? false) === true && $idJogoCriado > 0, $res['body']);

// 5.3 Validação de Conflito de Horário no Mesmo Local
$resConflito = httpReq("$baseUrl/api/jogos.php", 'POST', [
    'nome_jogo' => 'MM:4:1:2',
    'data_jogo' => $hoje,
    'inicio_jogo' => '08:15',
    'termino_jogo' => '09:00',
    'status_jogo' => 'Agendado',
    'modalidades_id_modalidade' => $modMataMataId,
    'locais_id_local' => $idLocal,
    'equipes' => [
        ['id_equipe' => (int)$equipes[2]['id_equipe']],
        ['id_equipe' => (int)$equipes[3]['id_equipe']]
    ]
], $cookieJarAdmin, ['Content-Type' => 'application/json']);
testAssert("Detecção e bloqueio de conflito de horário no mesmo local", $resConflito['code'] === 400 || ($resConflito['json']['success'] ?? true) === false, $resConflito['body']);


// =========================================================================
// SUITE 6: Operação de Jogo pelo Mesário e Avanço da Árvore
// =========================================================================
echo "\n>> SUITE 6: Operação de Mesa, Placar, Artilharia e Avanço de Chaves\n";

if ($idJogoCriado > 0) {
    // 6.1 Mesário lança gol na artilharia
    if (isset($primeiroAlunoId) && $primeiroAlunoId > 0) {
        $res = httpReq("$baseUrl/api/artilheiro.php", 'POST', [
            'usuarios_id_usuario' => $primeiroAlunoId,
            'jogos_id_jogo' => $idJogoCriado,
            'num_gol' => 2
        ], $cookieJarMesario, ['Content-Type' => 'application/json']);
        testAssert("Mesário lança gols do artilheiro", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);
    }

    // 6.2 Mesário finaliza o jogo com placar
    $res = httpReq("$baseUrl/api/lancar_resultado.php", 'POST', [
        'id_jogo' => $idJogoCriado,
        'nome_jogo' => 'MM:4:0:1',
        'id_modalidade' => $modMataMataId,
        'resultados' => [
            ['id_equipe' => (int)$equipes[0]['id_equipe'], 'gols' => 3],
            ['id_equipe' => (int)$equipes[1]['id_equipe'], 'gols' => 1]
        ]
    ], $cookieJarMesario, ['Content-Type' => 'application/json']);
    testAssert("Mesário conclui jogo com placar (3 x 1) e avança chave", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);

    // 6.3 Teste de Resolução de ID Temporário Offline (id_jogo <= 0)
    $resOffline = httpReq("$baseUrl/api/lancar_resultado.php", 'POST', [
        'id_jogo' => -99,
        'nome_jogo' => 'MM:4:0:1',
        'id_modalidade' => $modMataMataId,
        'resultados' => [
            ['id_equipe' => (int)$equipes[0]['id_equipe'], 'gols' => 4],
            ['id_equipe' => (int)$equipes[1]['id_equipe'], 'gols' => 2]
        ]
    ], $cookieJarMesario, ['Content-Type' => 'application/json']);
    testAssert("Sincronização de jogo gerado offline com ID provisório negativo", $resOffline['code'] === 200 && ($resOffline['json']['success'] ?? false) === true, $resOffline['body']);
}


// =========================================================================
// SUITE 7: Ocorrências Disciplinares, Arrecadação e Ranking
// =========================================================================
echo "\n>> SUITE 7: Ocorrências Disciplinares, Arrecadação e Cálculo de Ranking\n";

// 7.1 Lançar Ocorrência de Turma (Desconto de Pontos)
$res = httpReq("$baseUrl/api/ocorrencias_turmas.php", 'POST', [
    'turmas_id_turma' => $turmaTesteId,
    'interclasses_id_interclasse' => $novoIdInterclasse,
    'titulo_ocorrencia' => 'Atraso geral da torcida',
    'descricao_ocorrencia' => 'Turma não compareceu no horário estabelecido.',
    'pontos_descontados' => 5,
    'data_ocorrencia' => date('Y-m-d')
], $cookieJarAdmin, ['Content-Type' => 'application/json']);
testAssert("Lançamento de ocorrência disciplinar na turma (-5 pontos)", $res['code'] === 200 || ($res['json']['success'] ?? false) === true, $res['body']);

// 7.2 Lançar Arrecadação de Itens/Alimentos
$res = httpReq("$baseUrl/api/arrecadacao.php", 'POST', [
    'id_interclasse' => $novoIdInterclasse,
    'arrecadacoes' => [
        ['id_turma' => $turmaTesteId, 'quantidade' => 50]
    ]
], $cookieJarAdmin, ['Content-Type' => 'application/json']);
testAssert("Lançamento em lote de arrecadação solidária (50 itens)", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);

// 7.3 Consulta do Ranking Geral com Integração de Pontos
$res = httpReq("$baseUrl/api/ranking.php?id_interclasse=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
$ranking = $res['json'] ?? [];
testAssert("Cálculo e retorno do ranking geral consolidado", $res['code'] === 200 && is_array($ranking) && count($ranking) > 0, $res['body']);


// =========================================================================
// SUITE 8: Portal do Aluno (Inscrições e Termos)
// =========================================================================
echo "\n>> SUITE 8: Portal do Aluno (Termos de Aceite e Inscrições)\n";

// 8.1 Aluno Aceita Termo de Participação
$res = httpReq("$baseUrl/api/concordarTermos.php", 'POST', [], $cookieJarAluno);
testAssert("Aluno aceita termo de participação esportiva", $res['code'] === 200 && ($res['json']['success'] ?? false) === true, $res['body']);

// 8.2 Consulta de Termos
$res = httpReq("$baseUrl/api/concordarTermos.php", 'GET', null, $cookieJarAluno);
testAssert("Consulta de status dos termos pelo aluno (termo_aceito: true)", $res['code'] === 200 && ($res['json']['termo_aceito'] ?? false) === true, $res['body']);


// =========================================================================
// SUITE 9: Integridade de Rotas e Páginas Frontend
// =========================================================================
echo "\n>> SUITE 9: Renderização e Integridade de Todas as Páginas HTML/PHP\n";

$paginasStaff = [
    'dashboard.php',
    'edicao_agenda.php',
    'edicao_equipes.php',
    'edicao_arrecadacao.php',
    'edicao_categorias.php',
    'edicao_locais.php',
    'edicao_modalidades.php',
    'chaveamento_arvore.php',
    'ocorrencias.php',
    'ranking.php',
    'turmas.php',
    'turma_alunos.php',
    'perfil.php',
    'home.php'
];

foreach ($paginasStaff as $p) {
    $res = httpReq("$baseUrl/views/src/pages/$p?id=$novoIdInterclasse", 'GET', null, $cookieJarAdmin);
    testAssert("Renderização da tela staff [$p] (HTTP 200)", $res['code'] === 200, "Código HTTP: {$res['code']}");
}

$paginasAluno = [
    'home.php',
    'modalidade.php',
    'jogos.php',
    'termos.php',
    'perfil.php'
];

foreach ($paginasAluno as $p) {
    $res = httpReq("$baseUrl/views/src/pages/alunos/$p", 'GET', null, $cookieJarAluno);
    testAssert("Renderização da tela do aluno [$p] (HTTP 200)", $res['code'] === 200, "Código HTTP: {$res['code']}");
}

// Limpeza de cookies de teste
@unlink($cookieJarAdmin);
@unlink($cookieJarColab);
@unlink($cookieJarMesario);
@unlink($cookieJarAluno);

echo "\n========================================================\n";
echo " RESULTADO FINAL DA AUDITORIA:\n";
echo " Total de Testes Executados: $totalTests\n";
echo " Aprovados: $passedTests\n";
echo " Falhas:    $failedTests\n";
echo "========================================================\n";

if ($failedTests > 0) {
    echo "\nDetalhes das falhas:\n";
    foreach ($failures as $f) {
        echo " - $f\n";
    }
    exit(1);
} else {
    echo "\n>>> TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! <<<\n";
    exit(0);
}
