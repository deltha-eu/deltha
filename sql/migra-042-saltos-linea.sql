-- Los textos guardados desde el navegador llevaban CRLF; hilo.php partia los parrafos por \n\n
-- y no los reconocia, de modo que salian saltos de mas. Se normaliza a LF lo ya guardado.
UPDATE stories  SET content = REPLACE(REPLACE(content, '\r\n', '\n'), '\r', '\n') WHERE content LIKE '%\r%';
UPDATE comments SET content = REPLACE(REPLACE(content, '\r\n', '\n'), '\r', '\n') WHERE content LIKE '%\r%';
