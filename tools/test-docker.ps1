[CmdletBinding()]
param(
    [ValidateSet('mariadb', 'mysql')]
    [string] $Database = 'mariadb',
    [ValidateSet('8.2', '8.4')]
    [string] $PhpVersion = '8.4',
    [switch] $IncludeVisual,
    [switch] $SkipBrowser,
    [switch] $Keep
)

$ErrorActionPreference = 'Stop'

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker não foi encontrado no PATH.'
}

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$composeFile = Join-Path $root 'compose.test.yml'
$composeArguments = @('-f', $composeFile)
$databaseImage = if ($Database -eq 'mysql') { 'mysql:8.4' } else { 'mariadb:10.11' }

$previousEnvironment = @{}
foreach ($name in @('SGI_DB_IMAGE', 'SGI_PHP_VERSION', 'SGI_DB_PASSWORD', 'SGI_TEST_DB_NAME')) {
    $previousEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

$env:SGI_DB_IMAGE = $databaseImage
$env:SGI_PHP_VERSION = $PhpVersion
$env:SGI_DB_PASSWORD = if ($env:SGI_DB_PASSWORD) { $env:SGI_DB_PASSWORD } else { 'sgi-test-only' }
$env:SGI_TEST_DB_NAME = if ($env:SGI_TEST_DB_NAME) { $env:SGI_TEST_DB_NAME } else { 'sgi_test' }

function Invoke-Compose {
    param([Parameter(Mandatory)][string[]] $Arguments)

    & docker compose @composeArguments @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Docker Compose falhou: docker compose $($Arguments -join ' ')"
    }
}

$started = $false
$exitCode = 0
try {
    Invoke-Compose @('config', '--quiet')
    Invoke-Compose @('build', 'app', 'browser')

    $started = $true
    Invoke-Compose @('up', '-d', '--wait', 'db', 'app')

    Write-Host 'Executando qualidade PHP e JavaScript...'
    Invoke-Compose @('run', '--rm', '--no-deps', 'quality')

    Write-Host 'Executando integração HTTP, banco e recuperação...'
    Invoke-Compose @('run', '--rm', '--no-deps', 'integration')

    if (-not $SkipBrowser) {
        Write-Host 'Executando testes de navegador online/offline...'
        Invoke-Compose @('run', '--rm', '--no-deps', 'browser')
    }

    if ($IncludeVisual) {
        Write-Host 'Executando contrato visual...'
        Invoke-Compose @('run', '--rm', '--no-deps', 'visual')
    }

    Write-Host 'Todos os serviços de teste concluíram com sucesso.'
} catch {
    $exitCode = 1
    Write-Error $_
} finally {
    if ($started -and -not $Keep) {
        & docker compose @composeArguments down --volumes --remove-orphans
        if ($LASTEXITCODE -ne 0 -and $exitCode -eq 0) {
            $exitCode = 1
            Write-Error 'Não foi possível remover completamente o ambiente Docker de teste.'
        }
    } elseif ($Keep) {
        Write-Host 'Ambiente mantido conforme solicitado (-Keep).'
    }

    foreach ($name in $previousEnvironment.Keys) {
        $value = $previousEnvironment[$name]
        if ($null -eq $value) {
            Remove-Item "Env:$name" -ErrorAction SilentlyContinue
        } else {
            Set-Item "Env:$name" $value
        }
    }
}

exit $exitCode
