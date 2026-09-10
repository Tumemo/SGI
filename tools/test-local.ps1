[CmdletBinding()]
param(
    [ValidateSet('quality', 'integration', 'browser', 'visual', 'all')]
    [string] $Suite = 'all',
    [ValidateSet('local', 'docker')]
    [string] $DatabaseBackend = 'local',
    [ValidateSet('mariadb', 'mysql')]
    [string] $Database = 'mariadb',
    [string] $PhpPath = '',
    [int] $Port = 0,
    [int] $DatabasePort = 0,
    [string] $DatabaseHost = '',
    [string] $DatabaseUser = '',
    [string] $DatabasePassword = '',
    [switch] $IncludeVisual,
    [switch] $Keep
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$requiresDatabase = $Suite -in @('integration', 'browser', 'visual', 'all')
$needsBrowser = $Suite -in @('browser', 'all') -or $IncludeVisual -or $Suite -eq 'visual'
$runId = ('{0:yyyyMMdd_HHmmss}_{1}' -f (Get-Date), ([guid]::NewGuid().ToString('N').Substring(0, 6))).ToLowerInvariant()
$databaseName = "sgi_test_local_$($runId.Replace('_', ''))"
$runRoot = Join-Path $root "test-results\local-$runId"
$serverProcess = $null
$dockerContainer = $null
$lockStream = $null
$originalEnvironment = @{}
$originalEnvironment['PATH'] = [Environment]::GetEnvironmentVariable('PATH', 'Process')
$startedResources = $false
$exitCode = 0
New-Item -ItemType Directory -Force -Path $runRoot | Out-Null

function Get-CommandPath {
    param([Parameter(Mandatory)][string[]] $Names)

    foreach ($name in $Names) {
        $command = Get-Command $name -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($null -ne $command) {
            return $command.Source
        }
    }
    return $null
}

function Resolve-Php {
    if ($PhpPath -ne '') {
        $candidate = (Resolve-Path -LiteralPath $PhpPath -ErrorAction Stop).Path
        return $candidate
    }
    if ($env:SGI_PHP_PATH -and (Test-Path -LiteralPath $env:SGI_PHP_PATH)) {
        return (Resolve-Path -LiteralPath $env:SGI_PHP_PATH).Path
    }
    if (Test-Path -LiteralPath 'C:\xampp\php\php.exe') {
        return 'C:\xampp\php\php.exe'
    }
    $candidate = Get-CommandPath @('php.exe', 'php')
    if ($candidate) {
        return $candidate
    }
    throw 'PHP não foi encontrado. Informe -PhpPath ou defina SGI_PHP_PATH.'
}

function Invoke-Checked {
    param(
        [Parameter(Mandatory)][string] $FilePath,
        [Parameter()][string[]] $Arguments = @(),
        [Parameter(Mandatory)][string] $Description
    )

    Write-Host "`n[$Description]"
    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "$Description falhou (código $LASTEXITCODE)."
    }
}

function Invoke-Composer {
    param([Parameter()][string[]] $Arguments = @(), [Parameter(Mandatory)][string] $Description)

    if ($script:composerPhar) {
        Invoke-Checked -FilePath $script:php -Arguments (@($script:composerPhar) + $Arguments) -Description $Description
    } else {
        Invoke-Checked -FilePath $script:composer -Arguments $Arguments -Description $Description
    }
}

function Set-TestEnvironment {
    param([Parameter(Mandatory)][hashtable] $Values)

    foreach ($name in $Values.Keys) {
        if (-not $originalEnvironment.ContainsKey($name)) {
            $originalEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
        }
        Set-Item "Env:$name" ([string] $Values[$name])
    }
}

function Restore-TestEnvironment {
    foreach ($name in $originalEnvironment.Keys) {
        if ($null -eq $originalEnvironment[$name]) {
            Remove-Item "Env:$name" -ErrorAction SilentlyContinue
        } else {
            Set-Item "Env:$name" $originalEnvironment[$name]
        }
    }
}

function Get-FreePort {
    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, 0)
    try {
        $listener.Start()
        return ([System.Net.IPEndPoint] $listener.LocalEndpoint).Port
    } finally {
        $listener.Stop()
    }
}

