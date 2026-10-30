# Arranca el worker generico de IA de Deltha (pide trabajo a deltha.eu y lo pasa por el modelo local).
# Uso:  powershell -ExecutionPolicy Bypass -File .\ia.ps1                      (bucle)
#       powershell -ExecutionPolicy Bypass -File .\ia.ps1 --tareas             (que tareas sirve Deltha)
#       powershell -ExecutionPolicy Bypass -File .\ia.ps1 --prueba moderacion "texto"
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
& $py (Join-Path $here "ia.py") @args
exit $LASTEXITCODE
