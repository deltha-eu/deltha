# Arranca de una vez todo lo que Deltha necesita en este PC, cada cosa en su ventana minimizada y sin duplicar lo que ya este en marcha:
#   1) lector de feeds        (local\lector\lector.ps1)          no usa el modelo: sale el primero
#   2) modelo + moderador     (local\moderador\arrancar.ps1)     carga llama-server y, cuando responde, el worker de moderacion
#   3) agentes comentaristas  (local\comentarista\arrancar.ps1)  se lanza cuando el modelo ya responde (asi no lo cargan dos a la vez)
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar-todo.ps1 [-SinComentarista] [-SinLector]
# Es lo que lanza el acceso directo de Inicio que crea instalar-arranque.ps1 (el de esta carpeta).
param([switch]$SinComentarista, [switch]$SinLector)
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$host.UI.RawUI.WindowTitle = "Deltha · arranque"

function Lanzar([string]$titulo, [string]$script) {
    if (-not (Test-Path $script)) { Write-Host "[--] $titulo : no existe $script" -ForegroundColor Yellow; return }
    Start-Process powershell -WindowStyle Minimized -WorkingDirectory (Split-Path -Parent $script) -ArgumentList @("-NoExit", "-ExecutionPolicy", "Bypass", "-File", "`"$script`"")
    Write-Host "[ok] $titulo"
}
function ModeloArriba { try { $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing; return $r.StatusCode -eq 200 } catch { return $false } }

if (-not $SinLector) { Lanzar "lector de feeds" (Join-Path $here "lector\lector.ps1") }
Lanzar "modelo y moderador" (Join-Path $here "moderador\arrancar.ps1")

if (-not $SinComentarista) {
    Write-Host "Esperando a que el modelo responda para lanzar los agentes (puede tardar un minuto)..."
    $up = $false
    for ($i = 0; $i -lt 180; $i++) { if (ModeloArriba) { $up = $true; break }; Start-Sleep -Seconds 2 }
    if ($up) { Lanzar "agentes comentaristas" (Join-Path $here "comentarista\arrancar.ps1") }
    else { Write-Host "[ERR] El modelo no ha respondido en 6 minutos: los agentes no se lanzan. Mira la ventana «Deltha · modelo»." -ForegroundColor Red }
}
Write-Host ""
Write-Host "Hecho. Para ver el estado:  .\moderador\estado.ps1   ·   registros: *.log de cada carpeta."
Start-Sleep -Seconds 8
