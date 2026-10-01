# Pone (o quita con -Quitar) el lector local de feeds en el arranque de Windows: un acceso directo en la carpeta Inicio
# que abre lector.ps1 en una ventana minimizada.
# Uso:  powershell -ExecutionPolicy Bypass -File .\instalar-arranque.ps1 [-Quitar]
param([switch]$Quitar)
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$lnk = Join-Path ([Environment]::GetFolderPath('Startup')) "Deltha lector de feeds.lnk"
if ($Quitar) {
    if (Test-Path $lnk) { Remove-Item $lnk; Write-Host "Quitado del arranque." } else { Write-Host "No estaba en el arranque." }
    exit 0
}
$ws = New-Object -ComObject WScript.Shell
$s = $ws.CreateShortcut($lnk)
$s.TargetPath = "powershell.exe"
$s.Arguments = "-NoExit -ExecutionPolicy Bypass -File `"$(Join-Path $here 'lector.ps1')`""
$s.WorkingDirectory = $here
$s.WindowStyle = 7
$s.Description = "Deltha: lector local de feeds"
$s.Save()
Write-Host "Hecho: el lector arrancara al iniciar sesion en Windows ($lnk)."
