<?php
define('DDD_PUBLIC', true);   // la presentacion se ve sin clave; se llega desde «¿Que es Deltha?» (la puerta lleva directa a la clave)
require __DIR__ . '/../../lib/app.php';
$entrar = gate_open() ? '/' : '/delta/clave.php';
$gm = (int)cfg('ghost_minutes', 30);
$max = (int)cfg('aura_max', 50);
$n = fn($k, $d) => rtrim(rtrim(number_format((float)cfg($k, $d), 3, ',', ''), '0'), ',');
$aPort = $n('aura_portada', 0.5); $aEnv = $n('aura_envio', 0.1); $aCom = $n('aura_comentario', 0.02);
$aCap = $n('aura_daily_cap', 2); $aDec = $n('aura_daily_decay', 0.5);
$dl = strtotime(cfg('aura_deadline', '2026-12-31'));
$meses = explode(',', t('enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre'));
$dlTxt = t('%d de %s', (int)date('j', $dl), $meses[(int)date('n', $dl) - 1]) . t(' de %d', (int)date('Y', $dl));
$commAura = $n('comm_min_aura', 10); $commMax = (int)cfg('comm_max_per_user', 3);
$N = 10;   // diapositivas
$dots = function (int $i) use ($N): string { $h = '<div class="dots">'; for ($k = 1; $k <= $N; $k++) $h .= '<span' . ($k === $i ? ' class="on"' : '') . '></span>'; return $h . '</div>'; };
// muestras de plantillas: fondo, texto, acento, (fondo de cabecera), radio  — solo color y forma, en miniatura
$SK = [
    'editorial' => ['Editorial',     '#ffffff', '#111111', '#111111', '#ffffff', '8px'],
    'comunidad' => ['Comunidad',     '#E9EEF2', '#444444', '#3F6787', '#557EA0', '4px'],
    'delta'     => ['Delta',         '#f5efe3', '#171614', '#2f6b3f', '#f5efe3', '4px'],
    'flujo'     => ['Flujo',         '#ffffff', '#111111', '#111111', '#ffffff', '6px'],
    'retro'     => ['Retrocomputer', '#f3efd8', '#1f3d2a', '#1f3d2a', '#f3efd8', '0'],
    'manga'     => ['Manga',         '#ffffff', '#0a0a0a', '#0a0a0a', '#ffffff', '2px'],
    'punk'      => ['Punk',          '#0b0b0b', '#f2f2f2', '#ff2bd6', '#0b0b0b', '0'],
    'popart'    => ['Pop Art',       '#ffffff', '#111111', '#FFD91A', '#F23838', '3px'],
    'zen'       => ['Zen',           '#eef3f2', '#2b3a55', '#d98ba0', '#eef3f2', '3px'],
    'orange'    => ['Orange',        '#F5F5F3', '#1a1a1a', '#FF6400', '#F5F5F3', '6px'],
    'foro'      => ['Foro',          '#DAE0E6', '#1c1c1c', '#FF4500', '#ffffff', '16px'],
    'hyperpop'  => ['Hyperpop / Y2K','#F6F7FB', '#111111', '#FF4FD8', '#ffffff', '18px'],
    'neobrutal' => ['Neo-Brutal',    '#FFFCE6', '#111111', '#FFE600', '#FFFCE6', '2px'],
    'stickerbomb' => ['Stickerbomb', '#ECEBE7', '#111111', '#FFE14D', '#ECEBE7', '12px'],
    'riso'      => ['Riso / Zine',   '#F3E7D3', '#1a1a1a', '#2450FF', '#F3E7D3', '0'],
    'aero'      => ['Frutiger Aero', '#EAF6FF', '#12324a', '#24A4F5', '#ffffff', '20px'],
    'hud'       => ['Game HUD',      '#050807', '#e6efe9', '#34FF79', '#050807', '5px'],
    'acid'      => ['Acid Web',      '#0a0a0a', '#f4f4f4', '#C8FF00', '#0a0a0a', '3px'],
    'pixel'     => ['Pixel / 16-bit','#e9ecf5', '#14172b', '#3854D8', '#e9ecf5', '0'],
    'dopamine'  => ['Dopamine',      '#FFFCF5', '#111111', '#FF6B6B', '#FFFCF5', '22px'],
    'www1998'   => ['WWW 1998',      '#C0C0C0', '#000000', '#0000EE', '#C0C0C0', '0'],
    'solarpunk' => ['Solarpunk',     '#F4F0DF', '#23301f', '#557C4C', '#F4F0DF', '10px'],
];
?>
<!doctype html><html lang="<?= lang() ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<link rel="icon" href="<?= icon_src(64) ?>">
<title>Deltha · <?= t('presentación') ?></title>
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
a.hint { text-decoration: none; }
.hint { position: absolute; bottom: calc(28px + env(safe-area-inset-bottom)); left: 0; right: 0; text-align: center; font-size: 14px; color: var(--muted); }
.dots { position: absolute; top: 20px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px; }
.dots span { width: 8px; height: 8px; border-radius: 50%; background: var(--line2); }
.dots span.on { background: var(--accent); }
.aura-row { display: flex; gap: 18px; align-items: flex-end; margin: 8px 0 18px; }
.aura-row .av { width: 44px; height: 44px; font-size: 18px; }
.aura-row small { display: block; font-size: 12px; color: var(--muted); margin-top: 6px; }
.skins { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px 8px; max-width: 560px; margin: 4px 0 18px; }
.sk { --b: #fff; --f: #111; --a: #111; --h: #fff; --r: 6px; width: 52px; display: flex; flex-direction: column; align-items: center; gap: 5px; }
.sk .m { width: 52px; height: 70px; border-radius: 7px; background: var(--b); color: var(--f); border: 1px solid color-mix(in srgb, var(--f) 30%, var(--b)); overflow: hidden; display: flex; flex-direction: column; gap: 4px; padding: 5px; box-sizing: border-box; }
.sk .m i { display: block; height: 4px; border-radius: 2px; background: var(--f); opacity: .85; }
.sk .m i.h { height: 7px; background: var(--h); border: 1px solid color-mix(in srgb, var(--f) 40%, var(--b)); opacity: 1; margin: -1px 0 2px; border-radius: 2px; }
.sk .m i.s { width: 70%; opacity: .45; }
.sk .m i.p { height: 18px; border-radius: var(--r); background: color-mix(in srgb, var(--f) 22%, var(--b)); opacity: 1; margin-top: 1px; }
.sk .m i.a { width: 40%; height: 5px; background: var(--a); opacity: 1; margin-top: auto; }
.sk b { font-size: 10px; font-weight: 500; color: var(--muted); line-height: 1.1; text-align: center; }
</style></head><body>
<div class="deck">

<a class="slide" id="s1" href="#s2">
  <?= $dots(1) ?>
  <img class="logo l" src="<?= h(logo_src('light')) ?>" alt=""><img class="logo d" src="<?= h(logo_src('dark')) ?>" alt="">
  <h1><?= t('Hola.<br>Parece que has sido seleccionado para conocer Deltha.') ?></h1>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s2" href="#s3">
  <?= $dots(2) ?>
  <h1><?= t('Deltha es') ?></h1>
  <ul>
    <li><?= t('Un agregador de noticias.') ?></li>
    <li><?= t('Una red social contralgorítmica.') ?></li>
    <li><?= t('Un espacio para el debate.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s3" href="#s4">
  <?= $dots(3) ?>
  <h1><?= t('De dónde viene') ?></h1>
  <ul>
    <li><b><?= t('El manifiesto contralgorítmico') ?></b> <?= t('La idea de fondo: recuperar el criterio humano frente a la hegemonía del algoritmo.') ?></li>
    <li><b>RSS</b> <?= t('La manera de leer: cada web publica en abierto y se la sigue sin intermediarios, en orden y sin filtros.') ?></li>
    <li><b>Digg</b> <?= t('El agregador de noticias: la comunidad decide qué llega a portada.') ?></li>
    <li><b>Reddit</b> <?= t('Las comunidades: cualquiera puede abrir una y cuidarla en torno a lo que le importa.') ?></li>
    <li><b>X</b> <?= t('El diseño y la usabilidad: una línea de tiempo limpia y rápida.') ?></li>
    <li><b>4chan</b> <?= t('La efimeridad: los hilos nacen, se discuten y desaparecen.') ?></li>
    <li><b><?= t('El modelo de negocio de Jot Down') ?></b> <?= t('Sin publicidad programática ni rastreo: una comunidad que sostiene lo que lee.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s4" href="#s5">
  <?= $dots(4) ?>
  <h1><?= t('Cómo funciona') ?></h1>
  <ul>
    <li><b><?= t('Es efímera.') ?></b> <?= t('Los envíos y los comentarios desaparecen a las %d horas. Lo que importa es lo que pasa ahora, no lo que se acumula.', (int)cfg('thread_ttl_hours', 48)) ?></li>
    <li><b><?= t('No deja huella.') ?></b> <?= t('Sin cookies, sin rastreo, sin publicidad programática. Puedes comprobarlo abriendo las herramientas de tu navegador.') ?></li>
    <li><b><?= t('Es horizontal.') ?></b> <?= t('Todo se publica bajo un alias y el nombre del autor solo aparece a los %d minutos, para que se juzguen las palabras antes que a quien las dice.', $gm) ?></li>
    <li><b><?= t('Dos portadas y un radar.') ?></b> <?= t('Lo que envía la gente espera en el radar hasta que sus votos lo llevan a una de las dos portadas: Medios o Substacks. Los originales de la comunidad tienen su propia pestaña.') ?></li>
    <li><b><?= t('Se ve por dentro.') ?></b> <?= t('La sección Procesos enseña en directo el radar, el δ de cada envío y cada voto, comentario y portada según ocurren.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s5" href="#s6">
  <?= $dots(5) ?>
  <h1><?= t('El aura') ?></h1>
  <div class="aura-row">
    <div><span class="av" style="--ring:.2;--glow:0px">a</span><small><?= t('anónimo') ?> · 5</small></div>
    <div><span class="av" style="--ring:.6;--glow:4px">g</span><small>ghost · 25</small></div>
    <div><span class="av" style="--ring:1;--glow:8px">d</span><small><?= t('comprometido') ?> · <?= $max ?></small></div>
  </div>
  <ul>
    <li><b><?= t('No hay karma.') ?></b> <?= t('Hay aura, y mide una sola cosa: tu compromiso con la comunidad. Se gana estando y aportando, no gustando.') ?></li>
    <li><b><?= t('Nunca da altavoz.') ?></b> <?= t('El aura no sube noticias ni ordena comentarios. Todo el mundo ve lo mismo, en el mismo orden. Se ve como luz alrededor de tu alias, nada más.') ?></li>
    <li><b><?= t('Sin algoritmo oculto.') ?></b> <?= t('Lo que llega a portada lo decide una fórmula pública que se muestra bajo cada envío. Y solo se filtra lo ilegal, con una moderación propia que corre en nuestros servidores.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s6" href="#s7">
  <?= $dots(6) ?>
  <h1><?= t('¿Qué se puede publicar?') ?></h1>
  <ul>
    <li><b><?= t('Todo lo que permite la ley.') ?></b> <?= t('El límite no lo pone una línea editorial ni un gusto: lo pone la ley. Fuera quedan los insultos, los delitos de odio, las amenazas y todo lo que sea delito.') ?></li>
    <li><b><?= t('Moderan personas, con la ayuda de una IA propia.') ?></b> <?= t('Todo se publica al momento. Después, cuando está activada, lo revisa una IA que funciona en nuestros propios equipos: no hay terceros mirando lo que escribes. La IA nunca borra nada: señala lo dudoso y, como mucho, lo oculta de forma provisional. La decisión es siempre de una persona.') ?></li>
    <li><b><?= t('Y hay un correo para todo lo demás.') ?></b> <?= t('Quejas, felicitaciones y propuestas:') ?> <b>info@deltha.eu</b>.</li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s7" href="#s8">
  <?= $dots(7) ?>
  <h1><?= t('Temas y comunidades') ?></h1>
  <ul>
    <li><b><?= t('Los temas los pone %s.', h(cfg('site_name', 'Deltha'))) ?></b> <?= t('Pocos y estables: actualidad, ciencia, cultura… Cada envío lleva uno, y el tema puede dar un pequeño empujón al δ (un bonus público que se suma).') ?></li>
    <li><b><?= t('Las comunidades las crea la gente.') ?></b> <?= t('Con más de %s de aura puedes abrir hasta %d: un nombre, un color y de qué va. Un envío puede sumarse a una, y quien la creó (o un moderador) la cuida.', $commAura, $commMax) ?></li>
    <li><b><?= t('Cada comunidad tiene su aire.') ?></b> <?= t('Puede elegir una skin propia, que se aplica a su página y a sus hilos. Y tú puedes elegir una comunidad o un tema como página de entrada.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s8" href="#s9">
  <?= $dots(8) ?>
  <h1><?= t('A tu manera') ?></h1>
  <div class="skins">
<?php foreach ($SK as $k => [$name, $bg, $fg, $ac, $hd, $r]): ?>
    <span class="sk" style="--b:<?= $bg ?>;--f:<?= $fg ?>;--a:<?= $ac ?>;--h:<?= $hd ?>;--r:<?= $r ?>"><span class="m"><i class="h"></i><i></i><i class="s"></i><i class="p"></i><i class="a"></i></span><b><?= h($name) ?></b></span>
<?php endforeach; ?>
  </div>
  <ul>
    <li><b><?= t('%d skins.', count($SK)) ?></b> <?= t('Mismas portadas, mismos números, distinta piel: de revista, de tarjetas, de terminal, de manga, de fanzine… Se elige en el perfil, y cada una tiene versión clara y oscura.') ?></li>
    <li><b><?= t('En tu idioma y en tu bolsillo.') ?></b> <?= t('Ocho idiomas: español, catalán, euskera, gallego, inglés, francés, alemán e italiano. Se instala como app en el móvil, con mensajes privados entre registrados. Nada de esto cambia lo que ves: solo cómo lo ves.') ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s9" href="#s10">
  <?= $dots(9) ?>
  <h1><?= t('¿Quieres ser propietario de Deltha?') ?></h1>
  <p><?= t('Deltha es un proyecto abierto a todo el mundo. Para ser copropietario no te pedimos dinero: solo que uses la plataforma.') ?></p>
  <p><b><?= t('El 50 %% de la propiedad de Deltha se repartirá entre todos los usuarios registrados que tengan aura %s el %s.', $max, $dlTxt) ?></b></p>
  <ul>
    <li><b><?= t('Así sube el aura.') ?></b> <?= t('+%s por cada envío tuyo que llega a portada, +%s por cada envío u original, +%s por cada comentario. Como mucho +%s al día.', $aPort, $aEnv, $aCom, $aCap) ?></li>
    <li><b><?= t('Así baja.') ?></b> <?= t('Cada día pierdes %s, hasta el mínimo de 5. El aura no se acumula: se sostiene. Mide quién está, no quién estuvo.', $aDec) ?></li>
  </ul>
  <p class="hint"><?= t('Pulsa para continuar') ?></p>
</a>

<a class="slide" id="s10" href="<?= $entrar ?>">
  <?= $dots(10) ?>
  <img class="logo l" src="<?= h(logo_src('light')) ?>" alt=""><img class="logo d" src="<?= h(logo_src('dark')) ?>" alt="">
  <h1><?= t('¿Quieres probar Deltha?') ?></h1>
  <p class="hint"><?= t('Pulsa para entrar') ?></p>
</a>

</div>
<script>
// Opcional (sin JS todo funciona pulsando): Enter, espacio o → avanzan; ← retrocede.
document.addEventListener('keydown', function (e) {
  var cur = document.querySelector('.slide:target') || document.getElementById('s1');
  if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowRight') { e.preventDefault(); if (cur.getAttribute('href')) location.href = cur.getAttribute('href'); }
  else if (e.key === 'ArrowLeft') { var n = parseInt(cur.id.slice(1), 10); if (n > 1) location.hash = '#s' + (n - 1); }
});
</script>
</body></html>
