-- migra-095: verificacion en dos pasos por correo (lib/twofa.php, /login2.php). Quien marca la casilla en su perfil
-- (users.twofa = 1) recibe, tras acertar la contraseña, un codigo de seis cifras en su correo y lo escribe para entrar.
-- config.twofa_admin = 1 lo exige a todos los administradores: va APAGADO de serie, para encenderlo en admin -> Config
-- cuando conste que el correo de deltha.eu llega (SPF/DKIM); si no, un administrador podria quedarse fuera.
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN twofa TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS login_codes (
    user_id   INT UNSIGNED NOT NULL PRIMARY KEY,
    code_hash CHAR(64) NOT NULL,                 -- HMAC del codigo con el secreto del sitio: el codigo no se guarda
    expires   DATETIME NOT NULL,
    tries     TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- intentos fallidos con este codigo
    sent      DATETIME NOT NULL,
    sends     TINYINT UNSIGNED NOT NULL DEFAULT 1,   -- codigos enviados en la ultima hora
    CONSTRAINT fk_lc_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('twofa_admin',   '0',  '1 = todos los administradores necesitan el codigo por correo para entrar (encender solo cuando conste que el correo llega)'),
('twofa_minutes', '10', 'Minutos que vale el codigo de acceso enviado por correo (verificacion en dos pasos)');
