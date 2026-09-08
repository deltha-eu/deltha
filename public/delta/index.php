<?php
define('DDD_PUBLIC', true);   // la presentacion se ve sin clave
require __DIR__ . '/../../lib/app.php';
$entrar = gate_open() ? '/' : '/delta/clave.php';
$gm = (int)cfg('ghost_minutes', 30);
$max = (int)cfg('aura_max', 50);
$n = fn($k, $d) => rtrim(rtrim(number_format((float)cfg($k, $d), 3, ',', ''), '0'), ',');
$aPort = $n('aura_portada', 0.5); $aEnv = $n('aura_envio', 0.1); $aCom = $n('aura_comentario', 0.02);
$aCap = $n('aura_daily_cap', 2); $aDec = $n('aura_daily_decay', 0.5);
$dl = strtotime(cfg('aura_deadline', '2026-12-31'));
$dlTxt = date('j', $dl) . ' de ' . ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)date('n', $dl)] . ' de ' . date('Y', $dl);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<link rel="icon" href="/img/favicon.png">
<title>Deltha · presentación</title>
<link rel="stylesheet" href="/css/ddd.css?v=<?= DDD_CSS_VER ?>">
<style>
html, body { height: 100%; }
body { overflow: hidden; }
.deck { position: fixed; inset: 0; }
.slide { display: none; position: absolute; inset: 0; flex-direction: column; justify-content: center; align-items: center;
  padding: 32px 24px calc(32px + env(safe-area-inset-bottom)); text-align: center; text-decoration: none; color: var(--fg); }
.slide:target { display: flex; }
#s1 { display: flex; }
body:has(.slide:target) #s1:not(:target) { display: none; }
.slide .logo { height: 72px; margin-bottom: 28px; }
.slide .logo.l { display: var(--logo-l); }
.slide .logo.d { display: var(--logo-d); }
.slide p b { font-weight: 700; }
.slide h1 { font-family: var(--serif); font-size: 30px; line-height: 1.2; margin: 0 0 18px; max-width: 520px; padding: 0; }
.slide p, .slide li { font-size: 18px; line-height: 1.5; max-width: 520px; color: var(--fg); }
.slide ul { list-style: none; padding: 0; margin: 0 0 12px; text-align: left; display: flex; flex-direction: column; gap: 14px; }
.slide li { padding-left: 28px; position: relative; }
.slide li::before { content: "δ"; position: absolute; left: 4px; top: 0; font-family: var(--serif); color: var(--accent); font-size: 20px; }
.slide li b { display: block; font-weight: 700; }
.slide li a { color: var(--accent); text-decoration: underline; text-underline-offset: 3px; }
a.hint { text-decoration: none; }
.hint { position: absolute; bottom: calc(28px + env(safe-area-inset-bottom)); left: 0; right: 0; text-align: center; font-size: 14px; color: var(--muted); }
.dots { position: absolute; top: 20px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px; }
.dots span { width: 8px; height: 8px; border-radius: 50%; background: var(--line2); }
.dots span.on { background: var(--accent); }
.choice { display: flex; gap: 14px; margin-top: 12px; }
.choice a { font: inherit; font-weight: 700; font-size: 17px; padding: 12px 28px; border-radius: 999px; text-decoration: none; }
.choice .yes { background: var(--accent); color: var(--bg); }
.choice .no { border: 1px solid var(--line2); color: var(--fg); }
.aura-row { display: flex; gap: 18px; align-items: flex-end; margin: 8px 0 18px; }
.aura-row .av { width: 44px; height: 44px; font-size: 18px; }
.aura-row small { display: block; font-size: 12px; color: var(--muted); margin-top: 6px; }
</style></head><body>
<div class="deck">

<a class="slide" id="s1" href="#s2">
  <div class="dots"><span class="on"></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <img class="logo l" src="/img/logo.png" alt=""><img class="logo d" src="/img/logo-dark.png" alt="">
  <h1>Hola.<br>Parece que has sido seleccionado para conocer Deltha.</h1>
  <p class="hint">Pulsa para continuar</p>
</a>

<a class="slide" id="s2" href="#s3">
  <div class="dots"><span></span><span class="on"></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <h1>Deltha es</h1>
  <ul>
    <li>Un agregador de noticias.</li>
    <li>Una red social contralgorítmica.</li>
    <li>Un espacio para el debate.</li>
  </ul>
  <p class="hint">Pulsa para continuar</p>
</a>

<div class="slide" id="s3">
  <div class="dots"><span></span><span></span><span class="on"></span><span></span><span></span><span></span><span></span><span></span></div>
  <h1>De dónde viene</h1>
  <ul>
    <li><b><a href="https://contralgoritmia.com/" target="_blank" rel="noopener">El manifiesto contralgorítmico</a></b> La idea de fondo: recuperar el criterio humano frente a la hegemonía del algoritmo.</li>
    <li><b><a href="https://www.meneame.net" target="_blank" rel="noopener">Menéame</a></b> El agregador de noticias: la comunidad decide qué llega a portada.</li>
    <li><b><a href="https://x.com" target="_blank" rel="noopener">X</a></b> El diseño y la usabilidad: una línea de tiempo limpia y rápida.</li>
    <li><b><a href="https://www.4chan.org" target="_blank" rel="noopener">4chan</a></b> La efimeridad: los hilos nacen, se discuten y desaparecen.</li>
    <li><b>El modelo de negocio de Jot Down</b> Sin publicidad programática ni rastreo: una comunidad que sostiene lo que lee.</li>
  </ul>
  <a class="hint" href="#s4">Pulsa para continuar</a>
