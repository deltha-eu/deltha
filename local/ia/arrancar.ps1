# Todo en uno: abre el modelo en otra ventana (minimizada) si no esta ya, y cuando responde arranca el worker generico en esta.
# El modelo lo carga local\moderador\arrancar-modelo.ps1 (lee motor y opciones de moderador.ini), igual que antes.
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar.ps1
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$mod = Join-Path (Split-Path -Parent $here) "moderador"
$host.UI.RawUI.WindowTitle = "Deltha · IA (worker)"
$up = $false
try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; $up = $true } catch {}
if (-not $up) {
    Start-Process powershell -WindowStyle Minimized -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-Command",
        "`$host.UI.RawUI.WindowTitle = 'Deltha · modelo'; & '$(Join-Path $mod "arrancar-modelo.ps1")'")
    Write-Host "Cargando el modelo (Strata tarda varios minutos la primera vez)..."
    for ($i = 0; $i -lt 180; $i++) {
        Start-Sleep -Seconds 2
        try { $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; if ($r.StatusCode -eq 200) { $up = $true; break } } catch {}
    }
    if (-not $up) { Write-Host "[ERR] El modelo no ha respondido en 6 minutos: mira la ventana «Deltha · modelo»." -ForegroundColor Red; exit 1 }
}
# un solo worker: si ya hay uno en marcha, no se abre otro
$ya = @(Get-CimInstance Win32_Process -Filter "Name like 'python%'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*\ia\ia.py*' -and $_.CommandLine -notlike '*--prueba*' })
if ($ya.Count -gt 0) { Write-Host "Ya hay un worker de IA en marcha (PID $($ya[0].ProcessId)); no abro otro."; exit 0 }
& (Join-Path $here "ia.ps1")
