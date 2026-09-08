INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('aura_portada','0.5','Aura por envio que llega a portada'),
('aura_envio','0.1','Aura por envio o articulo publicado en nuevas'),
('aura_comentario','0.02','Aura por comentario'),
('aura_daily_cap','2','Tope de aura que se puede ganar al dia'),
('aura_daily_decay','0.5','Aura que se pierde cada dia (nunca por debajo de aura_start)'),
('aura_deadline','2026-12-31','Fecha del reparto de propiedad entre usuarios con aura maxima');
DELETE FROM config WHERE ckey='aura_per_publish';
ALTER TABLE users MODIFY aura DECIMAL(6,3) NOT NULL DEFAULT 5.000;
ALTER TABLE aura_log MODIFY amount DECIMAL(7,3) NOT NULL;
UPDATE config SET cvalue='0.02' WHERE ckey='aura_comentario';
UPDATE config SET cvalue='0.5' WHERE ckey='aura_daily_decay';
UPDATE config SET cvalue='2026-12-31' WHERE ckey='aura_deadline';
