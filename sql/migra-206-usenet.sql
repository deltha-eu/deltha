-- migra-206 (19/11/2026): grupos de noticias de Usenet dentro de Foros, pedido por Ángel: «que aparezcan en config todos y marcamos».
--  Deltha se conecta por NNTP a un servidor de noticias (admin → Usenet: servidor, usuario y clave), trae la lista completa de
--  grupos (usenet_groups) y el admin marca los que quiere. El cron lee solo los marcados y guarda los mensajes (usenet_msgs),
--  que se ven en /usenet (pestaña «Usenet» de Foros), en solo lectura. lib/usenet.php.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usenet_groups (
  group_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(190) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,   -- es.ciencia.misc
  descr      VARCHAR(255) NOT NULL DEFAULT '',          -- la de LIST NEWSGROUPS
  posting    CHAR(1) NOT NULL DEFAULT 'y',              -- y = abierto, n = no admite envios, m = moderado
  srv_low    BIGINT UNSIGNED NOT NULL DEFAULT 0,        -- primer y ultimo numero de articulo en el servidor (LIST ACTIVE / GROUP)
  srv_high   BIGINT UNSIGNED NOT NULL DEFAULT 0,
  activo     TINYINT(1) NOT NULL DEFAULT 0,             -- marcado en admin → Usenet: se lee y se ve
  last_num   BIGINT UNSIGNED NOT NULL DEFAULT 0,        -- ultimo numero de articulo leido
  last_fetch DATETIME NULL,
  last_error VARCHAR(255) NOT NULL DEFAULT '',
  threads    INT UNSIGNED NOT NULL DEFAULT 0,
  msgs       INT UNSIGNED NOT NULL DEFAULT 0,
  last_msg   DATETIME NULL,                             -- fecha del mensaje mas reciente guardado
  seen       DATETIME NULL,                             -- ultima vez que salio en la lista del servidor
  PRIMARY KEY (group_id),
  UNIQUE KEY uq_name (name),
  KEY idx_activo (activo, last_fetch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usenet_msgs (
  msg_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id  INT UNSIGNED NOT NULL,
  num       BIGINT UNSIGNED NOT NULL,                   -- numero de articulo en el servidor
  mid_hash  CHAR(32) CHARACTER SET ascii NOT NULL,      -- md5 del Message-ID
  thr_hash  CHAR(32) CHARACTER SET ascii NOT NULL,      -- md5 del primer References (el Message-ID del que abrio el hilo) o del propio
  root_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,         -- 0 = abre hilo; si no, msg_id del que lo abre
  subject   VARCHAR(255) NOT NULL DEFAULT '',
  author    VARCHAR(120) NOT NULL DEFAULT '',           -- solo el nombre: la direccion de correo no se guarda
  sent      DATETIME NOT NULL,
  body      MEDIUMTEXT NOT NULL,
  replies   INT UNSIGNED NOT NULL DEFAULT 0,            -- solo en el que abre hilo
  last_act  DATETIME NOT NULL,                          -- solo en el que abre hilo: fecha del ultimo mensaje
  PRIMARY KEY (msg_id),
  UNIQUE KEY uq_mid (group_id, mid_hash),
  KEY idx_thr (group_id, thr_hash),
  KEY idx_lista (group_id, root_id, last_act),
  KEY idx_hilo (root_id, sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('usenet_on',            '0',   'Usenet (migra-206): 1 = la pestaña Usenet de Foros (/usenet) la ve todo el mundo; 0 = solo admin. Los grupos se marcan en admin → Usenet'),
('usenet_host',          '',    'Usenet: servidor NNTP (p. ej. news.eternal-september.org). Se edita en admin → Usenet'),
('usenet_port',          '563', 'Usenet: puerto del servidor NNTP (563 = con TLS; 119 = sin cifrar)'),
('usenet_user',          '',    'Usenet: usuario del servidor NNTP (vacio = sin autenticacion)'),
('usenet_pass',          '',    'Usenet: clave del servidor NNTP'),
('usenet_interval_min',  '15',  'Usenet: cada cuantos minutos mira el cron si hay mensajes nuevos en cada grupo marcado'),
('usenet_per_fetch',     '100', 'Usenet: mensajes como mucho que se traen de un grupo en cada lectura'),
('usenet_first_n',       '300', 'Usenet: al marcar un grupo, cuantos de sus ultimos mensajes se traen (el resto del historico no se importa)'),
('usenet_max_kb',        '64',  'Usenet: tamaño maximo de un mensaje en KB; los mayores (binarios, adjuntos) se saltan'),
('usenet_max_crosspost', '4',   'Usenet: un mensaje enviado a mas grupos que estos a la vez se salta (casi siempre es spam); 0 = sin limite'),
('usenet_keep_days',     '0',   'Usenet: dias que se conservan los hilos sin actividad; 0 = no se borra nada');