function Wait-TcpPort {
    param([Parameter(Mandatory)][string] $Host, [Parameter(Mandatory)][int] $PortNumber, [int] $TimeoutSeconds = 60)

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        try {
            $client = [System.Net.Sockets.TcpClient]::new()
            $task = $client.ConnectAsync($Host, $PortNumber)
            if ($task.Wait(500) -and $client.Connected) {
                $client.Dispose()
                return
            }
            $client.Dispose()
        } catch {
            # O serviço ainda pode estar iniciando.
        }
        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)
    throw "A porta $Host`:$PortNumber não ficou disponível em $TimeoutSeconds segundos."
}

function Wait-Health {
    param([Parameter(Mandatory)][string] $Url, [Parameter(Mandatory)][string] $ExpectedDatabase, [int] $TimeoutSeconds = 60)

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        try {
            $response = Invoke-WebRequest -UseBasicParsing -Uri "$Url/api/v1/health" -TimeoutSec 3
            $json = $response.Content | ConvertFrom-Json
            if ($json.status -eq 'ok' -and $json.test_environment.database -eq $ExpectedDatabase) {
                return
            }
            throw 'O health endpoint não confirmou o ambiente de teste esperado.'
        } catch {
            if ((Get-Date) -ge $deadline) {
                throw "O servidor de teste não respondeu corretamente em $TimeoutSeconds segundos: $($_.Exception.Message)"
            }
            Start-Sleep -Milliseconds 500
        }
    } while ((Get-Date) -lt $deadline)
}

