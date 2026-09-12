function Invoke-TestLocalCleanupActions {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory)]
        [object[]] $Actions
    )

    foreach ($action in $Actions) {
        try {
            & $action.Run
        } catch {
            $name = [string] $action.Name
            $exceptionType = $_.Exception.GetType().Name
            Write-Warning "Falha na etapa de cleanup '$name' ($exceptionType); as demais etapas continuarão."
        }
    }
}
