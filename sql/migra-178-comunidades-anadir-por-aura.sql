-- migra-178 (05/11/2026): añadir envios a cualquier comunidad por aura. Hasta ahora solo el dueño de la comunidad y los moderadores
-- (migra-037); con la moderacion por aura desactivada (mod_aura = 0) nadie mas podia. Permiso aparte, que no depende de mod_aura:
-- con aura > comm_add_min_aura se añade un envio ya enviado a cualquier comunidad visible (tope comm_max_per_story) y se puede
-- quitar lo que uno mismo añadio. lib/app.php: community_can_add_any(), community_can_add(), community_can_remove(). Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('comm_add_min_aura', '20', 'Aura (estricta: mayor que) desde la que se puede añadir un envio a cualquier comunidad (0 = solo dueños y moderadores)');
