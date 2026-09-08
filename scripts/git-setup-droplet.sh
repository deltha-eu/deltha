#!/bin/bash
# Se ejecuta UNA vez en el droplet: crea el repositorio Git desnudo /root/deltha.git y el hook
# post-receive que, en cada "git push deploy main" desde el PC, vuelca el codigo en /var/www/deltha
# y llama a scripts/desplegar.sh --git (migraciones + php -l).
set -e
REPO=/root/deltha.git
DIR=/var/www/deltha
command -v git >/dev/null || { apt-get update -qq && apt-get install -y -qq git; }
[ -d "$REPO" ] || git init --bare -b main "$REPO"
mkdir -p "$DIR/public/media"
cat > "$REPO/hooks/post-receive" <<'HOOK'
#!/bin/bash
set -e
DIR=/var/www/deltha
REPO=/root/deltha.git
while read old new ref; do
  [ "$ref" = "refs/heads/main" ] || { echo "rama $ref ignorada (solo se despliega main)"; continue; }
  echo "== codigo (git $(echo "$new" | cut -c1-7))"
  git --work-tree="$DIR" --git-dir="$REPO" checkout -f main
  chown -R root:root "$DIR"; chown -R www-data:www-data "$DIR/public/media"
  bash "$DIR/scripts/desplegar.sh" --git
done
HOOK
chmod +x "$REPO/hooks/post-receive"
echo "Repositorio listo en $REPO. Desde el PC: git remote add deploy root@$(hostname -f 2>/dev/null || hostname):$REPO"
