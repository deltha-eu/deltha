-- migra-114 (04/10/2026): cola de destacados de usuario. En cada grupo solo hay una destacada de usuario en marcha: si alguien gasta un
-- destacado mientras otra esta en su media hora, la suya espera (starts = expires de la ultima de la cola) y empieza cuando a esa se le
-- acabe el tiempo; varias esperan por orden. Las del admin no entran en la cola (starts NULL, sin caducidad). Pedido por Angel.
SET NAMES utf8mb4;
ALTER TABLE pins ADD COLUMN starts DATETIME DEFAULT NULL COMMENT 'cuando empieza a verse; NULL o pasado = en marcha; futuro = en cola' AFTER created;
UPDATE pins SET starts = created WHERE starts IS NULL AND by_admin = 0;
