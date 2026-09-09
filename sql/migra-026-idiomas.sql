-- Idiomas nuevos de la interfaz (ca, eu, gl, de, it): users.lang ya es VARCHAR(5). Solo se retoca el texto de «Como funciona el δ».
UPDATE pages SET body = REPLACE(body, 'el <b>idioma</b> (español, inglés o francés)', 'el <b>idioma</b> (español, catalán, euskera, gallego, inglés, francés, alemán o italiano)') WHERE slug = 'algoritmo';
