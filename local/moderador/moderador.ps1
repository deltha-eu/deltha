# Arranca el worker del moderador de Deltha (pide trabajo a deltha.eu y lo pasa por el modelo local).
# Uso:  powershell -ExecutionPolicy Bypass -File .\moderador.ps1                 (bucle)
#       powershell -ExecutionPolicy Bypass -File .\moderador.ps1 --prueba "texto"
#       powershell -ExecutionPolicy Bypass -File .\moderador.ps1 --prueba-archivo muestras.txt
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
& $py (Join-Path $here "moderador.py") @args
exit $LASTEXITCODE
