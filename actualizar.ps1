# Pone al dia este PC con lo que hay en GitHub (git pull origin main).
# Si hay cambios locales sin commit, los guarda en un stash para no pisarlos.
Set-Location $PSScriptRoot
git status --short
$cambios = git status --porcelain
if ($cambios) {
    Write-Host "Hay cambios locales sin commit; los guardo en stash (git stash list para verlos)."
    git stash push -u -m "auto antes de pull $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
}
git pull origin main
git log --oneline -5
