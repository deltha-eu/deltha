# Arranca el lector local de feeds de Deltha (descarga desde este PC los feeds que la web de la fuente no deja leer al servidor).
# No usa el modelo de IA: no hace falta que llama-server este en marcha. No duplica un lector ya en marcha.
# Uso:  powershell -ExecutionPolicy Bypass -File .\lector.ps1                 (bucle)
#       powershell -ExecutionPolicy Bypass -File .\lector.ps1 --una-vez --todas   (una pasada leyendo todas ya)
#       powershell -ExecutionPolicy Bypass -File .\lector.ps1 --prueba "https://politico.es/feed/"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$host.UI.RawUI.WindowTitle = "Deltha · lector de feeds (worker)"
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
if (-not ($args -contains "--prueba") -and -not ($args -contains "--una-vez")) {
    $ya = @(Get-CimInstance Win32_Process -Filter "Name like 'python%'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*lector.py*' -and $_.CommandLine -notlike '*--prueba*' -and $_.CommandLine -notlike '*--una-vez*' })
    if ($ya.Count -gt 0) { Write-Host "Ya hay un lector en marcha (PID $($ya[0].ProcessId)); no abro otro."; exit 0 }
}
$env:PYTHONIOENCODING = "utf-8"
& $py (Join-Path $here "lector.py") @args
exit $LASTEXITCODE
