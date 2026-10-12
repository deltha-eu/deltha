-- migra-143 (12/10/2026): «usar siempre mi plantilla» (users.skin_propio). Las comunidades y fuentes con skin propia la imponen por defecto
-- (community_skin, lib/app.php); con skin_propio = 1 el usuario ve siempre la suya. Se elige en /perfil (radio) y se conmuta desde la cabecera
-- de la comunidad o la fuente (/skin-propio). No afecta a los anonimos.
ALTER TABLE users ADD COLUMN skin_propio TINYINT(1) NOT NULL DEFAULT 0 AFTER skin;
