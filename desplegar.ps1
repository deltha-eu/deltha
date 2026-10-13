# Despliega Deltha: guarda los cambios (commit), los sube a GitHub (origin) y los empuja al repo
# del droplet (deploy), cuyo hook post-receive los pone en /var/www/deltha, aplica migraciones y pasa php -l.
# Solo este PC (con la clave ssh del droplet) puede desplegar; GitHub es para colaborar y para Claude.
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
# Traer lo que haya en GitHub (PR fusionados, trabajo del otro PC) antes de desplegar
git pull --rebase origin main
if ($LASTEXITCODE -ne 0) { Write-Host "Conflicto al traer de GitHub; resuelvelo (git status) y repite"; exit 1 }
git push origin main
if ($LASTEXITCODE -ne 0) { Write-Host "Fallo al subir a GitHub; no se despliega"; exit 1 }
git push deploy main
