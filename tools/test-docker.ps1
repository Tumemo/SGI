[CmdletBinding()]
param(
    [ValidateSet('mariadb', 'mysql')]
    [string] $Database = 'mariadb',
    [ValidateSet('8.2', '8.4')]
    [string] $PhpVersion = '8.4',
    [switch] $SkipQuality,
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
$runId = ('{0:yyyyMMdd_HHmmss}_{1}' -f (Get-Date).ToUniversalTime(), ([guid]::NewGuid().ToString('N').Substring(0, 6))).ToLowerInvariant()
$projectName = "sgi-test-$($runId.Replace('_', '-'))"
$databaseName = "sgi_test_$($runId.Replace('_', ''))"
$resultsDirectory = Join-Path $root "test-results\docker-$runId"
$browserResultsDirectory = Join-Path $root "tests\browser\test-results\docker-$runId"
$browserReportDirectory = Join-Path $root "tests\browser\playwright-report\docker-$runId"
$portProbe = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, 0)
try {
    $portProbe.Start()
    $hostPort = ([System.Net.IPEndPoint] $portProbe.LocalEndpoint).Port
} finally {
    $portProbe.Stop()
}
foreach ($directory in @($resultsDirectory, $browserResultsDirectory, $browserReportDirectory)) {
    New-Item -ItemType Directory -Force -Path $directory | Out-Null
}
$composeArguments = @('--project-name', $projectName, '-f', $composeFile)
$databaseImage = if ($Database -eq 'mysql') { 'mysql:8.4' } else { 'mariadb:10.11' }

$previousEnvironment = @{}
foreach ($name in @('COMPOSE_PROJECT_NAME', 'SGI_DB_IMAGE', 'SGI_PHP_VERSION', 'SGI_DB_PASSWORD', 'SGI_TEST_DB_NAME', 'SGI_TEST_RUN_ID', 'SGI_TEST_HOST_PORT', 'SGI_TEST_RESULTS_DIR', 'SGI_TEST_BROWSER_RESULTS_DIR', 'SGI_TEST_BROWSER_REPORT_DIR')) {
    $previousEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

$env:COMPOSE_PROJECT_NAME = $projectName
$env:SGI_DB_IMAGE = $databaseImage
$env:SGI_PHP_VERSION = $PhpVersion
$env:SGI_DB_PASSWORD = 'sgi-test-only'
$env:SGI_TEST_DB_NAME = $databaseName
$env:SGI_TEST_RUN_ID = $runId
$env:SGI_TEST_HOST_PORT = [string] $hostPort
$env:SGI_TEST_RESULTS_DIR = $resultsDirectory
$env:SGI_TEST_BROWSER_RESULTS_DIR = $browserResultsDirectory
$env:SGI_TEST_BROWSER_REPORT_DIR = $browserReportDirectory

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
    $buildServices = @('app')
    if (-not $SkipBrowser -or $IncludeVisual) {
        $buildServices += 'browser'
    }
    Invoke-Compose (@('build') + $buildServices)

    $started = $true
    if (-not $SkipQuality) {
        Write-Host 'Executando qualidade PHP e JavaScript...'
        Invoke-Compose @('run', '--rm', '--no-deps', 'quality')
    }

    Invoke-Compose @('up', '-d', '--wait', 'db', 'app')

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
        & docker compose @composeArguments logs --no-color app db | Set-Content -LiteralPath (Join-Path $resultsDirectory 'docker-compose.log')
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