function Acquire-TestLock {
    $lockPath = Join-Path $root 'test-results\.test-local.lock'
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $lockPath) | Out-Null
    $deadline = (Get-Date).AddSeconds(30)
    do {
        try {
            $script:lockStream = [System.IO.File]::Open($lockPath, [System.IO.FileMode]::OpenOrCreate, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
            return
        } catch {
            Start-Sleep -Milliseconds 250
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Outra execução local de testes já está usando o banco descartável. Aguarde o término ou finalize a execução anterior.'
}

function Resolve-SqlClient {
    param([Parameter(Mandatory)][string[]] $Names)

    $configured = if ($Names[0] -match 'dump') { $env:SGI_MYSQLDUMP_PATH } else { $env:SGI_MYSQL_PATH }
    if ($configured -and (Test-Path -LiteralPath $configured)) { return (Resolve-Path -LiteralPath $configured).Path }
    $path = Get-CommandPath $Names
    if ($path) { return $path }
    $xamppCandidates = if ($Names[0] -match 'dump') {
        @('C:\xampp\mysql\bin\mysqldump.exe', 'C:\xampp\mysql\bin\mariadb-dump.exe')
    } else {
        @('C:\xampp\mysql\bin\mysql.exe', 'C:\xampp\mysql\bin\mariadb.exe')
    }
    foreach ($candidate in $xamppCandidates) {
        if (Test-Path -LiteralPath $candidate) { return $candidate }
    }
    return $null
}

function Check-Prerequisites {
    $script:php = Resolve-Php
    $env:PATH = "$(Split-Path -Parent $script:php);$($env:PATH)"
    $phpVersion = (& $script:php -r "echo PHP_VERSION;").Trim()
    if ($LASTEXITCODE -ne 0) { throw "Não foi possível executar o PHP selecionado: $script:php" }
    $modules = (& $script:php -m) -join "`n"
    foreach ($extension in @('mysqli', 'mbstring', 'fileinfo', 'curl', 'dom')) {
        if ($modules -notmatch "(?m)^$extension$") {
            throw "O PHP $phpVersion não tem a extensão '$extension'. Use o PHP do XAMPP ou informe outro -PhpPath."
        }
    }
    Write-Host "PHP ${phpVersion}: $script:php"

    $script:composer = Get-CommandPath @('composer.bat', 'composer')
    $script:composerPhar = $null
    if ($script:composer) {
        $adjacentPhar = Join-Path (Split-Path -Parent $script:composer) 'composer.phar'
        if (Test-Path -LiteralPath $adjacentPhar) { $script:composerPhar = $adjacentPhar }
    }
    $script:npm = Get-CommandPath @('npm.cmd', 'npm')
    if ($Suite -in @('quality', 'all') -and -not $script:composer) { throw 'Composer não foi encontrado no PATH.' }
    if (($Suite -in @('quality', 'all')) -and -not $script:npm) { throw 'npm não foi encontrado no PATH.' }
    if ($Suite -in @('quality', 'all') -and -not (Test-Path -LiteralPath (Join-Path $root 'vendor/autoload.php'))) {
        throw 'Dependências PHP não encontradas. Execute composer install antes dos testes.'
    }
    if ($needsBrowser -and -not (Test-Path -LiteralPath (Join-Path $root 'tests/browser/node_modules'))) {
        throw 'Dependências do navegador não encontradas. Execute npm ci --prefix tests/browser.'
    }
    if ($requiresDatabase) {
        $script:mysqlClient = Resolve-SqlClient @('mysql.exe', 'mysql', 'mariadb.exe', 'mariadb')
        $script:mysqlDump = Resolve-SqlClient @('mysqldump.exe', 'mysqldump', 'mariadb-dump.exe', 'mariadb-dump')
        if (-not $script:mysqlClient -or -not $script:mysqlDump) {
            throw 'Os clientes mysql/mysqldump são necessários para os testes de recuperação. Instale-os ou defina SGI_MYSQL_PATH e SGI_MYSQLDUMP_PATH.'
        }
    }
}

function Start-DockerDatabase {
    $docker = Get-CommandPath @('docker.exe', 'docker')
    if (-not $docker) { throw 'Docker não foi encontrado no PATH.' }
    $image = if ($Database -eq 'mysql') { 'mysql:8.4' } else { 'mariadb:10.11' }
    if ($script:dbPassword -eq '') { $script:dbPassword = 'sgi-test-only' }
    $script:databasePort = if ($DatabasePort -gt 0) { $DatabasePort } else { Get-FreePort }
    $containerName = "sgi-test-db-$($runId.Replace('_', '-'))"
    $args = @('run', '--detach', '--name', $containerName, '--tmpfs', '/var/lib/mysql', '-e', "MYSQL_ROOT_PASSWORD=$script:dbPassword", '-e', "MYSQL_DATABASE=$databaseName", '-e', "MARIADB_ROOT_PASSWORD=$script:dbPassword", '-e', "MARIADB_DATABASE=$databaseName", '-p', "127.0.0.1:$($script:databasePort):3306", $image)
    $script:dockerContainer = (& $docker @args).Trim()
    if ($LASTEXITCODE -ne 0 -or -not $script:dockerContainer) { throw 'Não foi possível iniciar o banco de testes no Docker.' }
    Wait-TcpPort -Host '127.0.0.1' -PortNumber $script:databasePort
}

function Start-TestServer {
    $script:port = if ($Port -gt 0) { $Port } else { Get-FreePort }
    $script:baseUrl = "http://127.0.0.1:$($script:port)"
    $sessionPath = Join-Path $runRoot 'sessions'
    $uploadTmp = Join-Path $runRoot 'upload-tmp'
    $uploads = Join-Path $runRoot 'uploads'
    $importPath = Join-Path $runRoot 'imports'
    foreach ($directory in @($runRoot, $sessionPath, $uploadTmp, $uploads, (Join-Path $uploads 'regulamentos'), (Join-Path $uploads 'fotos'), $importPath)) {
        New-Item -ItemType Directory -Force -Path $directory | Out-Null
    }

    $values = @{
        SGI_APP_ENV = 'test'
        SGI_APP_DEBUG = '0'
        SGI_DB_HOST = $script:dbHost
        SGI_DB_PORT = [string] $script:databasePort
        SGI_DB_NAME = $databaseName
        SGI_TEST_DB_NAME = $databaseName
        SGI_DB_USER = $script:dbUser
        SGI_DB_PASSWORD = $script:dbPassword
        SGI_TEST_BASE_URL = $script:baseUrl
        SGI_BASE_URL = "$($script:baseUrl)/"
        SGI_UPLOAD_DIR = $uploads
        SGI_REGULAMENTOS_DIR = Join-Path $uploads 'regulamentos'
        SGI_FOTOS_DIR = Join-Path $uploads 'fotos'
        SGI_IMPORT_DIR = $importPath
        SGI_SESSION_DIR = $sessionPath
        SGI_MYSQL_PATH = $script:mysqlClient
        SGI_MYSQLDUMP_PATH = $script:mysqlDump
        SGI_TEST_RUN_ID = $runId
        SGI_BROWSER_OUTPUT_DIR = Join-Path $runRoot 'browser'
        SGI_BROWSER_REPORT_DIR = Join-Path $runRoot 'playwright-report'
    }
    Set-TestEnvironment $values
    $stdout = Join-Path $runRoot 'server.stdout.log'
    $stderr = Join-Path $runRoot 'server.stderr.log'
    $args = @('-d', "session.save_path=$sessionPath", '-d', "upload_tmp_dir=$uploadTmp", '-S', "127.0.0.1:$($script:port)", '-t', (Join-Path $root 'public'), (Join-Path $root 'public/index.php'))
    $script:serverProcess = Start-Process -FilePath $script:php -ArgumentList $args -WorkingDirectory $root -RedirectStandardOutput $stdout -RedirectStandardError $stderr -WindowStyle Hidden -PassThru
    $script:startedResources = $true
    Wait-Health -Url $script:baseUrl -ExpectedDatabase $databaseName
}

function Invoke-Quality {
    Invoke-Composer -Arguments @('validate', '--no-check-publish') -Description 'Validação do Composer'
    Invoke-Composer -Arguments @('verify') -Description 'Qualidade PHP'
    Invoke-Checked -FilePath $script:npm -Arguments @('run', 'build') -Description 'Build de assets'
    Invoke-Checked -FilePath $script:npm -Arguments @('run', 'check') -Description 'Checks JavaScript'
    Invoke-Checked -FilePath $script:npm -Arguments @('test') -Description 'Testes JavaScript'
}

function Invoke-Integration {
    Invoke-Checked -FilePath $script:php -Arguments @('tests/run_all.php') -Description 'Integração HTTP, banco e recuperação'
}

function Invoke-Browser {
    $arguments = @('--prefix', (Join-Path $root 'tests/browser'), 'test', '--', '--grep-invert', 'contrato visual do acesso')
    Invoke-Checked -FilePath $script:npm -Arguments $arguments -Description 'Testes de navegador online/offline'
}

function Invoke-Visual {
    $arguments = @('--prefix', (Join-Path $root 'tests/browser'), 'test', '--', 'visual-contract.spec.cjs')
    Invoke-Checked -FilePath $script:npm -Arguments $arguments -Description 'Contrato visual'
}

function Remove-LocalTestDatabase {
    if ($DatabaseBackend -ne 'local' -or $Keep -or -not $script:mysqlClient) { return }
    $query = "DROP DATABASE IF EXISTS ``$databaseName``"
    & $script:mysqlClient "--host=$script:dbHost" "--port=$script:databasePort" "--user=$script:dbUser" "--password=$script:dbPassword" '-e' $query 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Não foi possível remover a base temporária '$databaseName'. Remova-a manualmente após verificar que ela pertence a esta execução."
    }
}

try {
    Check-Prerequisites
    if ($requiresDatabase) {
        Acquire-TestLock
        $script:dbUser = if ($DatabaseUser) { $DatabaseUser } elseif ($env:SGI_TEST_DB_USER) { $env:SGI_TEST_DB_USER } else { 'root' }
        $script:dbPassword = if ($DatabasePassword) { $DatabasePassword } elseif ($null -ne $env:SGI_TEST_DB_PASSWORD) { $env:SGI_TEST_DB_PASSWORD } else { '' }
        if ($DatabaseBackend -eq 'docker') {
            Start-DockerDatabase
            $script:dbHost = '127.0.0.1'
        } else {
            $script:dbHost = if ($DatabaseHost) { $DatabaseHost } elseif ($env:SGI_TEST_DB_HOST) { $env:SGI_TEST_DB_HOST } else { '127.0.0.1' }
            $script:databasePort = if ($DatabasePort -gt 0) { $DatabasePort } elseif ($env:SGI_TEST_DB_PORT) { [int] $env:SGI_TEST_DB_PORT } else { 3306 }
        }
    }

    if ($Suite -eq 'quality') {
        Invoke-Quality
    } elseif ($requiresDatabase) {
        Start-TestServer
        if ($Suite -eq 'all') { Invoke-Quality }
        Invoke-Integration
        if ($Suite -in @('browser', 'all')) { Invoke-Browser }
        if ($IncludeVisual -or $Suite -eq 'visual') { Invoke-Visual }
    }
    Write-Host "`nTestes concluídos. Artefatos: $runRoot"
} catch {
    $exitCode = 1
    Write-Error $_
} finally {
    if ($script:serverProcess) {
        try { Stop-Process -Id $script:serverProcess.Id -Force -ErrorAction SilentlyContinue } catch { }
    }
    Remove-LocalTestDatabase
    if ($script:dockerContainer -and -not $Keep) {
        $docker = Get-CommandPath @('docker.exe', 'docker')
        if ($docker) { & $docker rm -f $script:dockerContainer | Out-Null }
    } elseif ($script:dockerContainer -and $Keep) {
        Write-Host "Banco Docker mantido: $script:dockerContainer"
    }
    if ($lockStream) { $lockStream.Dispose() }
    Restore-TestEnvironment
}

exit $exitCode
