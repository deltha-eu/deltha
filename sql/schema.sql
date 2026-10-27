-- Deltha — esquema completo (MySQL 8, InnoDB, utf8mb4)
-- Regenerado el 20/10/2026 a partir de la base real (scripts/regenerar-esquema.sh, mysqldump --no-data deltha): las tablas tal como estan
-- en produccion con las migraciones 001-167 aplicadas.
--
-- Uso (instalacion nueva; lo hacen install.sh y scripts/install-droplet.sh):
--   mysql -e "CREATE DATABASE IF NOT EXISTS deltha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql deltha < sql/schema.sql
--   for f in sql/migra-*.sql; do mysql --force deltha < "$f"; done
-- La ESTRUCTURA sale entera de aqui. Los DATOS de partida (config, paginas, temas, fuentes...) salen de los INSERT
-- del final y de las migraciones: por eso se siguen pasando todas, con --force, para que los ALTER/CREATE que ya
-- estan hechos fallen sin cortar los INSERT/UPDATE que van detras en el mismo fichero.
-- Este fichero no elige base de datos (no lleva USE) ni borra nada (no lleva DROP): se carga sobre la base que se le indique.
-- Comprobacion: scripts/probar-esquema.sh monta una base de prueba y compara su estructura con la real.
-- Al añadir una migracion que cambie la estructura, hay que reflejarla tambien aqui.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_item_seen` (
  `user_id` int unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `score` tinyint unsigned NOT NULL DEFAULT '0',
  `note` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `model` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`item_id`),
  KEY `idx_ais_user` (`user_id`,`created`),
  CONSTRAINT `fk_ais_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_reply_seen` (
  `user_id` int unsigned NOT NULL,
  `comment_id` int unsigned NOT NULL,
  `result` enum('comment','skip','fail') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'skip',
  `answer_id` int unsigned DEFAULT NULL,
  `note` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`comment_id`),
  KEY `idx_ars_user` (`user_id`,`created`),
  KEY `fk_ars_comment` (`comment_id`),
  CONSTRAINT `fk_ars_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ars_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_seen` (
  `user_id` int unsigned NOT NULL,
  `story_id` int unsigned NOT NULL,
  `result` enum('leased','comment','skip','fail') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'leased',
  `comment_id` int unsigned DEFAULT NULL,
  `note` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leased_until` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`story_id`),
  KEY `idx_agent_created` (`user_id`,`created`),
  KEY `fk_as_story` (`story_id`),
  CONSTRAINT `fk_as_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_sent` (
  `item_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `story_id` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_sent_user` (`user_id`,`created`),
  KEY `fk_ase_story` (`story_id`),
  CONSTRAINT `fk_ase_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `user_id` int unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `persona` text COLLATE utf8mb4_unicode_ci,
  `per_day` tinyint unsigned NOT NULL DEFAULT '4',
  `min_age_min` smallint unsigned NOT NULL DEFAULT '20',
  `max_age_h` smallint unsigned NOT NULL DEFAULT '24',
  `max_comments` tinyint unsigned NOT NULL DEFAULT '6',
  `replies` tinyint(1) NOT NULL DEFAULT '0',
  `skip_users` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topics` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hour_from` tinyint unsigned NOT NULL DEFAULT '8' COMMENT 'comenta desde esta hora (hora del servidor)',
  `hour_to` tinyint unsigned NOT NULL DEFAULT '24' COMMENT 'y hasta esta (exclusiva, 0-24); si es menor que hour_from, cruza la medianoche',
  `gap_min` smallint unsigned NOT NULL DEFAULT '25' COMMENT 'minutos entre dos comentarios suyos (por azar x0,6-1,8)',
  `memory_n` tinyint unsigned NOT NULL DEFAULT '8' COMMENT 'cuantos de sus ultimos comentarios ve el modelo (0 = ninguno)',
  `next_comment` datetime DEFAULT NULL COMMENT 'no se le ofrece nada antes de esta hora (se fija al comentar)',
  `submits_per_day` tinyint unsigned NOT NULL DEFAULT '0',
  `submit_kinds` enum('medio','newsletter','ambos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ambos',
  `submit_topics` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `submit_langs` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `submit_review` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = la IA local evalua las candidatas antes de enviar',
  `submit_brief` text COLLATE utf8mb4_unicode_ci COMMENT 'criterio de envio: que es una buena noticia para este agente',
  `next_submit` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_ag_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `allowed_domains` (
  `domain` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ana_daily` (
  `day` date NOT NULL,
  `visitors` int unsigned NOT NULL DEFAULT '0',
  `sessions` int unsigned NOT NULL DEFAULT '0',
  `pageviews` int unsigned NOT NULL DEFAULT '0',
  `logged` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aura_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `amount` decimal(7,3) NOT NULL,
  `reason` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bajas` (
  `baja_id` int unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registrado` datetime NOT NULL,
  `aura` decimal(6,3) NOT NULL DEFAULT '0.000',
  `envios` int unsigned NOT NULL DEFAULT '0',
  `comentarios` int unsigned NOT NULL DEFAULT '0',
  `todo` tinyint(1) NOT NULL DEFAULT '0',
  `motivo` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `motivo_txt` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`baja_id`),
  KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banned_domains` (
  `domain` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banned_sources` (
  `domain` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat` (
  `msg_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `alias` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `anon_key` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_to` int unsigned DEFAULT NULL,
  `content` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_orig` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `likes` int unsigned NOT NULL DEFAULT '0',
  `dislikes` int unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `status` enum('ok','gone','pending_mod') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` datetime DEFAULT NULL,
  `anuncio` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'mensaje a la comunidad (admin/mod)',
  PRIMARY KEY (`msg_id`),
  KEY `idx_created` (`status`,`created`),
  KEY `idx_reply` (`reply_to`),
  KEY `idx_user` (`user_id`,`created`),
  KEY `idx_anon` (`anon_key`,`created`),
  KEY `idx_anuncio` (`anuncio`,`msg_id`),
  CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_reports` (
  `report_id` int unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `fp` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` enum('insulto','odio','acoso','datos','ilegal','spam','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `status` enum('open','deleted','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `decided_by` int unsigned DEFAULT NULL,
  `decided` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `uq_user` (`msg_id`,`user_id`),
  UNIQUE KEY `uq_fp` (`msg_id`,`fp`),
  KEY `idx_status` (`status`,`created`),
  CONSTRAINT `fk_chr_msg` FOREIGN KEY (`msg_id`) REFERENCES `chat` (`msg_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_votes` (
  `msg_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `value` tinyint NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`msg_id`,`user_id`),
  KEY `idx_user` (`user_id`,`created`),
  CONSTRAINT `fk_chv_msg` FOREIGN KEY (`msg_id`) REFERENCES `chat` (`msg_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clicks_daily` (
  `day` date NOT NULL,
  `n` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clicks_source` (
  `day` date NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `n` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`day`,`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_reports` (
  `report_id` int unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int unsigned NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `fp` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` enum('insulto','odio','acoso','datos','ilegal','spam','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `status` enum('open','deleted','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `decided_by` int unsigned DEFAULT NULL,
  `decided` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `uq_user` (`comment_id`,`user_id`),
  UNIQUE KEY `uq_fp` (`comment_id`,`fp`),
  KEY `idx_status` (`status`,`created`),
  CONSTRAINT `fk_cr_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_votes` (
  `comment_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `value` tinyint NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`,`user_id`),
  KEY `idx_user` (`user_id`,`created`),
  CONSTRAINT `fk_cv_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `story_id` int unsigned NOT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `root_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `alias` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `anon_key` char(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aura` decimal(5,2) NOT NULL DEFAULT '5.00',
  `likes` int unsigned NOT NULL DEFAULT '0',
  `dislikes` int unsigned NOT NULL DEFAULT '0',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'imagen adjunta, ruta relativa a /media (migra-166)',
  `content_orig` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending_mod','rejected','ok') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` datetime DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_story` (`story_id`,`created`),
  KEY `fk_c_user` (`user_id`),
  KEY `idx_root` (`story_id`,`root_id`,`created`),
  KEY `idx_created` (`created`),
  FULLTEXT KEY `ft_comments` (`content`),
  CONSTRAINT `fk_c_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`),
  CONSTRAINT `fk_c_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communities` (
  `community_id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descr` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#534AB7',
  `logo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skin` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `owner_id` int unsigned NOT NULL,
  `status` enum('ok','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`community_id`),
  UNIQUE KEY `uq_slug` (`slug`),
  UNIQUE KEY `uq_name` (`name`),
  KEY `idx_owner` (`owner_id`),
  CONSTRAINT `fk_co_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `ckey` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cvalue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ckey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `user_id` int unsigned NOT NULL,
  `story_id` int unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`story_id`),
  KEY `idx_fav_created` (`user_id`,`created`),
  KEY `fk_fav_story` (`story_id`),
  CONSTRAINT `fk_fav_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forums` (
  `forum_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descr` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('on','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on',
  `position` tinyint unsigned NOT NULL DEFAULT '1',
  `orden` enum('fecha','actividad') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fecha',
  `min_aura` decimal(6,1) NOT NULL DEFAULT '0.0' COMMENT 'aura minima (estricta) para ver y escribir; 0 = abierto',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`forum_id`),
  UNIQUE KEY `uq_forum_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hits` (
  `hit_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts` datetime NOT NULL,
  `fp` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logged` tinyint(1) NOT NULL DEFAULT '0',
  `new_session` tinyint(1) NOT NULL DEFAULT '0',
  `path` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_host` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device` enum('desktop','mobile','tablet') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desktop',
  `country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lang` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`hit_id`),
  KEY `ix_ts` (`ts`),
  KEY `ix_fp_ts` (`fp`,`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_generic` (
  `domain` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`domain`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_seen` (
  `seen_id` int unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seen_id`),
  KEY `idx_imgseen_hash` (`domain`,`hash`),
  KEY `idx_imgseen_file` (`file`),
  KEY `idx_imgseen_seen` (`seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_codes` (
  `user_id` int unsigned NOT NULL,
  `code_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  `tries` tinyint unsigned NOT NULL DEFAULT '0',
  `sent` datetime NOT NULL,
  `sends` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_lc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `msg_id` int unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int unsigned NOT NULL,
  `to_id` int unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`msg_id`),
  KEY `idx_to_unread` (`to_id`,`read_at`),
  KEY `idx_from` (`from_id`,`created`),
  KEY `idx_pair` (`from_id`,`to_id`,`msg_id`),
  CONSTRAINT `fk_m_from` FOREIGN KEY (`from_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_m_to` FOREIGN KEY (`to_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mod_log` (
  `log_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `action` varchar(60) NOT NULL,
  `detail` varchar(800) DEFAULT NULL,
  `result` varchar(600) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`,`log_id`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mod_queue` (
  `item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `item_type` enum('story','comment','chat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'story',
  `ref_id` int unsigned NOT NULL,
  `status` enum('queued','processing','ok','flag','hidden','needs_human','human_ok','human_removed','reject') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `priority` tinyint unsigned NOT NULL DEFAULT '0',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `verdict` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reason` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` decimal(4,3) DEFAULT NULL,
  `prev_status` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leased_until` datetime DEFAULT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decided_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `idx_status` (`status`,`created`),
  KEY `idx_ref` (`item_type`,`ref_id`),
  KEY `idx_lease` (`status`,`priority`,`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notif_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `from_id` int unsigned DEFAULT NULL,
  `from_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `kind` enum('mention_comment','reply_comment','mention_chat','reply_chat','dm','mention_story') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int unsigned NOT NULL DEFAULT '0',
  `story_id` int unsigned DEFAULT NULL,
  `snippet` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`notif_id`),
  KEY `idx_user_unread` (`user_id`,`read_at`),
  KEY `idx_user_list` (`user_id`,`created`),
  KEY `idx_dm_open` (`user_id`,`from_id`,`kind`,`read_at`),
  KEY `fk_n_from` (`from_id`),
  CONSTRAINT `fk_n_from` FOREIGN KEY (`from_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_n_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `slug` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pins` (
  `pin_id` int unsigned NOT NULL AUTO_INCREMENT,
  `what` enum('story','item') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int unsigned NOT NULL,
  `grupo` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned NOT NULL,
  `by_admin` tinyint(1) NOT NULL DEFAULT '0',
  `cost` decimal(5,2) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `starts` datetime DEFAULT NULL COMMENT 'cuando empieza a verse; NULL o pasado = en marcha; futuro = en cola',
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`pin_id`),
  UNIQUE KEY `uq_ref` (`what`,`ref_id`),
  KEY `idx_grupo` (`what`,`grupo`,`by_admin`,`expires`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_pin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presence` (
  `fp` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seen` datetime NOT NULL,
  PRIMARY KEY (`fp`),
  KEY `ix_seen` (`seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `story_id` int unsigned NOT NULL,
  `portada` enum('medios','substacks','canales') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delta` decimal(8,3) NOT NULL,
  `candidates` smallint unsigned NOT NULL,
  `commenters` smallint unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_story` (`story_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reg_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `fp` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_fp` (`fp`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reg_log` (
  `reg_id` int unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `login` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_id` int unsigned DEFAULT NULL,
  `estado` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `motivo` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `activated` datetime DEFAULT NULL,
  PRIMARY KEY (`reg_id`),
  KEY `idx_reglog_created` (`created`),
  KEY `idx_reglog_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `source_errors` (
  `err_id` int unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int unsigned NOT NULL,
  `errhash` char(32) NOT NULL,
  `error` varchar(255) NOT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  `times` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`err_id`),
  UNIQUE KEY `uq_src_err` (`source_id`,`errhash`),
  KEY `last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `source_items` (
  `item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `source_id` smallint unsigned NOT NULL,
  `ext_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phase` enum('nuevas','portada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nuevas',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thread_url` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` varchar(48) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `votes` int NOT NULL DEFAULT '0',
  `negatives` int NOT NULL DEFAULT '0',
  `comments` int NOT NULL DEFAULT '0',
  `pub_date` datetime NOT NULL,
  `first_seen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `uq_ext` (`source_id`,`ext_id`),
  KEY `idx_phase_date` (`source_id`,`phase`,`pub_date`),
  KEY `idx_phase_votes` (`source_id`,`phase`,`votes`),
  KEY `idx_seen` (`last_seen`),
  FULLTEXT KEY `ft_items` (`title`,`content`),
  CONSTRAINT `fk_si_source` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `source_proposals` (
  `prop_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `kind` enum('medio','newsletter','youtube','agregador') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medio',
  `name` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `home` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feed` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descr` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `test_items` smallint unsigned NOT NULL DEFAULT '0',
  `test_info` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','ok','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reply` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` smallint unsigned DEFAULT NULL,
  `reviewed_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed` datetime DEFAULT NULL,
  PRIMARY KEY (`prop_id`),
  KEY `idx_fp_status` (`status`,`created`),
  KEY `idx_fp_user` (`user_id`,`created`),
  CONSTRAINT `fk_fp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sources` (
  `source_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` enum('agregador','medio','newsletter','youtube') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medio',
  `name` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `home` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descr` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `skin` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `topic_id` smallint unsigned DEFAULT NULL,
  `feed_portada` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feed_nuevas` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `interval_min` smallint unsigned NOT NULL DEFAULT '5',
  `status` enum('ok','off') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `relay` tinyint(1) NOT NULL DEFAULT '0',
  `last_fetch` datetime DEFAULT NULL,
  `last_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `listed` tinyint(1) NOT NULL DEFAULT '1',
  `added_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`source_id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stories` (
  `story_id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_type` enum('link','article') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link',
  `user_id` int unsigned DEFAULT NULL,
  `anon_key` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_alias` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revealed` tinyint(1) NOT NULL DEFAULT '0',
  `topic_id` smallint unsigned NOT NULL,
  `community_id` int unsigned DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pub_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending_mod','rejected','nuevas','destacadas','archived','discard') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nuevas',
  `portada` enum('medios','substacks','canales') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forum_id` smallint unsigned DEFAULT NULL COMMENT 'foro del texto propio (content_type = article); NULL en los enlaces',
  `sent_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` datetime DEFAULT NULL,
  `indexable` tinyint(1) NOT NULL DEFAULT '0',
  `published_date` datetime DEFAULT NULL,
  `votes_pos` int NOT NULL DEFAULT '0',
  `votes_neg` int NOT NULL DEFAULT '0',
  `eff_pos` int NOT NULL DEFAULT '0',
  `eff_neg` int NOT NULL DEFAULT '0',
  `comments` int NOT NULL DEFAULT '0',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT 'entradas al hilo',
  `clicks` int unsigned NOT NULL DEFAULT '0' COMMENT 'salidas al medio (/ir.php)',
  `delta` decimal(8,3) NOT NULL DEFAULT '0.000',
  `delta_calc` datetime DEFAULT NULL,
  PRIMARY KEY (`story_id`),
  KEY `idx_status_sent` (`status`,`sent_date`),
  KEY `idx_status_delta` (`status`,`delta`),
  KEY `idx_status_pub` (`status`,`published_date`),
  KEY `idx_source` (`source`),
  KEY `fk_st_user` (`user_id`),
  KEY `fk_st_topic` (`topic_id`),
  KEY `idx_comm` (`community_id`,`sent_date`),
  KEY `idx_portada` (`portada`,`status`,`published_date`),
  KEY `idx_lang` (`lang`),
  KEY `idx_anon_sent` (`anon_key`,`sent_date`),
  KEY `idx_pub_sent` (`pub_key`,`sent_date`),
  KEY `idx_forum` (`forum_id`,`status`,`sent_date`),
  FULLTEXT KEY `ft_stories` (`title`,`content`),
  CONSTRAINT `fk_st_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`),
  CONSTRAINT `fk_st_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_communities` (
  `story_id` int unsigned NOT NULL,
  `community_id` int unsigned NOT NULL,
  `added_by` int unsigned DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`story_id`,`community_id`),
  KEY `idx_comm` (`community_id`,`created`),
  KEY `fk_sc_user` (`added_by`),
  CONSTRAINT `fk_sc_comm` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_user` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_aliases` (
  `story_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `alias` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`story_id`,`user_id`),
  KEY `fk_ta_user` (`user_id`),
  CONSTRAINT `fk_ta_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`),
  CONSTRAINT `fk_ta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topics` (
  `topic_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bonus` decimal(4,2) NOT NULL DEFAULT '0.00',
  `color` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#534AB7',
  PRIMARY KEY (`topic_id`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_communities` (
  `user_id` int unsigned NOT NULL,
  `community_id` int unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`community_id`),
  KEY `community_id` (`community_id`),
  CONSTRAINT `fk_uc_comm` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ghosts` (
  `user_id` int unsigned NOT NULL,
  `target_id` int unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`target_id`),
  KEY `idx_ghost_target` (`target_id`),
  CONSTRAINT `fk_ug_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ug_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_media` (
  `user_id` int unsigned NOT NULL,
  `source_id` smallint unsigned NOT NULL,
  `pos` smallint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`,`source_id`),
  KEY `fk_um_source` (`source_id`),
  CONSTRAINT `fk_um_source` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `token` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_sent` datetime DEFAULT NULL,
  `reset_token` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_sent` datetime DEFAULT NULL,
  `level` enum('normal','mod','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `avatar` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` enum('auto','light','dark') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `skin` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `skin_propio` tinyint(1) NOT NULL DEFAULT '0',
  `landing` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `src_mylang` tinyint(1) NOT NULL DEFAULT '0',
  `src_langs` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'idiomas elegidos («es,en»); vacio = el de la interfaz',
  `show_follows` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = los medios y substacks que sigue se ven en su ficha',
  `aura` decimal(6,3) NOT NULL DEFAULT '5.000',
  `aura_suelo` decimal(8,3) NOT NULL DEFAULT '0.000',
  `pin_credits` smallint unsigned NOT NULL DEFAULT '0',
  `pin_mark` smallint unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` datetime DEFAULT NULL,
  `twofa` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_login` (`login`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `ix_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `story_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `value` tinyint NOT NULL,
  `fp` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`story_id`,`user_id`),
  KEY `fk_v_user` (`user_id`),
  KEY `idx_created` (`created`),
  KEY `idx_story_fp` (`story_id`,`fp`),
  CONSTRAINT `fk_v_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`),
  CONSTRAINT `fk_v_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- ---------------------------------------------------------------------------------------------
-- Datos de partida del esquema v0 (config, temas y primeras fuentes). El resto lo ponen las migraciones.
-- ---------------------------------------------------------------------------------------------

INSERT INTO config (ckey, cvalue, descr) VALUES
('site_name',           'Deltha', 'Nombre del sitio'),
('access_key',          'CAMBIAR', 'Clave temporal de acceso: mientras no este vacia, quien no la tenga ve /delta/ en vez de la portada. Cambiarla expulsa a todos.'),
('mod_enabled',         '0',    '1 = la IA local revisa lo nuevo DESPUES de publicarse (posmoderacion); 0 = no se encola nada'),
('delta_min_age_min',   '30',   'Minutos desde el envio antes de poder llegar a portada (y desde los que descuenta la antigüedad)'),
('decay_medios',        '0.05', 'Decremento de delta por media hora de espera en Medios (desde que es elegible)'),
('decay_substacks',     '0.02', 'Decremento de delta por media hora de espera en Substacks (mas lento: lo suyo no caduca con la actualidad)'),
('delta_min_publish_medios',    '1', 'Delta minimo para publicar en Medios'),
('delta_min_publish_substacks', '1', 'Delta minimo para publicar en Substacks'),
('delta_min_publish_canales', '1', 'Delta minimo para publicar en Canales'),
('decay_canales',       '0.02', 'Decremento de delta por media hora de espera en Canales'),
('portada_canales',     '1',    '1 = los enlaces a YouTube van a la portada Canales (turno condicionado); 0 = van a Medios y la pestaña no se ve'),
('canales_gap_min',     '15',   'Minutos minimos entre dos publicaciones en Canales'),
('delta_min_votes',      '2',    'Votos netos que cuentan necesarios para ser candidato a portada; el bonus del tema no sustituye a ninguno'),
('vote_quarantine_hours', '24', 'Horas desde que se crea una cuenta hasta que sus votos cuentan en el delta (se ven desde el principio)'),
('cycle_lottery',       '1',    '1 = entre las que superan el minimo se sortea con probabilidad proporcional al delta; 0 = gana la de mayor delta'),
('cycle_cede_turno',    '1',    '1 = si a la portada del turno no le llega nada, publica en la otra'),
('cycle_jitter',        '1',    '1 = el minuto de ejecucion dentro de cada ventana de cycle_minutes depende del secreto del sitio (contra el sniping); 0 = minuto 0'),
('test_mode',           '0',    '1 = modo test (beta): si el ciclo no publica nada y la ultima promocion tiene mas de 60 min, publica el enlace del radar con mas delta sin exigir edad, votos ni delta minimos'),
('aura_portada',        '0.5',  'Aura por envio que llega a portada'),
('aura_envio',          '0.1',  'Aura por envio o articulo publicado en nuevas'),
('aura_comentario',     '0.02', 'Aura por comentario'),
('aura_daily_cap',      '2',    'Tope de aura que se puede ganar al dia'),
('aura_daily_decay',    '0.5',  'Aura que se pierde cada dia (nunca por debajo de aura_start)'),
('aura_deadline',       '2027-12-31', 'Fecha del reparto de propiedad entre usuarios con aura maxima'),
('aura_anon',           '5',    'Aura del anonimo'),
('aura_start',          '5',    'Aura inicial del registrado'),
('aura_max',            '30',   'Tope de aura'),
('mod_aura',            '20',   'Aura desde la que cualquier registrado modera aunque su nivel sea normal (0 = solo por nivel mod/admin)'),
('analytics_full_aura', '10',   'Aura desde la que un registrado ve en /analitica la analitica completa (ahora, hoy, fechas, paginas, procedencia, paises); 0 = solo el admin'),
('thread_ttl_hours',    '48',   'Vida del hilo (destacadas -> archivo)'),
('nuevas_ttl_hours',    '168',  'Horas que puede pasar un envio en el radar sin llegar a portada antes de archivarse (168 = una semana; 0 = nunca)'),
('ghost_minutes',       '30',   'Minutos que dura el alias Ghost de cada envio/comentario antes de mostrar el nombre real'),
('per_page',            '20',   'Envios por pagina'),
('cycle_minutes',       '5',    'Minutos entre ciclos del cron; los ciclos alternan portada (Medios, Substacks) y corren en un minuto imprevisible de cada ventana'),
('procesos_days',       '7',    'Dias de actividad que muestra el flujo de /procesos.php'),
('processes_votes_who', 'todos', 'Quien ve la pestaña Votos de /procesos y los votos del flujo «Todo»: todos | usuarios | moderadores | admin'),
('msg_enabled',         '1',    '1 = mensajes privados entre registrados activos'),
('msg_per_hour',        '30',   'Mensajes privados maximos que puede enviar un usuario por hora'),
('notif_enabled',       '1',    '1 = avisos (campanita): menciones @nick, respuestas y privados'),
('notif_per_page',      '30',   'Avisos por pagina en /avisos.php'),
('notif_poll_sec',      '60',   'Segundos entre consultas del contador de avisos desde el navegador (0 = solo al cargar la pagina)'),
('skin_default',        'editorial', 'Skin por defecto (anonimos y quien no elija): cualquiera de DDD_SKINS'),
('comm_min_aura',       '6',    'Aura minima (estricta: mayor que) para poder crear comunidades'),
('comm_max_per_user',   '3',    'Comunidades que puede crear cada usuario'),
('comm_max_per_story',  '3',    'Comunidades en las que puede estar un mismo envio (contando la principal)'),
('side_order',          'ciclo,temas,comunidades,votadas,agregadores,medios,substacks,youtube', 'Modulos de la columna derecha (escritorio), en orden; lo que no este no se pinta'),
('sources_smf_per_run', '12',   'Foros SMF (Areopago): paginas de mensaje que se leen por pasada para sacar autor y numero del mensaje'),
('comment_edit_minutes','10',   'Minutos tras publicar en los que el autor puede editar su comentario'),
('comment_edit_min_aura','6',   'Aura minima (estricta: mayor que) para poder editar los comentarios propios'),
('comment_images',       '1',    '1 = los comentarios admiten una imagen adjunta; 0 = no'),
('comment_image_min_aura','6',  'Aura minima (estricta: mayor que) para adjuntar una imagen a un comentario'),
('comment_image_px',     '1000', 'Lado largo maximo (px) al que se reduce la imagen de un comentario'),
('article_max_words',   '1000', 'Palabras maximas de un articulo'),
('anon_alias_mode',     'ip_hash', 'ip_hash = alias estable por hilo sin cookie | random = alias nuevo por comentario'),
('anon_can_comment',    '1',    '1 = los anonimos pueden comentar'),
('anon_can_submit',     '1',    '1 = los anonimos pueden enviar'),
('img_w',               '1200', 'Ancho de la foto procesada'),
('img_h',               '900',  'Alto de la foto procesada (4:3)'),
('img_max_bytes',       '6000000', 'Tamano maximo de subida'),
('og_timeout_sec',      '3',    'Timeout para extraer og:image'),
('img_generic_min',     '3',    'Foto de la pagina o del feed repetida en este numero de noticias distintas del mismo medio = generica (logo, relleno): no se usa. 0 = no mirar'),
('curl_impersonate',    '/opt/curl-impersonate/curl_chrome116', 'Ruta a curl-impersonate (vacio = usar curl de PHP)'),
('sources_enabled',      '1',    '1 = fuentes externas visibles (/fuente.php) y lectura de sus RSS en el cron'),
('sources_agregadores',  '0',    '1 = los agregadores (Meneame, Mediatize, Renegados...) se ven y se leen; 0 = fuera de todo salvo admin > Fuentes'),
('youtube_embed',        '1',    '1 = los videos de YouTube se reproducen dentro de Deltha al pulsar la miniatura (youtube-nocookie.com); 0 = el enlace abre YouTube'),
('sources_youtube',      '1',    '1 = los canales de YouTube (fuentes de tipo youtube) se ven y se leen; 0 = fuera de todo salvo admin > Fuentes'),
('youtube_shorts',       '0',    'Al dar de alta un canal de YouTube: 0 = feed solo de videos largos (sin shorts ni directos); 1 = feed completo del canal'),
('yt_max_per_user',      '3',    'Canales de YouTube que salen en el menu de cada usuario («Mis canales»)'),
('sources_popular_hours','24',   'Fuentes: «populares» = nuevas de las ultimas N horas ordenadas por votos'),
('sources_popular_min_votes','5','Fuentes: votos minimos de una nueva para salir en «populares»'),
('sources_keep_days',    '7',    'Fuentes: dias que se guardan los items que ya no aparecen en los feeds'),
('sources_timeout_sec',  '8',    'Fuentes: timeout al leer cada feed'),
('media_max_per_user',   '3',    'Medios que puede elegir cada usuario en su perfil («Mis medios»)'),
('news_max_per_user',    '3',    'Newsletters (Substack) que puede elegir cada usuario en su perfil («Mis substacks»)'),
('sources_og_image',     '1',    '1 = para los medios sin imagen en el RSS se busca la og:image de la noticia'),
('sources_og_per_run',   '10',   'Fuentes: og:image maximas que se descargan en cada pasada del cron'),
('mod_max_retries',     '3',    'Fallos del modelo con un mismo elemento antes de pasarlo a revision humana'),
('mod_auto_hide',       '0',    '1 = la IA oculta provisionalmente lo muy claro (confianza >= mod_hide_score) hasta que lo vea un moderador; 0 = solo señala'),
('mod_hide_score',      '0.90', 'Confianza minima para ocultar provisionalmente (con mod_auto_hide = 1)'),
('mod_hide_links_only', '1',    '1 = la IA solo oculta provisionalmente (mod_auto_hide) lo que lleva algun enlace; sin enlace se limita a señalar. 0 = oculta por confianza, lleve enlace o no'),
('mod_flag_score',      '0.60', 'Confianza minima para señalar a los moderadores; por debajo se da por bueno'),
('mod_lease_sec',       '300',  'Segundos que el worker tiene reservado un elemento antes de que vuelva a la cola'),
('mod_worker_seen',     '',     'Ultima conexion del worker de IA local (lo escribe /api/moderacion.php)');

INSERT INTO forums (slug, name, descr, status, position, orden, min_aura) VALUES
('originales', 'Originales', 'Textos propios de la comunidad. No se permiten textos ya publicados en otros sitios ni extractos de ellos.', 'on', 1, 'fecha', 0),
('deltha',     'Deltha',     'Dudas, propuestas de mejora y conversación sobre el propio sitio.', 'on', 2, 'actividad', 0),
('hardusers',  'Hardusers',  'Foro reservado a quienes tienen más de 20 de aura. Lo que se habla aquí no sale en búsquedas ni en portadas para el resto.', 'on', 3, 'actividad', 20);

INSERT INTO topics (name, bonus, color) VALUES
('actualidad', 0.00, '#534AB7'),
('politica',   0.00, '#993C1D'),
('cultura',    0.30, '#0F6E56'),
('ciencia',    0.30, '#185FA5'),
('deportes',   0.00, '#3B6D11'),
('tecnologia', 0.00, '#5F5E5A'),
('ocio',       0.00, '#993556'),
('relatos',    0.30, '#6B3FA0');

INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min) VALUES
('meneame',  'agregador', 'Menéame',          'https://www.meneame.net/',        NULL, 'orange', NULL, 'https://www.meneame.net/rss', 'https://www.meneame.net/rss?status=queued', 5),
('jotdown',  'medio',     'Jot Down',         'https://www.jotdown.es/',         'Contemporary Culture Mag', '', 3, 'https://www.jotdown.es/feed/', '', 60),
('mercurio', 'medio',     'Revista Mercurio', 'https://www.revistamercurio.es/', 'Revista de libros y cultura', '', 3, 'https://revistamercurio.es/feed/', '', 60);

INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min) VALUES
('meneame',  'agregador', 'Menéame',          'https://www.meneame.net/',        NULL, 'orange', NULL, 'https://www.meneame.net/rss', 'https://www.meneame.net/rss?status=queued', 5),
('jotdown',  'medio',     'Jot Down',         'https://www.jotdown.es/',         'Contemporary Culture Mag', '', 3, 'https://www.jotdown.es/feed/', '', 60),
('mercurio', 'medio',     'Revista Mercurio', 'https://www.revistamercurio.es/', 'Revista de libros y cultura', '', 3, 'https://revistamercurio.es/feed/', '', 60);

INSERT INTO sources (slug, kind, name, home, descr, skin, topic_id, feed_portada, feed_nuevas, interval_min) VALUES
('meneame',  'agregador', 'Menéame',          'https://www.meneame.net/',        NULL, 'orange', NULL, 'https://www.meneame.net/rss', 'https://www.meneame.net/rss?status=queued', 5),
('jotdown',  'medio',     'Jot Down',         'https://www.jotdown.es/',         'Contemporary Culture Mag', '', 3, 'https://www.jotdown.es/feed/', '', 60),
('mercurio', 'medio',     'Revista Mercurio', 'https://www.revistamercurio.es/', 'Revista de libros y cultura', '', 3, 'https://revistamercurio.es/feed/', '', 60);
