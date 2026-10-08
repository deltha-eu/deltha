-- migra-127: registro de bajas voluntarias (08/10/2026).
-- Cuando un usuario borra su cuenta desde /perfil (/baja) queda una fila con el nick, cuanto llevaba, que se borro y el motivo
-- que haya querido dar (cuestionario). Sin correo ni IP: solo lo que hace falta para saber por que se va la gente. Se ve en
-- admin -> Usuarios -> «Bajas».
CREATE TABLE IF NOT EXISTS bajas (
    baja_id     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(32) NOT NULL,
    registrado  DATETIME NOT NULL,                     -- users.created
    aura        DECIMAL(6,3) NOT NULL DEFAULT 0,
    envios      INT UNSIGNED NOT NULL DEFAULT 0,       -- lo que tenia al irse
    comentarios INT UNSIGNED NOT NULL DEFAULT 0,
    todo        TINYINT(1) NOT NULL DEFAULT 0,         -- 1 = pidio borrar tambien lo escrito
    motivo      VARCHAR(16) NOT NULL DEFAULT '',       -- entiendo | gusta | usuarios | admin | otra | '' (prefiere no contestar)
    motivo_txt  VARCHAR(500) DEFAULT NULL,             -- texto libre de «otra»
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created (created)
) ENGINE=InnoDB;
