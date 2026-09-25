# Estado del moderador de Deltha en este PC: modelo (llama-server) y worker (moderador.py).
# Uso:  powershell -ExecutionPolicy Bypass -File .\estado.ps1
Write-Host ""
Write-Host "=== Modelo (llama-server) ===" -ForegroundColor Cyan
$srv = @(Get-Process llama-server -ErrorAction SilentlyContinue)
if ($srv.Count -eq 0)     { Write-Host "  No está cargado." -ForegroundColor Yellow }
elseif ($srv.Count -eq 1) { Write-Host "  Cargado una vez (PID $($srv[0].Id), $([math]::Round($srv[0].WorkingSet64/1MB)) MB de RAM)." -ForegroundColor Green }
else                      { Write-Host "  ¡Cargado $($srv.Count) veces! PIDs: $($srv.Id -join ', ')  → cierra las ventanas sobrantes o: Stop-Process -Id <PID>" -ForegroundColor Red }

$port = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($port) {
    $who = (Get-Process -Id $port.OwningProcess -ErrorAction SilentlyContinue).ProcessName
    Write-Host "  Puerto 8080: escucha $who (PID $($port.OwningProcess)) en $($port.LocalAddress)"
} else { Write-Host "  Puerto 8080: nadie escucha." }

try {
    $h = Invoke-RestMethod -Uri "http://127.0.0.1:8080/health" -TimeoutSec 3
    Write-Host "  /health: $($h.status)" -ForegroundColor Green
} catch { Write-Host "  /health: no responde (o aún está cargando)" -ForegroundColor Yellow }

Write-Host ""
Write-Host "=== GPU ===" -ForegroundColor Cyan
if (Get-Command nvidia-smi -ErrorAction SilentlyContinue) {
    $g = (nvidia-smi --query-gpu=memory.used,memory.total,utilization.gpu --format=csv,noheader,nounits) -split ',\s*'
    Write-Host "  VRAM $($g[0]) / $($g[1]) MiB · uso $($g[2]) %"
}

Write-Host ""
Write-Host "=== Brave Search (MCP: puerto 8001, chat web: 8002) ===" -ForegroundColor Cyan
$b = Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($b) { Write-Host "  Escuchando (PID $($b.OwningProcess)) en $($b.LocalAddress)." -ForegroundColor Green } else { Write-Host "  No está en marcha (sin clave o sin arrancar)." -ForegroundColor Yellow }
$p = Get-NetTCPConnection -LocalPort 8002 -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($p) { Write-Host "  Intermediario CORS del chat web: escuchando en el 8002 (PID $($p.OwningProcess)) → usa http://127.0.0.1:8002/mcp" -ForegroundColor Green } elseif ($b) { Write-Host "  Intermediario CORS (8002): NO está en marcha; el chat web no podrá usar Brave." -ForegroundColor Yellow }

Write-Host ""
Write-Host "=== Worker (moderador.py) ===" -ForegroundColor Cyan
$all = @(Get-CimInstance Win32_Process -Filter "Name like 'python%'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*moderador.py*' -and $_.CommandLine -notlike '*--prueba*' })
# el python.exe de un .venv en Windows es un lanzador que abre el Python real como hijo con la misma orden:
# padre + hijo son UN solo worker, asi que solo cuentan los que no son hijos de otro de la lista
$pids = @($all | ForEach-Object { $_.ProcessId })
$wk = @($all | Where-Object { $pids -notcontains $_.ParentProcessId })
if ($wk.Count -eq 0)     { Write-Host "  No está en marcha." -ForegroundColor Yellow }
elseif ($wk.Count -eq 1) { Write-Host "  En marcha una vez (PID $($wk[0].ProcessId))." -ForegroundColor Green }
else                     { Write-Host "  ¡En marcha $($wk.Count) veces! PIDs: $($wk.ProcessId -join ', ')  (no rompe nada, pero sobra)" -ForegroundColor Red }

$log = Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) "moderador.log"
if (Test-Path $log) {
    Write-Host ""
    Write-Host "=== Últimas líneas de moderador.log ===" -ForegroundColor Cyan
    Get-Content $log -Tail 5 -Encoding UTF8 | ForEach-Object { Write-Host "  $_" }
}
Write-Host ""
