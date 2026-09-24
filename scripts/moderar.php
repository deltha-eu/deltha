#!/usr/bin/php
<?php
/**
 * RETIRADO (24/09/2026). Era el «portero» de premoderacion con Ollama en el droplet: nada se publicaba sin veredicto.
 * Ahora la moderacion es POSMODERACION con IA local: todo se publica y el worker de local/moderador/ (en el PC de la
 * redaccion, con llama-server + Ternary Bonsai 2 27B) pide el trabajo a /api/moderacion.php. Ver lib/moderacion.php
 * y sql/migra-062-moderacion-ia-local.sql. Se deja este fichero para que un cron antiguo no falle.
 */
echo date('c') . "  scripts/moderar.php esta retirado: la moderacion la hace local/moderador/ (ver README)\n";
exit(0);
