# Arranca llama-server (fork de PrismML) con Ternary Bonsai 2 27B para el moderador de Deltha.
# Solo escucha en 127.0.0.1:8080. Sin proyector de visión (no hace falta y ahorra ~0,9 GB de VRAM).
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar-modelo.ps1
$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$ini = Join-Path $here "moderador.ini"
function IniValue($section, $key, $default) {
    if (-not (Test-Path $ini)) { return $default }
    $cur = ""
    foreach ($line in Get-Content $ini -Encoding UTF8) {
        $t = $line.Trim()
        if ($t -match '^\[(.+)\]$') { $cur = $Matches[1]; continue }
        if ($cur -eq $section -and $t -match "^$key\s*=\s*(.*)$") { $v = $Matches[1].Trim(); if ($v) { return $v } }
    }
    return $default
}
$bonsai = IniValue "bonsai" "dir" (Join-Path $env:USERPROFILE "Bonsai-demo")
$ctx    = IniValue "bonsai" "ctx" "8192"
$apiKey = IniValue "modelo" "api_key" ""

$bin   = Join-Path $bonsai "bin\cuda\llama-server.exe"
$model = Get-ChildItem (Join-Path $bonsai "models\bonsai2-gguf\27B") -Filter "*-PQ2_0.gguf" -File |
         Where-Object { $_.Name -notlike "*mmproj*" } | Select-Object -First 1
if (-not (Test-Path $bin)) { Write-Host "[ERR] No encuentro $bin" -ForegroundColor Red; exit 1 }
if (-not $model)           { Write-Host "[ERR] No encuentro el GGUF PQ2_0 en $bonsai\models\bonsai2-gguf\27B" -ForegroundColor Red; exit 1 }

try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing
      Write-Host "[AVISO] Ya hay algo escuchando en el 8080 (¿otro llama-server?)." -ForegroundColor Yellow; exit 1 } catch {}

$env:Path = "$(Split-Path $bin -Parent);$env:Path"
# PowerShell 5.1 pasa mal las comillas a los .exe: hay que escaparlas (igual que start_llama_server.ps1)
$kw = if ($PSVersionTable.PSEdition -eq 'Desktop') { '{\"enable_thinking\": false}' } else { '{"enable_thinking": false}' }
$srvArgs = @(
    "-m", $model.FullName,
    "--host", "127.0.0.1", "--port", "8080",
    "-ngl", "99", "-fa", "on",
    "-c", $ctx, "-np", "1",
    "--jinja", "--chat-template-kwargs", $kw,
    "--temp", "0"
)
if ($apiKey) { $srvArgs += @("--api-key", $apiKey) }

Write-Host ""
Write-Host "=== Moderador de Deltha: modelo ===" -ForegroundColor Cyan
Write-Host "  Modelo:   $($model.Name)"
Write-Host "  Binario:  $bin"
$extra = if ($apiKey) { " · con api_key" } else { "" }
Write-Host "  Contexto: $ctx · solo 127.0.0.1:8080$extra"
Write-Host "  Ctrl+C para parar."
Write-Host ""
& $bin @srvArgs
exit $LASTEXITCODE
