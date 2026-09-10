[CmdletBinding()]
param(
    [ValidateSet('quality', 'integration', 'browser', 'visual', 'all')]
    [string] $Suite = 'quality',
    [ValidateSet('local', 'docker')]
    [string] $DatabaseBackend = 'local',
    [ValidateSet('mariadb', 'mysql')]
    [string] $Database = 'mariadb',
    [ValidateRange(1, 10)]
    [int] $Runs = 3,
    [string] $PhpPath = ''
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runId = ('{0:yyyyMMdd_HHmmss}_{1}' -f (Get-Date), ([guid]::NewGuid().ToString('N').Substring(0, 6))).ToLowerInvariant()
$outputPath = Join-Path $root "test-results\benchmark-$runId.json"
$localRunner = Join-Path $PSScriptRoot 'test-local.ps1'
$results = [System.Collections.Generic.List[object]]::new()

New-Item -ItemType Directory -Force -Path (Split-Path -Parent $outputPath) | Out-Null

for ($run = 1; $run -le $Runs; $run++) {
    $started = Get-Date
    $arguments = @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $localRunner, '-Suite', $Suite, '-DatabaseBackend', $DatabaseBackend, '-Database', $Database)
    if ($PhpPath) { $arguments += @('-PhpPath', $PhpPath) }

    Write-Host "`nBenchmark $run/$Runs ($Suite, $DatabaseBackend)"
    & powershell @arguments 2>&1
    $status = $LASTEXITCODE
    $finished = Get-Date
    $results.Add([ordered]@{
        run = $run
        suite = $Suite
        database_backend = $DatabaseBackend
        database = $Database
        started_at = $started.ToUniversalTime().ToString('o')
        finished_at = $finished.ToUniversalTime().ToString('o')
        duration_seconds = [math]::Round(($finished - $started).TotalSeconds, 3)
        exit_code = $status
    })
}

$payload = [ordered]@{
    generated_at = (Get-Date).ToUniversalTime().ToString('o')
    repository = $root
    suite = $Suite
    database_backend = $DatabaseBackend
    runs = $results
    successful_runs = @($results | Where-Object { $_.exit_code -eq 0 }).Count
}
$payload | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $outputPath -Encoding utf8

$durations = @($results | Where-Object { $_.exit_code -eq 0 } | ForEach-Object { [double] $_.duration_seconds })
if ($durations.Count -gt 0) {
    $orderedDurations = $durations | Sort-Object
    $median = $orderedDurations[[math]::Floor(($orderedDurations.Count - 1) / 2)]
    Write-Host "`nMediana das execuções aprovadas: $median s"
}
Write-Host "Medições gravadas em $outputPath"

if ($payload.successful_runs -ne $Runs) { exit 1 }
exit 0
