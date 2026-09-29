# Arranca el worker del agente comentarista de Deltha (pide hilos a deltha.eu, los pasa por el modelo local y comenta).
# Uso:  powershell -ExecutionPolicy Bypass -File .\comentarista.ps1                    (bucle)
#       powershell -ExecutionPolicy Bypass -File .\comentarista.ps1 --seco             (bucle sin publicar)
#       powershell -ExecutionPolicy Bypass -File .\comentarista.ps1 --prueba "https://..."
#       powershell -ExecutionPolicy Bypass -File .\comentarista.ps1 --prueba-hilos
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$candidatos = @(
    (Join-Path $env:USERPROFILE "Bonsai-demo\.venv\Scripts\python.exe"),
    "py", "python"
)
$py = $null
foreach ($c in $candidatos) {
    if ($c -like "*.exe") { if (Test-Path $c) { $py = $c; break } }
    elseif (Get-Command $c -ErrorAction SilentlyContinue) { $py = $c; break }
}
if (-not $py) { Write-Host "[ERR] No encuentro Python (ni el del .venv de Bonsai-demo)." -ForegroundColor Red; exit 1 }
$env:PYTHONIOENCODING = "utf-8"
& $py (Join-Path $here "comentarista.py") @args
exit $LASTEXITCODE
