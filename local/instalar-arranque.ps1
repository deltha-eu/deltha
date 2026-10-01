# Deja UN solo acceso directo en la carpeta Inicio de Windows que arranca todo lo de Deltha (arrancar-todo.ps1: lector de feeds,
# modelo + moderador y agentes comentaristas) y quita los accesos sueltos de antes («Deltha moderador», «Deltha lector de feeds»),
# para que nada se abra dos veces. Para deshacerlo:  .\instalar-arranque.ps1 -Quitar
# Uso:  powershell -ExecutionPolicy Bypass -File .\instalar-arranque.ps1 [-SinComentarista]
param([switch]$Quitar, [switch]$SinComentarista)
$here    = Split-Path -Parent $MyInvocation.MyCommand.Path
$startup = [Environment]::GetFolderPath('Startup')
$lnk     = Join-Path $startup "Deltha.lnk"
foreach ($viejo in @("Deltha moderador.lnk", "Deltha lector de feeds.lnk")) {
    $p = Join-Path $startup $viejo
    if (Test-Path $p) { Remove-Item $p; Write-Host "Quitado el acceso suelto: $viejo" }
}
if ($Quitar) {
    if (Test-Path $lnk) { Remove-Item $lnk; Write-Host "Quitado: Deltha ya no arranca solo." -ForegroundColor Green } else { Write-Host "No estaba instalado." }
    exit 0
}
$extra = if ($SinComentarista) { " -SinComentarista" } else { "" }
$sh = New-Object -ComObject WScript.Shell
$s = $sh.CreateShortcut($lnk)
$s.TargetPath       = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$s.Arguments        = "-ExecutionPolicy Bypass -WindowStyle Minimized -File `"$(Join-Path $here 'arrancar-todo.ps1')`"$extra"
$s.WorkingDirectory = $here
$s.WindowStyle      = 7   # minimizada
$s.Description      = "Deltha: lector de feeds, modelo + moderador y agentes comentaristas"
$s.Save()
Write-Host "Instalado: $lnk" -ForegroundColor Green
Write-Host "Al iniciar sesion se abriran, minimizadas: lector de feeds, modelo, moderador$(if ($SinComentarista) { '' } else { ' y agentes comentaristas' })."
