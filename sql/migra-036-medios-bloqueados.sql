-- Medios bloqueados: dominios cuyos enlaces no se admiten (moderacion, /moderacion.php#medios).
-- Como banned_domains pero para enlaces; cubre subdominios (actualidad.rt.com cae con rt.com).
CREATE TABLE IF NOT EXISTS banned_sources (
    domain   VARCHAR(120) NOT NULL PRIMARY KEY,
    reason   VARCHAR(120) DEFAULT NULL,
    added_by INT UNSIGNED DEFAULT NULL,
    created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- Medios rusos con emision y difusion prohibidas en la UE (Reglamento 833/2014, anexo XV, y ampliaciones).
INSERT IGNORE INTO banned_sources (domain, reason) VALUES
('rt.com','sancionado por la UE'),('rt.ru','sancionado por la UE'),('ruptly.tv','sancionado por la UE'),
('sputniknews.com','sancionado por la UE'),('sputnikglobe.com','sancionado por la UE'),('sputniknews.lat','sancionado por la UE'),
('ria.ru','sancionado por la UE'),('iz.ru','sancionado por la UE'),('rg.ru','sancionado por la UE'),
('1tv.ru','sancionado por la UE'),('vesti.ru','sancionado por la UE'),('smotrim.ru','sancionado por la UE'),
('ntv.ru','sancionado por la UE'),('ren.tv','sancionado por la UE'),('tvc.ru','sancionado por la UE'),
('tsargrad.tv','sancionado por la UE'),('katehon.com','sancionado por la UE'),('journal-neo.su','sancionado por la UE'),
('strategic-culture.org','sancionado por la UE'),('voiceofeurope.com','sancionado por la UE');
