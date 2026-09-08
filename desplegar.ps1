# Despliega F:\Deltha en el droplet con Git: guarda los cambios (commit) y los empuja al repo
# del droplet, cuyo hook post-receive los pone en /var/www/deltha, aplica migraciones y pasa php -l.
# Uso (PowerShell, desde cualquier carpeta):
#   F:\Deltha\desplegar.ps1 "mensaje del cambio"
#   F:\Deltha\desplegar.ps1            (mensaje automatico con fecha y hora)
param([string]$msg = "deploy $(Get-Date -Format 'yyyy-MM-dd HH:mm')")
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $dir
git add -A
git diff --cached --quiet
if ($LASTEXITCODE -ne 0) {
  git commit -m $msg
  if ($LASTEXITCODE -ne 0) { Write-Host "Fallo al hacer commit"; exit 1 }
} else {
  Write-Host "Sin cambios nuevos; se empuja lo que haya pendiente."
}
git push deploy main
