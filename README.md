# Delta de Dirac — v0 (web privada)

PHP 8.1+ (gd, mbstring, pdo_mysql, curl) · MySQL/MariaDB · Apache 2.4 (o nginx). Sin framework, sin composer, sin JS obligatorio, sin recursos de terceros. Cero cookies para quien no inicia sesión.

## Instalar
```bash
cd /var/www && unzip deltha-v0.zip -d deltha
cd deltha && sudo ./install.sh   # crea BD, usuario deltha, /etc/deltha/config.ini, admin y media/
```
Apuntar el vhost a `public/` (ver `apache.conf.example`) y añadir el cron:
```
*/5 * * * * /usr/bin/php /var/www/deltha/scripts/promocionar.php >> /var/log/deltha-promo.log 2>&1
```

La zona horaria la fija `lib/app.php` (`[app] timezone` en config.ini, por defecto Europe/Madrid) para que web y cron compartan el mismo `NOW()`. Si el cron no promociona nada, ejecutarlo a mano y comparar: `php scripts/promocionar.php` y `php -r 'echo date("P");'`.

## Acceso privado (clave temporal)
`config.access_key` (admin → Config; `sql/migra-006-clave.sql`). Mientras no esté vacía, quien no tenga la clave es redirigido a la presentación `/delta/`; el «Sí» final lleva a `/delta/clave.php`, que deja una cookie técnica `ddd_k` (HMAC de la clave, 30 días). Los usuarios logueados pasan sin clave (`/login.php` queda exento). Cambiar la clave invalida todas las cookies; vaciarla abre la web.

## Tema claro/oscuro
Por defecto sigue al sistema (`prefers-color-scheme`). Un registrado puede fijarlo en `/perfil.php` (`users.theme`, `sql/migra-007-tema.sql`): se emite `<html data-theme="light|dark">` y el CSS lo respeta vía variables (`--logo-l/--logo-d/--ink-invert`). Sin cookie, sin JS.

## Qué hay
- `/` Portada (destacadas) — con clave activa y sin ella, redirige a `/delta/` · `/candidatas.php` (una asciende por ciclo, la nueva de mayor delta; ordenadas por delta calculado al cargar) · `/nuevas.php` (con delta en vivo y cuenta atrás de elegibilidad) · `/articulos.php` (vacía)
- `/hilo.php?id=N` noticia + comentarios cronológicos con alias Ghost (Apellido-Lugar: `Mozart-Alburquerque`, listas en `lib/app.php`) y halo de aura
- `/enviar.php` URL + titular + entradilla + tema + foto (subida → og:image → color plano). Recorte 3:2 en servidor, JPEG sin EXIF.
- `/votar.php` solo registrados; un voto por envío, cambio de sentido permitido, autovoto ignorado
- `/comentar.php` anónimos (alias estable por hilo vía hash ip+hilo+secreto, sin cookie) y registrados (alias Ghost por hilo)
- `/privacidad.php` política de privacidad, aviso legal y contacto (info@deltha.eu); visible sin clave; enlazada en el pie; texto editable desde admin (tabla `pages`, `sql/migra-009-paginas.sql`; marcadores `{site}` `{ttl}` `{gm}`)
- `/usuarios.php` últimos 20 registrados (solo registrados) · en perfil: cambio de nick y tema
- `/editar-usuario.php?id=N` moderadores y admin editan el perfil de otro usuario (nick, frase, foto; el admin además correo y estado de activación). Un moderador solo edita cuentas `normal`. Enlace «editar perfil» en `/u.php` y botón «editar» en admin → Usuarios
- `/moderacion.php` cola de revisión humana (lo que la IA no decide); para `level=mod` y admin; enlace en perfil y pie. Con `mod_enabled=0` todo se publica directo y la cola queda vacía
- `/tema.php?t=ID` feed de un tema: todo lo enviado (enlaces y artículos, cualquier fase) por fecha; enlazado desde la etiqueta gris sobre la foto y desde el nombre del tema en cada post
- `/estadisticas.php` registrados, conectados con cuenta (`users.last_seen`, `sql/migra-010-last-seen.sql`), lectores sin cuenta (tabla `presence`: huella HMAC truncada de ip+navegador+día, borrada a los 10 min, `sql/migra-011-presencia.sql`), actividad
- `/moderacion.php` además: lista de temas y alta de temas nuevos (nombre; coef 1,00). Moderadores y admin cambian el tema de un envío desde su hilo (`/cambiar-tema.php`) y lo editan entero en `/editar.php?id=N` (titular, enlace, entradilla o texto, tema, foto: subir, sustituir, quitar o extraer del enlace; eliminar → status `discard`). Comentarios: `/editar-comentario.php?id=N` (editar texto; eliminar → status `rejected` y contador −1), enlace «editar · eliminar» bajo cada comentario para mods
- Registro con activación por correo: `/registro.php` crea la cuenta con `verified=0`, envía enlace (`lib/mail.php`: `mail()` o SMTP según `[mail]` de config.ini; `site_url`, `mail_from`, `token_hours` en config) → `/activar.php?u&t` activa y abre sesión → `/reenviar.php` (cada 10 min). Límite `reg_per_ip_hour` por IP (huella, tabla `reg_attempts`). Dominios vetados (`banned_domains`, gestionados en moderación) y dominios sin MX se rechazan. `sql/migra-012-activacion.sql`
- `/admin.php` usuarios (todos, con buscador por usuario/correo, casilla «moderador» → `users.level`, `sql/migra-008-moderador.sql`; botón «validar» para marcar a mano el correo como activado; botón «borrar» que, tras confirmar tecleando el nick, elimina la cuenta con todo lo suyo en cascada: sus hilos enteros incluidos comentarios y votos ajenos, sus comentarios con las respuestas que cuelgan de ellos, sus votos con ajuste de contadores, alias, aura, fotos y avatar), config editable, editor de la página de privacidad, temas (coef), **ejecutar ciclo**, **adelantar reloj**, cola de revisión humana, log de promociones
- `scripts/promocionar.php` cron · `scripts/moderar.php` portero (apagado: `mod_enabled=0`)

