-- migra-152 (13/10/2026): aviso a la comunidad (admin → Avisos). Texto de hasta 150 palabras que, activado, sustituye los avisos grises
-- de cabecera (<p class="notice">) de las paginas publicas (aviso_comunidad(), lib/app.php: buffer en page_header/page_footer).
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('aviso_txt', '', 'Aviso a la comunidad: texto (hasta 150 palabras) que sustituye los avisos grises de cabecera'),
('aviso_on',  '0', 'Aviso a la comunidad: 1 = activado');
