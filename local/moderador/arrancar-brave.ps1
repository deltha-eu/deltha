# Puente MCP de Brave Search para el chat web del modelo (http://127.0.0.1:8080): búsqueda en internet.
# Lee la clave de [brave] api_key en moderador.ini y escucha solo en 127.0.0.1:8001.
# Solo activa tres herramientas (web, noticias, resumen: ~2.900 tokens); el juego completo de Brave ocupa ~29.000.
# El navegador no puede hablar con el 8001 (el puente no manda cabeceras CORS): cors-proxy.py escucha en el 8002 y
# reenvía. En el chat web el servidor se da de alta como http://127.0.0.1:8002/mcp
# Uso:  powershell -ExecutionPolicy Bypass -File .\arrancar-brave.ps1
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$host.UI.RawUI.WindowTitle = "Deltha · Brave Search (MCP)"
$ini = Join-Path $here "moderador.ini"
$key = ""
if (Test-Path $ini) {
    $cur = ""
    foreach ($line in Get-Content $ini -Encoding UTF8) {
        $t = $line.Trim()
        if ($t -match '^\[(.+)\]$') { $cur = $Matches[1]; continue }
        if ($cur -eq "brave" -and $t -match '^api_key\s*=\s*(.*)$') { $key = $Matches[1].Trim() }
    }
}
if (-not $key) { Write-Host "Sin clave de Brave ([brave] api_key en moderador.ini): no arranco la búsqueda." -ForegroundColor Yellow; exit 0 }
# intermediario CORS (8002 -> 8001) en esta misma ventana: al cerrarla se para con el puente
if (-not (Get-NetTCPConnection -LocalPort 8002 -State Listen -ErrorAction SilentlyContinue)) {
    $py = $null
    foreach ($c in @((Join-Path $env:USERPROFILE "Bonsai-demo\.venv\Scripts\python.exe"), "py", "python")) {
        if ($c -like "*.exe") { if (Test-Path $c) { $py = $c; break } } elseif (Get-Command $c -ErrorAction SilentlyContinue) { $py = $c; break }
    }
    if ($py) { Start-Process $py -NoNewWindow -ArgumentList @("`"$(Join-Path $here 'cors-proxy.py')`"") }
    else { Write-Host "[AVISO] Sin Python: no arranco el intermediario CORS del 8002." -ForegroundColor Yellow }
}
if (Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue) { Write-Host "Brave ya está escuchando en el 8001."; exit 0 }
$exe = Get-Command brave-search-mcp-server.cmd -ErrorAction SilentlyContinue
if (-not $exe) { Write-Host "[ERR] Falta brave-search-mcp-server (npm i -g @brave/brave-search-mcp-server)." -ForegroundColor Red; exit 1 }
$env:BRAVE_API_KEY = $key
Write-Host "Brave Search MCP en http://127.0.0.1:8001/mcp; para el chat web: http://127.0.0.1:8002/mcp (web, noticias, resumen). Ctrl+C para parar."
& $exe.Source --transport http --host 127.0.0.1 --port 8001 --enabled-tools brave_web_search brave_news_search brave_summarizer
