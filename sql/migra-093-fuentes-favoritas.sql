-- migra-093: medios y substacks favoritos sin limite. Antes cada usuario elegia hasta tres de cada grupo en tres desplegables del
-- perfil; ahora marca los que quiera con la estrella (catalogo, pagina de la fuente, tarjeta de cada noticia, hilo) y los ve todos
-- en las pestañas «Mis medios» y «Mis substacks» del perfil (/mis-fuentes.php). En el menu lateral salen los que han publicado mas
-- recientemente: media_max_per_user / news_max_per_user pasan a ser «cuantos salen en el menu» (3), no «cuantos se pueden elegir».
-- La tabla es la misma (user_media): lo ya elegido se conserva. pos deja de ser 1..3 y pasa a ser el orden de guardado.
SET NAMES utf8mb4;

ALTER TABLE user_media MODIFY pos SMALLINT UNSIGNED NOT NULL DEFAULT 1;
