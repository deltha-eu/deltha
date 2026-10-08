-- migra-124 (08/10/2026): titulares sin HTML. Algunos feeds traen el titulo con etiquetas («<span lang="gl">Morre Xosé Manuel
-- Beiras…</span>») y se guardaban tal cual. El codigo las quita desde hoy (texto_plano, lib/app.php) al leer feeds, al extraer la
-- og:title y en los formularios; aqui se limpian los que ya estaban en stories y source_items (MySQL 8: REGEXP_REPLACE).
SET NAMES utf8mb4;
UPDATE stories      SET title = TRIM(REGEXP_REPLACE(title, '<[a-zA-Z/][^>]*>', '')) WHERE title LIKE '%<%>%';
UPDATE stories      SET content = TRIM(REGEXP_REPLACE(content, '<[a-zA-Z/][^>]*>', '')) WHERE content_type = 'link' AND content LIKE '%<%>%';
UPDATE source_items SET title = TRIM(REGEXP_REPLACE(title, '<[a-zA-Z/][^>]*>', '')) WHERE title LIKE '%<%>%';
UPDATE stories      SET title = REPLACE(REPLACE(REPLACE(REPLACE(title, '&amp;', '&'), '&quot;', '"'), '&#039;', ''''), '&#39;', '''') WHERE title LIKE '%&%;%';
UPDATE source_items SET title = REPLACE(REPLACE(REPLACE(REPLACE(title, '&amp;', '&'), '&quot;', '"'), '&#039;', ''''), '&#39;', '''') WHERE title LIKE '%&%;%';
