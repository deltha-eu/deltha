# Arranca el agente comentarista: espera a que el modelo del moderador responda (lo arranca si hace falta,
# con local/moderador/arrancar-modelo.ps1) y lanza el worker en esta ventana. No duplica un worker ya en marcha.
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar.ps1 [--seco]
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$host.UI.RawUI.WindowTitle = "Deltha · comentarista (worker)"
if (-not (Test-Path (Join-Path $here "comentarista.ini"))) { Write-Host "[ERR] Falta comentarista.ini (copia comentarista.ini.example y pon el token)." -ForegroundColor Red; exit 1 }
$up = $false
try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; $up = $true } catch {}
if (-not $up) {
    $modelo = Join-Path (Split-Path -Parent $here) "moderador\arrancar-modelo.ps1"
    Start-Process powershell -WindowStyle Minimized -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-Command",
        "`$host.UI.RawUI.WindowTitle = 'Deltha · modelo (llama-server)'; & '$modelo'")
    Write-Host "Cargando el modelo (puede tardar un minuto)..."
    for ($i = 0; $i -lt 180; $i++) {
        Start-Sleep -Seconds 2
        try { $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; if ($r.StatusCode -eq 200) { $up = $true; break } } catch {}
    }
    if (-not $up) { Write-Host "[ERR] El modelo no ha respondido en 6 minutos: mira la ventana «Deltha · modelo»." -ForegroundColor Red; exit 1 }
}
$ya = @(Get-CimInstance Win32_Process -Filter "Name like 'python%'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*comentarista.py*' -and $_.CommandLine -notlike '*--prueba*' })
if ($ya.Count -gt 0) { Write-Host "Ya hay un worker del comentarista en marcha (PID $($ya[0].ProcessId)); no abro otro."; exit 0 }
& (Join-Path $here "comentarista.ps1") @args
