-- migra-116 (05/10/2026): los medios y substacks que sigue cada usuario se ven en su ficha publica (/u.php, pestañas «Medios» y
-- «Substacks»), como «a quien sigue». Casilla en el perfil para ocultarlos (users.show_follows); por defecto se muestran, tambien
-- en las cuentas que ya existen (Angel: publico por defecto con interruptor). Pedido por Angel.
SET NAMES utf8mb4;
ALTER TABLE users ADD COLUMN show_follows TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = los medios y substacks que sigue se ven en su ficha' AFTER src_mylang;
