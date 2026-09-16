$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'test-local-cleanup.ps1')

$script:cleanupOrder = [System.Collections.Generic.List[string]]::new()
$runnerExitCode = 0
$originalFailureMessage = ''
try {
    throw [System.InvalidOperationException]::new('synthetic quality failure')
} catch {
    $runnerExitCode = 1
    $originalFailureMessage = $_.Exception.Message
}
$actions = @(
    [pscustomobject]@{
        Name = 'container removal failure'
        Run = {
            $script:cleanupOrder.Add('database')
            throw [System.InvalidOperationException]::new('synthetic cleanup failure')
        }
    },
    [pscustomobject]@{
        Name = 'lock release'
        Run = { $script:cleanupOrder.Add('lock') }
    },
    [pscustomobject]@{
        Name = 'environment restore'
        Run = { $script:cleanupOrder.Add('environment') }
    }
)

$warnings = @(Invoke-TestLocalCleanupActions -Actions $actions 3>&1)
$warningText = $warnings | ForEach-Object { $_.ToString() }
if (($script:cleanupOrder -join ',') -ne 'database,lock,environment') {
    throw "Cleanup não executou todas as etapas na ordem esperada: $($script:cleanupOrder -join ',')."
}
if ($runnerExitCode -ne 1) {
    throw "O código de saída original foi alterado para $runnerExitCode."
}
if ($originalFailureMessage -ne 'synthetic quality failure') {
    throw 'A falha original da suíte foi substituída durante o cleanup.'
}
if (-not ($warningText -match 'container removal failure')) {
    throw 'A falha sintética de remoção do container não foi reportada.'
}

Write-Output 'Cleanup isolado: falha reportada, etapas seguintes executadas e código original preservado.'
