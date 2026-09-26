-- migra-082 — reportes de la gente: quien los decidio y cuando (26/09/2026). Los usa la pestaña Moderacion de /procesos.php, que hasta
-- ahora solo mostraba lo señalado por la IA (mod_queue); ahora lista tambien los reportes de comentarios y del chat, pendientes e historico.
ALTER TABLE comment_reports ADD COLUMN decided_by INT UNSIGNED DEFAULT NULL AFTER status, ADD COLUMN decided DATETIME DEFAULT NULL AFTER decided_by;
ALTER TABLE chat_reports    ADD COLUMN decided_by INT UNSIGNED DEFAULT NULL AFTER status, ADD COLUMN decided DATETIME DEFAULT NULL AFTER decided_by;
