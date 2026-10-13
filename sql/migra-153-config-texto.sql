-- migra-153 (13/10/2026): config.cvalue pasa de VARCHAR(255) a TEXT: el aviso a la comunidad (aviso_txt, hasta 150 palabras, migra-152)
-- no cabia y el guardado daba «Data too long for column 'cvalue'» (500 en admin → Avisos).
ALTER TABLE config MODIFY cvalue TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
