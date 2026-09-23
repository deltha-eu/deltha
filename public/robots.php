<?php
/** /robots.txt -> este fichero (AliasMatch en apache.conf). Parte de public/robots.txt, que sigue siendo la fuente editable.
 *  Con config robots_ai_users = 1 deja pasar a los asistentes de IA que solo entran cuando una persona se lo pide en ese momento
 *  (ChatGPT-User, Claude-User, Perplexity-User, MistralAI-User): se sacan del grupo vetado y reciben las mismas reglas que los
 *  buscadores, menos /delta/clave.php, para que puedan entrar con el enlace de la clave (/delta/clave.php?k=…).
 *  Los rastreadores de entrenamiento y de buscadores de IA (GPTBot, ClaudeBot, CCBot, OAI-SearchBot…) siguen vetados siempre.
 *  Con 0 (por defecto) el resultado es robots.txt tal cual. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
const DDD_AI_USER_AGENTS = ['ChatGPT-User', 'Claude-User', 'Perplexity-User', 'MistralAI-User'];

$txt = (string)@file_get_contents(__DIR__ . '/robots.txt');
if ((string)cfg('robots_ai_users', '0') === '1' && $txt !== '') {
    $lines = preg_split('/\r?\n/', $txt);
    $quitar = array_map('strtolower', DDD_AI_USER_AGENTS);
    $out = []; $disallows = []; $enStar = false;
    foreach ($lines as $l) {
        if (preg_match('/^\s*User-agent:\s*(.+?)\s*$/i', $l, $m)) {
            $enStar = $m[1] === '*';
            if (in_array(strtolower($m[1]), $quitar, true)) continue;   // fuera del grupo vetado
        } elseif ($enStar && preg_match('/^\s*Disallow:\s*(\S+)/i', $l, $m) && $m[1] !== '/delta/clave.php') $disallows[] = $m[1];
        $out[] = $l;
    }
    $out[] = '';
    $out[] = '# Asistentes de IA que entran solo cuando una persona se lo pide (config robots_ai_users = 1, admin > Config).';
    $out[] = '# Pueden usar el enlace de la clave; lo demas, como los buscadores.';
    foreach (DDD_AI_USER_AGENTS as $ua) $out[] = "User-agent: $ua";
    foreach ($disallows ?: [''] as $d) $out[] = 'Disallow: ' . $d;
    $txt = implode("\n", $out) . "\n";
}
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=300');   // corto: el cambio en config se nota en minutos (tambien tras Cloudflare)
echo $txt;
