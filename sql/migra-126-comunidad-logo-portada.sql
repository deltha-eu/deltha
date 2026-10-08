-- migra-126: logo y foto de portada de las comunidades (08/10/2026).
-- El creador (o un mod) sube desde «Editar la comunidad» un logo cuadrado (256x256, sustituye al punto de color) y una
-- imagen de fondo ancha (1600x400) que va detras de la cabecera de /comunidad/slug. Ficheros en public/media/comunidades/.
ALTER TABLE communities
    ADD COLUMN logo  VARCHAR(40) DEFAULT NULL AFTER color,
    ADD COLUMN cover VARCHAR(40) DEFAULT NULL AFTER logo;
