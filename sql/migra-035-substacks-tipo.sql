-- Correccion: las seis newsletters entraron como 'medio' (version antigua de la 034) y el maximo de medios subio a 5.
UPDATE sources SET kind = 'newsletter' WHERE slug IN ('jorgecarrion','periodismo','sergioparra','error500','uriondo','martinsacristan');
UPDATE sources SET featured = 1 WHERE slug IN ('jorgecarrion','sergioparra','error500');
UPDATE config SET cvalue = '3' WHERE ckey = 'media_max_per_user';
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES ('news_max_per_user', '3', 'Newsletters (Substack) que puede elegir cada usuario en su perfil («Mis substacks»)');
-- quien tuviera una newsletter elegida como «medio» la pierde de ese grupo (ahora se elige en «Mis substacks»)
DELETE um FROM user_media um JOIN sources s ON s.source_id = um.source_id WHERE s.kind = 'newsletter';
