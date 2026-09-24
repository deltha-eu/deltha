# Todo en uno: abre el modelo en otra ventana y, cuando responde, arranca el worker en esta.
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar.ps1
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$up = $false
try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; $up = $true } catch {}
if (-not $up) {
    Start-Process powershell -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-File", (Join-Path $here "arrancar-modelo.ps1"))
    Write-Host "Cargando el modelo (puede tardar un minuto)..."
    for ($i = 0; $i -lt 180; $i++) {
        Start-Sleep -Seconds 2
        try { $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; if ($r.StatusCode -eq 200) { $up = $true; break } } catch {}
    }
    if (-not $up) { Write-Host "[ERR] El modelo no ha respondido en 6 minutos: mira la otra ventana." -ForegroundColor Red; exit 1 }
}
& (Join-Path $here "moderador.ps1")
