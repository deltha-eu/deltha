-- migra-214 (24/11/2026), pedido por Ángel: «Hoy un usuario ha sumado 5 puntos porque ha enviado 67 noticias. Vamos a topar en 15
--  como máximo: 10 en substack / 10 medios. 15 combinados.» Tope diario de envíos por persona en /enviar (lib/topes.php,
--  envio_tope_dia), que se suma al de por hora (migra-155): en 24 horas, como mucho 15 noticias en total y, dentro de esas, como
--  mucho 10 de las que van a la portada de Medios y 10 de las que van a la de Substacks. Cuenta igual para una cuenta (user_id) que
--  para un anónimo (anon_key). No cuentan los hilos de los foros ni lo que envían los agentes. El admin no tiene tope. 0 = sin tope.
--  Solo config: no cambia ninguna tabla. Idempotente.
SET NAMES utf8mb4;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('send_person_per_day',           '15', 'Tope diario de envios por persona (migra-214): noticias que una cuenta o un anonimo puede enviar en 24 horas, de cualquier portada; 0 = sin tope'),
('send_person_per_day_medios',    '10', 'Tope diario por persona de noticias que van a la portada de Medios (dentro del total de send_person_per_day); 0 = sin tope'),
('send_person_per_day_substacks', '10', 'Tope diario por persona de noticias que van a la portada de Substacks (dentro del total de send_person_per_day); 0 = sin tope');
