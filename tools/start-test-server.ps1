[CmdletBinding()]
param(
    [int] $Port = 8099,
    [string] $Database = 'sgi_test',
    [string] $PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'

if ($Database -notmatch '^(?i)(?:[a-z0-9_]*_)?(?:test|testing)(?:_[a-z0-9_]+)*$') {
    throw "O banco de teste precisa começar com 'test' ou 'testing'."
}

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runtime = Join-Path $root 'test-results'
$sessionPath = Join-Path $runtime 'sessions'
$uploadTmp = Join-Path $runtime 'upload-tmp'
$uploads = Join-Path $runtime 'uploads'
$regulamentos = Join-Path $uploads 'regulamentos'
$fotos = Join-Path $uploads 'fotos'

foreach ($directory in @($sessionPath, $uploadTmp, $uploads, $regulamentos, $fotos)) {
    New-Item -ItemType Directory -Force -Path $directory | Out-Null
}

$env:SGI_DB_NAME = $Database
$env:SGI_APP_ENV = 'test'
$env:SGI_UPLOAD_DIR = $uploads
$env:SGI_REGULAMENTOS_DIR = $regulamentos
$env:SGI_FOTOS_DIR = $fotos
$env:SGI_TEST_BASE_URL = "http://127.0.0.1:$Port"

Write-Host "Servidor SGI de testes em $($env:SGI_TEST_BASE_URL) usando o banco '$Database'."
Write-Host 'Mantenha esta janela aberta enquanto executa os testes de integração.'

& $PhpPath `
    -d "session.save_path=$sessionPath" `
    -d "upload_tmp_dir=$uploadTmp" `
    -S "127.0.0.1:$Port" `
    -t (Join-Path $root 'public') (Join-Path $root 'public/index.php')
