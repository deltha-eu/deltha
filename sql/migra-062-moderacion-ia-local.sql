-- Moderacion con IA local (posmoderacion): todo se publica al momento y el modelo, que corre en un PC
-- de la redaccion (llama-server + Ternary Bonsai 2 27B), revisa despues a traves de /api/moderacion.php.
-- El criterio son las normas de uso (mismas categorias que report_reasons()). Lo claro puede ocultarse
-- de forma provisional (mod_auto_hide); lo decide siempre una persona en /moderacion.php#ia.
SET NAMES utf8mb4;

ALTER TABLE mod_queue
    MODIFY item_type ENUM('story','comment','chat') NOT NULL DEFAULT 'story',
    MODIFY status ENUM('queued','processing','ok','flag','hidden','needs_human','human_ok','human_removed','reject') NOT NULL DEFAULT 'queued',
    ADD COLUMN priority     TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER status,        -- 1 = reportado: pasa delante
    ADD COLUMN category     VARCHAR(16) DEFAULT NULL AFTER reason,                    -- insulto, odio, acoso, datos, ilegal, spam, otro
    ADD COLUMN score        DECIMAL(4,3) DEFAULT NULL AFTER category,                 -- confianza del modelo (0-1)
    ADD COLUMN prev_status  VARCHAR(16) DEFAULT NULL AFTER score,                     -- estado del envio antes de ocultarlo
    ADD COLUMN leased_until DATETIME DEFAULT NULL AFTER prev_status,                  -- reservado por el worker hasta
    ADD COLUMN model        VARCHAR(80) DEFAULT NULL AFTER leased_until,
    ADD COLUMN decided_by   INT UNSIGNED DEFAULT NULL AFTER model,                    -- moderador que lo cerro
    ADD KEY idx_lease (status, priority, item_id);

-- el chat puede quedar oculto provisionalmente, como comentarios y envios
ALTER TABLE chat MODIFY status ENUM('ok','gone','pending_mod') NOT NULL DEFAULT 'ok';

-- lo que hubiera del portero antiguo (premoderacion con Ollama) se cierra como revisado
UPDATE mod_queue SET status = 'needs_human' WHERE status IN ('queued','processing');

DELETE FROM config WHERE ckey IN ('mod_model', 'mod_ollama_url', 'mod_timeout_sec');
UPDATE config SET descr = '1 = la IA local revisa lo nuevo DESPUES de publicarse (posmoderacion); 0 = no se encola nada' WHERE ckey = 'mod_enabled';
UPDATE config SET descr = 'Fallos del modelo con un mismo elemento antes de pasarlo a revision humana' WHERE ckey = 'mod_max_retries';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('mod_auto_hide',  '0',    '1 = la IA oculta provisionalmente lo muy claro (confianza >= mod_hide_score) hasta que lo vea un moderador; 0 = solo señala'),
('mod_hide_score', '0.90', 'Confianza minima para ocultar provisionalmente (con mod_auto_hide = 1)'),
('mod_flag_score', '0.60', 'Confianza minima para señalar a los moderadores; por debajo se da por bueno'),
('mod_lease_sec',  '300',  'Segundos que el worker tiene reservado un elemento antes de que vuelva a la cola'),
('mod_worker_seen', '',    'Ultima conexion del worker de IA local (lo escribe /api/moderacion.php)');
