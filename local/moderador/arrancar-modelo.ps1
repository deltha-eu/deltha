# Arranca el modelo local para el moderador y los agentes de Deltha. Solo escucha en 127.0.0.1:8080.
# El motor lo decide [modelo] motor en moderador.ini:
#   strata  Strata (https://github.com/Niko1221/Strata) con Qwen3.8-Flash-Next; opciones en [strata]
#   llama   llama-server (fork de PrismML) con Ternary Bonsai 2 27B; opciones en [bonsai]
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
$motor  = (IniValue "modelo" "motor" "strata").ToLower()
$apiKey = IniValue "modelo" "api_key" ""

try { $null = Invoke-WebRequest -Uri "http://127.0.0.1:8080/health" -TimeoutSec 2 -UseBasicParsing
      Write-Host "[AVISO] Ya hay algo escuchando en el 8080 (¿otro modelo en marcha?)." -ForegroundColor Yellow; exit 1 } catch {}

# ------------------------------------------------------------------ Strata
if ($motor -eq "strata") {
    $dir    = IniValue "strata" "dir" (Join-Path $env:USERPROFILE "Strata")
    $modelo = IniValue "strata" "modelo" "IQ2_XS"
    $ctx    = IniValue "strata" "ctx" "16384"
    $vram   = IniValue "strata" "vram_reserva" "700"
    $bat    = Join-Path $dir "START-HERE.bat"
    if (-not (Test-Path $bat)) { Write-Host "[ERR] No encuentro $bat (¿está Strata en [strata] dir?)" -ForegroundColor Red; exit 1 }
    # la clave la lee Strata de la variable de entorno (o de strata-<modelo>.json)
    if ($apiKey) { $env:STRATA_API_KEY = $apiKey }
    $batArgs = @("--model", $modelo, "--context", $ctx, "--vram-reserve-mib", $vram, "--port", "8080", "--no-browser", "--yes")
    Write-Host ""
    Write-Host "=== Modelo de Deltha: Strata ===" -ForegroundColor Cyan
    Write-Host "  Modelo:   Qwen3.8-Flash-Next $modelo"
    Write-Host "  Carpeta:  $dir"
    $extra = if ($apiKey) { " · con api_key" } else { "" }
    Write-Host "  Contexto: $ctx · VRAM reservada $vram MiB · una petición cada vez · solo 127.0.0.1:8080$extra"
    Write-Host "  La carga mete 35-55 GB en RAM: el PC puede quedarse atascado 1-3 minutos. Ctrl+C para parar."
    Write-Host ""
    Set-Location $dir
    & cmd.exe /c "`"$bat`" $($batArgs -join ' ')"
    exit $LASTEXITCODE
}

# ------------------------------------------------------------------ llama-server (Bonsai)
$bonsai = IniValue "bonsai" "dir" (Join-Path $env:USERPROFILE "Bonsai-demo")
$ctx    = IniValue "bonsai" "ctx" "32768"
$slots  = IniValue "bonsai" "slots" "2"
$kv     = IniValue "bonsai" "kv" "q8_0"
$unif   = IniValue "bonsai" "unificada" "1"

$bin   = Join-Path $bonsai "bin\cuda\llama-server.exe"
$model = Get-ChildItem (Join-Path $bonsai "models\bonsai2-gguf\27B") -Filter "*-PQ2_0.gguf" -File |
         Where-Object { $_.Name -notlike "*mmproj*" } | Select-Object -First 1
if (-not (Test-Path $bin)) { Write-Host "[ERR] No encuentro $bin" -ForegroundColor Red; exit 1 }
if (-not $model)           { Write-Host "[ERR] No encuentro el GGUF PQ2_0 en $bonsai\models\bonsai2-gguf\27B" -ForegroundColor Red; exit 1 }

$env:Path = "$(Split-Path $bin -Parent);$env:Path"
# PowerShell 5.1 pasa mal las comillas a los .exe: hay que escaparlas (igual que start_llama_server.ps1)
$kw = if ($PSVersionTable.PSEdition -eq 'Desktop') { '{\"enable_thinking\": false}' } else { '{"enable_thinking": false}' }
$srvArgs = @(
    "-m", $model.FullName,
    "--host", "127.0.0.1", "--port", "8080",
    "-ngl", "99", "-fa", "on",
    "-c", $ctx, "-np", $slots,
    "--jinja", "--chat-template-kwargs", $kw,
    "--temp", "0"
)
if ($unif -eq "1") { $srvArgs += "--kv-unified" }
if ($kv -and $kv -ne "f16") { $srvArgs += @("--cache-type-k", $kv, "--cache-type-v", $kv) }
# servidores MCP preconfigurados en el chat web (Brave Search); se activan por conversación
$webui = Join-Path $here "webui-config.json"
if (Test-Path $webui) { $srvArgs += @("--webui-config-file", $webui) }
if ($apiKey) { $srvArgs += @("--api-key", $apiKey) }

Write-Host ""
Write-Host "=== Modelo de Deltha: llama-server (Bonsai) ===" -ForegroundColor Cyan
Write-Host "  Modelo:   $($model.Name)"
Write-Host "  Binario:  $bin"
$extra = if ($apiKey) { " · con api_key" } else { "" }
$reparto = if ($unif -eq "1") { "compartido" } else { "a partes iguales" }
Write-Host "  Contexto: $ctx en $slots ranuras ($reparto) · caché $kv · solo 127.0.0.1:8080$extra"
Write-Host "  Ctrl+C para parar."
Write-Host ""
& $bin @srvArgs
exit $LASTEXITCODE
