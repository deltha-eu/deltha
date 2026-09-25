# Todo en uno: abre el modelo en otra ventana (minimizada) y, cuando responde, arranca el worker en esta.
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar.ps1
# Es lo que lanza el acceso directo de Inicio que crea instalar-arranque.ps1.
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$host.UI.RawUI.WindowTitle = "Deltha · moderador (worker)"
# búsqueda en internet para el chat web (solo si hay clave de Brave en moderador.ini)
if (-not (Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue)) {
    $ini = Get-Content (Join-Path $here "moderador.ini") -Raw -ErrorAction SilentlyContinue
    if ($ini -match '(?ms)^\[brave\].*?^api_key\s*=\s*\S+') {
        Start-Process powershell -WindowStyle Minimized -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-File", (Join-Path $here "arrancar-brave.ps1"))
    }
}
$up = $false
try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; $up = $true } catch {}
if (-not $up) {
    Start-Process powershell -WindowStyle Minimized -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-Command",
        "`$host.UI.RawUI.WindowTitle = 'Deltha · modelo (llama-server)'; & '$(Join-Path $here "arrancar-modelo.ps1")'")
    Write-Host "Cargando el modelo (puede tardar un minuto)..."
    for ($i = 0; $i -lt 180; $i++) {
        Start-Sleep -Seconds 2
        try { $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; if ($r.StatusCode -eq 200) { $up = $true; break } } catch {}
    }
    if (-not $up) { Write-Host "[ERR] El modelo no ha respondido en 6 minutos: mira la ventana «Deltha · modelo»." -ForegroundColor Red; exit 1 }
}
# un solo worker: si ya hay uno en marcha, no se abre otro
$ya = @(Get-CimInstance Win32_Process -Filter "Name like 'python%'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*moderador.py*' -and $_.CommandLine -notlike '*--prueba*' })
if ($ya.Count -gt 0) { Write-Host "Ya hay un worker en marcha (PID $($ya[0].ProcessId)); no abro otro."; exit 0 }
& (Join-Path $here "moderador.ps1")