</div>

<a class="slide" id="s4" href="#s5">
  <div class="dots"><span></span><span></span><span></span><span class="on"></span><span></span><span></span><span></span><span></span></div>
  <h1>Cómo funciona</h1>
  <ul>
    <li><b>Es efímera.</b> Los envíos y los comentarios desaparecen a las 48 horas. Lo que importa es lo que pasa ahora, no lo que se acumula.</li>
    <li><b>No deja huella.</b> Sin cookies, sin rastreo, sin publicidad programática. Puedes comprobarlo abriendo las herramientas de tu navegador.</li>
    <li><b>Es horizontal.</b> Todo se publica bajo un alias y el nombre del autor solo aparece a la media hora, para que se juzguen las palabras antes que a quien las dice.</li>
  </ul>
  <p class="hint">Pulsa para continuar</p>
</a>

<a class="slide" id="s5" href="#s6">
  <div class="dots"><span></span><span></span><span></span><span></span><span class="on"></span><span></span><span></span><span></span></div>
  <h1>El aura</h1>
  <div class="aura-row">
    <div><span class="av" style="--ring:.2;--glow:0px">a</span><small>anónimo · 5</small></div>
    <div><span class="av" style="--ring:.6;--glow:4px">g</span><small>ghost · 25</small></div>
    <div><span class="av" style="--ring:1;--glow:8px">d</span><small>comprometido · <?= $max ?></small></div>
  </div>
  <ul>
    <li><b>No hay karma.</b> Hay aura, y mide una sola cosa: tu compromiso con la comunidad. Se gana estando y aportando, no gustando.</li>
    <li><b>Nunca da altavoz.</b> El aura no sube noticias ni ordena comentarios. Todo el mundo ve lo mismo, en el mismo orden. Se ve como luz alrededor de tu alias, nada más.</li>
    <li><b>Sin algoritmo oculto.</b> Lo que llega a portada lo decide una fórmula pública que se muestra bajo cada envío. Y solo se filtra lo ilegal, con una moderación propia que corre en nuestros servidores.</li>
  </ul>
  <p class="hint">Pulsa para continuar</p>
</a>

<div class="slide" id="s6">
  <div class="dots"><span></span><span></span><span></span><span></span><span></span><span class="on"></span><span></span><span></span></div>
  <h1>¿Qué se puede publicar?</h1>
  <ul>
    <li><b>Todo lo que permite la ley.</b> El límite no lo pone una línea editorial ni un gusto: lo pone la ley. Fuera quedan los insultos, los delitos de odio, las amenazas y todo lo que sea delito.</li>
    <li><b>Modera una IA que vive en nuestros servidores.</b> Ningún envío ni comentario sale de ellos para ser revisado: no hay terceros mirando lo que escribes. Cuando duda, decide una persona.</li>
    <li><b>Y hay un correo para todo lo demás.</b> Quejas, felicitaciones y propuestas: <a href="mailto:info@deltha.eu">info@deltha.eu</a>.</li>
  </ul>
  <a class="hint" href="#s7">Pulsa para continuar</a>
</div>

<a class="slide" id="s7" href="#s8">
  <div class="dots"><span></span><span></span><span></span><span></span><span></span><span></span><span class="on"></span><span></span></div>
  <h1>¿Quieres ser propietario de Deltha?</h1>
  <p>Deltha es un proyecto abierto a todo el mundo. Para ser copropietario no te pedimos dinero: solo que uses la plataforma.</p>
  <p><b>El 50 % de la propiedad de Deltha se repartirá entre todos los usuarios registrados que tengan aura <?= $max ?> el <?= $dlTxt ?>.</b></p>
  <ul>
    <li><b>Así sube el aura.</b> +<?= $aPort ?> por cada envío tuyo que llega a portada, +<?= $aEnv ?> por cada envío o artículo, +<?= $aCom ?> por cada comentario. Como mucho +<?= $aCap ?> al día.</li>
    <li><b>Así baja.</b> Cada día pierdes <?= $aDec ?>, hasta el mínimo de 5. El aura no se acumula: se sostiene. Mide quién está, no quién estuvo.</li>
  </ul>
  <p class="hint">Pulsa para continuar</p>
</a>

<div class="slide" id="s8">
  <div class="dots"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span class="on"></span></div>
  <img class="logo l" src="/img/logo.png" alt=""><img class="logo d" src="/img/logo-dark.png" alt="">
  <h1>¿Quieres probar Deltha?</h1>
  <div class="choice"><a class="yes" href="<?= $entrar ?>">Sí</a><a class="no" href="#s1">No</a></div>
</div>

</div>
</body></html>
