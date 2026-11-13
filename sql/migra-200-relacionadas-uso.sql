-- migra-200 (13/11/2026): ¿se usan las «Relacionadas» que salen bajo cada envío? (pedido por Ángel)
--  · related_daily: por día, cuántas veces se ha enseñado la caja (shown: una por persona, hilo y día) y cuántas se ha pulsado
--    una de sus noticias (clicks: una por persona, origen, destino y día). Lo apunta rel_cuenta() (lib/contadores.php):
--    /hilo al pintar la caja, y /hilo/N?rel=M al llegar desde ella (cuenta y redirige a la dirección limpia).
--  · Se ve en /analitica, «Uso de las relacionadas». No guarda quién ni qué noticia: solo las dos sumas del día.
-- Idempotente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS related_daily (
  day    DATE NOT NULL,
  shown  INT UNSIGNED NOT NULL DEFAULT 0,
  clicks INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
