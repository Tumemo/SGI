<?php

declare(strict_types=1);

/**
 * Ponto único de entrada HTTP do SGI.
 *
 * O DocumentRoot deve apontar para /public. O código legado continua
 * organizado em /api e /views, mas nenhum arquivo interno é exposto como
 * recurso público por acidente.
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
\App\Shared\Config\EnvLoader::load($projectRoot . DIRECTORY_SEPARATOR . '.env');

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$originalRequestPath = $requestPath;

// O projeto pode ser publicado em um subdiretório (por exemplo
// http://localhost/SGI/) ou em um virtual host na raiz. Remove apenas o
// prefixo conhecido do próprio front controller para manter ambos os modos.
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$publicMarker = strrpos($scriptName, '/public/index.php');
$basePath = '';
// Em servidores embutidos o PHP pode preencher SCRIPT_NAME com o próprio
// caminho solicitado (por exemplo, /views/index.php). Nesse caso não há
// prefixo de publicação para remover; só derivamos a base quando o script
// realmente foi reescrito para o front controller.
$scriptRepresentsFrontController = $scriptName !== $originalRequestPath;
if ($publicMarker !== false && $scriptRepresentsFrontController) {
    $basePath = substr($scriptName, 0, $publicMarker);
} elseif ($scriptRepresentsFrontController && str_ends_with($scriptName, '/index.php')) {
    $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
}
if ($basePath !== '' && $basePath !== '.' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

if ($requestPath === '' || $requestPath === '/') {
    $homePath = ($basePath !== '' ? rtrim($basePath, '/') . '/' : '/') . 'views/index.php';
    header('Location: ' . $homePath, true, 302);
    exit;
}

if (str_contains($requestPath, "\0") || str_contains($requestPath, '\\')) {
    http_response_code(400);
    exit('Requisição inválida.');
}

$path = ltrim($requestPath, '/');
$segments = explode('/', $path);
if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
    http_response_code(400);
    exit('Requisição inválida.');
}

/**
 * Resolve somente arquivos que permanecem dentro do diretório do projeto.
 */
$resolve = static function (string $relativePath, ?string $baseDirectory = null) use ($projectRoot): ?string {
    $baseDirectory ??= $projectRoot;
    $candidate = $baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $realPath = realpath($candidate);
    if ($realPath === false || !is_file($realPath)) {
        return null;
    }

    $root = realpath($baseDirectory);
    if ($root === false) {
        return null;
    }

    $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return str_starts_with($realPath, $rootPrefix) ? $realPath : null;
};

// Arquivos PHP são executados somente nas duas áreas de apresentação da
// aplicação. A lista explícita impede que helpers (auth.php, filtros.php e
// engines internos) sejam tratados como endpoints por engano.
$publicApiEndpoints = [
    'arrecadacao.php', 'artilheiro.php', 'categorias.php', 'chaveamento.php',
    'auth.php', 'classificacao.php', 'concordarTermos.php', 'CriarEquipes.php',
    'equipes.php', 'foto.php', 'historico_turma.php', 'inscricao.php',
    'interclasse.php', 'jogos.php', 'lancar_resultado.php', 'locais.php',
    'login.php', 'logout.php', 'modalidades.php', 'ocorrencias.php',
    'ocorrencias_turmas.php', 'partidas.php', 'pontuacaoInterclasse.php',
    'ranking.php', 'sincronizar_chaveamento.php',
    'tipoModalidade.php', 'trocar_senha.php', 'turmas.php',
    'upload_turma_pdf.php', 'usuarios.php',
];
$isPublicApi = str_starts_with($path, 'api/')
    && in_array(substr($path, 4), $publicApiEndpoints, true);

if ($isPublicApi || preg_match('/^views\/.+\.php$/D', $path) === 1) {
    $file = $resolve($path);
    if ($file === null) {
        http_response_code(404);
        exit('Recurso não encontrado.');
    }

    // Muitos scripts legados usam require relativo à própria pasta. Como o
    // front controller é executado a partir de /public, preservamos esse
    // contrato durante a inclusão sem reabrir qualquer caminho externo.
    $currentDirectory = getcwd();
    chdir(dirname($file));
    // O caminho absoluto evita que um arquivo chamado index.php seja
    // resolvido novamente para este próprio front controller pelo include_path.
    require $file;
    if (is_string($currentDirectory)) {
        chdir($currentDirectory);
    }
    exit;
}

// index.php é legado e existe apenas para manter a entrada principal clara.
if ($path === 'index.php') {
    $homePath = ($basePath !== '' ? rtrim($basePath, '/') . '/' : '/') . 'views/index.php';
    header('Location: ' . $homePath, true, 302);
    exit;
}

// Somente assets das áreas públicas do produto e uploads gerenciados podem
// ser lidos diretamente. O diretório storage continua privado.
if (preg_match('/^(?:views|uploads)\/.+/D', $path) !== 1) {
    http_response_code(404);
    exit('Recurso não encontrado.');
}

$file = $resolve($path);

// Os diretórios configuráveis podem ficar fora da árvore versionada
// (recomendado em produção). As URLs existentes continuam estáveis.
if ($file === null && str_starts_with($path, 'uploads/')) {
    $storageMappings = [
        'uploads/regulamentos' => \App\Shared\Storage\StoragePaths::regulamentos(),
        'uploads/fotosUsuarios' => \App\Shared\Storage\StoragePaths::fotosUsuarios(),
        'uploads/turmas' => \App\Shared\Storage\StoragePaths::turmaPdfs(),
    ];
    foreach ($storageMappings as $prefix => $directory) {
        if ($path === $prefix || !str_starts_with($path, $prefix . '/')) {
            continue;
        }
        $relative = substr($path, strlen($prefix) + 1);
        $file = $resolve($relative, $directory);
        if ($file !== null) {
            break;
        }
    }
}
if ($file === null) {
    http_response_code(404);
    exit('Recurso não encontrado.');
}

$extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
$contentTypes = [
    'css' => 'text/css; charset=UTF-8',
    'js' => 'text/javascript; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'pdf' => 'application/pdf',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'map' => 'application/json; charset=UTF-8',
];

if (!isset($contentTypes[$extension])) {
    http_response_code(404);
    exit('Recurso não encontrado.');
}

header('Content-Type: ' . $contentTypes[$extension]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
readfile($file);