## Ciclo
envío → `nuevas` → (≥30 min) cada ciclo asciende UNA a `candidatas`, la de mayor delta → cada ciclo se publica la candidata de mayor delta si ≥ `delta_min_publish` → `destacadas` (+0,10 aura al autor, tope 50) → (48 h) `archived` + revelado sigiloso de nicks.

delta = (pos − neg) × coef_tema − 0,05 × medias_horas   (o multiplicativo, `decay_mode`)

## Probar sin esperar
Admin → "Adelantar reloj 35 min" → "Ejecutar ciclo" → ver Candidatas/Portada. Reloj 2940 min → ciclo → hilo archivado con nombres reales.

## Pendiente (fuera de v0)
Portero activo (Ollama), moderación de imágenes, archivo SEO, tarjetas compartibles, espacios de aura, Artículos, purga de caché Cloudflare al revelar.

## Migración a un droplet propio (`scripts/migrar-empaquetar.sh` + `scripts/install-droplet.sh`)
1. En el servidor viejo: `bash scripts/migrar-empaquetar.sh` → `/root/deltha-migracion.tar.gz` (BD, config.ini, media, cron, curl-impersonate). Copiarlo: `scp /root/deltha-migracion.tar.gz root@IP_NUEVA:/root/`.
2. Desde el PC: `scp F:\Deltha\deltha.zip root@IP_NUEVA:/root/` (y `scripts/install-droplet.sh`).
3. Cloudflare → SSL/TLS → Origin Server → crear certificado de origen; guardarlo en el droplet como `/etc/ssl/deltha/origin.pem` y `/etc/ssl/deltha/origin.key`. Modo SSL «Completo (estricto)».
4. En el droplet (Ubuntu 24.04, hostname = dominio): `bash install-droplet.sh deltha.eu`. Instala Apache+PHP 8.3-FPM+MySQL, restaura datos, vhost 80/443, cron, Postfix solo envío + OpenDKIM (selector `deltha`), ufw y fail2ban, e imprime el TXT `deltha._domainkey`.
5. Cloudflare DNS: `A @` y `A www` → IP nueva; SPF con `ip4:IP_NUEVA`; TXT `deltha._domainkey`.
6. Probar correo con `sendmail` y comprobar spf/dkim/dmarc = pass en las cabeceras.

## Despliegue de cambios (día a día) — Git
El código vive en un repositorio Git en `F:\Deltha` (`.gitignore` excluye `deltha.zip`, `config.ini` y `public/media/`). El droplet tiene un repo desnudo en `/root/deltha.git` (creado con `scripts/git-setup-droplet.sh`) cuyo hook `post-receive` vuelca `main` en `/var/www/deltha` y ejecuta `scripts/desplegar.sh --git`: aplica las migraciones `sql/migra-*.sql` que no consten en `/etc/deltha/migradas.txt` y pasa `php -l` a todo. Ni `public/media` ni `/etc/deltha/config.ini` se tocan.

- Desplegar: `F:\Deltha\desplegar.ps1 "qué he cambiado"` (hace `git add -A`, `commit` y `git push deploy main`).
- Ver qué hay pendiente: `git status`; historial: `git log --oneline`; volver atrás: `git revert <hash>` y desplegar.
- Solo se despliega la rama `main`; otras ramas se pueden empujar sin efecto en producción.
- Modo antiguo (zip) sigue disponible: `scp deltha.zip root@165.245.212.46:/root/` y `bash /root/desplegar.sh` sin `--git`.
