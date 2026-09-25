# Hace que el moderador de Deltha (modelo + worker) se abra solo al iniciar sesión en Windows, en dos ventanas minimizadas.
# Crea un acceso directo en la carpeta Inicio del usuario (shell:startup). Para quitarlo:  .\instalar-arranque.ps1 -Quitar
# Uso:  powershell -ExecutionPolicy Bypass -File .\instalar-arranque.ps1
param([switch]$Quitar)
$here    = Split-Path -Parent $MyInvocation.MyCommand.Path
$startup = [Environment]::GetFolderPath('Startup')
$lnk     = Join-Path $startup "Deltha moderador.lnk"

if ($Quitar) {
    if (Test-Path $lnk) { Remove-Item $lnk; Write-Host "Quitado: ya no arranca solo." -ForegroundColor Green }
    else { Write-Host "No estaba instalado." }
    exit 0
}
$sh = New-Object -ComObject WScript.Shell
$s = $sh.CreateShortcut($lnk)
$s.TargetPath       = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$s.Arguments        = "-NoExit -ExecutionPolicy Bypass -WindowStyle Minimized -File `"$(Join-Path $here 'arrancar.ps1')`""
$s.WorkingDirectory = $here
$s.WindowStyle      = 7   # minimizada
$s.Description      = "Deltha: modelo de moderación (llama-server) y worker"
$s.Save()
Write-Host "Instalado: $lnk" -ForegroundColor Green
Write-Host "Al iniciar sesión se abrirán dos ventanas minimizadas: «Deltha · modelo» y «Deltha · moderador (worker)»."
Write-Host "Para comprobarlo:  .\estado.ps1    Para quitarlo:  .\instalar-arranque.ps1 -Quitar"
