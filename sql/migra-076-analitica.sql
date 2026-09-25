-- Analitica propia, sin cookies ni terceros (lib/analitica.php).
-- hits: una fila por pagina vista con la misma huella no reversible de presence (ip+navegador+dia, HMAC truncado):
-- nunca la IP; la huella cambia cada dia, asi que nadie se puede seguir de un dia a otro. Se conservan
-- analytics_keep_days dias (para el detalle de admin); los totales diarios (ana_daily) se guardan siempre.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS hits (
    hit_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ts          DATETIME NOT NULL,
    fp          CHAR(16) NOT NULL,                    -- huella del dia (como presence.fp)
    logged      TINYINT(1) NOT NULL DEFAULT 0,        -- 1 = con cuenta (no se guarda quien)
    new_session TINYINT(1) NOT NULL DEFAULT 0,        -- 1 = primera pagina tras analytics_session_minutes sin actividad
    path        VARCHAR(190) NOT NULL,                -- ruta (+ id/c/t/portada/k/p cuando identifican el contenido)
    ref_host    VARCHAR(120) NOT NULL DEFAULT '',     -- dominio de procedencia externo ('' = directo o interno)
    device      ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
    country     CHAR(2) NOT NULL DEFAULT '',          -- cabecera CF-IPCountry si la hay
    lang        CHAR(2) NOT NULL DEFAULT '',          -- primer idioma del navegador
    INDEX ix_ts (ts),
    INDEX ix_fp_ts (fp, ts)
) ENGINE=InnoDB;

-- Totales por dia, cerrados por el cron a primera hora (scripts/promocionar.php -> ana_tick)
CREATE TABLE IF NOT EXISTS ana_daily (
    day        DATE NOT NULL PRIMARY KEY,
    visitors   INT UNSIGNED NOT NULL DEFAULT 0,       -- huellas distintas del dia
    sessions   INT UNSIGNED NOT NULL DEFAULT 0,
    pageviews  INT UNSIGNED NOT NULL DEFAULT 0,
    logged     INT UNSIGNED NOT NULL DEFAULT 0        -- paginas vistas con cuenta
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('analytics_enabled',         '1',  'Analitica propia: 1 registra las paginas vistas (hits), 0 no'),
('analytics_session_minutes', '30', 'Minutos sin actividad tras los que una visita cuenta como sesion nueva'),
('analytics_keep_days',       '90', 'Dias que se conserva el detalle de paginas vistas (los totales diarios se guardan siempre)');

UPDATE pages SET body = REPLACE(body, 'se borra sola a los diez minutos y no se usa para nada más.</p>', 'se borra sola a los diez minutos. Con esa misma huella del día contamos las páginas vistas para nuestra propia analítica (visitantes, sesiones y páginas por día, sin cookies ni servicios de terceros); como la huella cambia cada día, no permite seguir a nadie de un día a otro.</p>') WHERE slug = 'privacidad';
