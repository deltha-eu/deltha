-- Presencia anonima para /estadisticas.php: huella HMAC truncada (ip+navegador+dia) y ultima vez vista.
-- Se borra sola pasados 10 minutos. Nunca contiene la IP.
CREATE TABLE IF NOT EXISTS presence (
    fp    CHAR(16) NOT NULL PRIMARY KEY,
    seen  DATETIME NOT NULL,
    INDEX ix_seen (seen)
) ENGINE=InnoDB;
-- Frase correspondiente en la politica de privacidad (si ya estaba sembrada por migra-009 sin ella)
UPDATE pages SET body = REPLACE(body, 'que existen en cualquier servidor web, sirven para mantener el servicio y detectar abusos, y se borran en un plazo breve.</p>', 'que existen en cualquier servidor web, sirven para mantener el servicio y detectar abusos, y se borran en un plazo breve. Para saber cuántas personas hay en el sitio en cada momento guardamos durante unos minutos una huella no reversible de tu visita (calculada a partir de la IP, el navegador y el día, y que no permite recuperarlos); se borra sola a los diez minutos y no se usa para nada más.</p>') WHERE slug = 'privacidad';
