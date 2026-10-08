-- migra-125: destacar Originales (08/10/2026).
-- Los textos propios (content_type = article) se destacan como los envios de las portadas: grupo 'originales' en la tabla pins,
-- boton «Destacar» en /originales y en el hilo, la destacada sale la primera en /originales. Sin halo automatico en la primera
-- cuando no hay ninguna: solo las que ponga el admin (sin caducidad) o un usuario con credito de aura, que dura lo que diga
-- esta clave (por defecto 4 horas; en las portadas sigue mandando pin_user_minutes, 30 min).
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('pin_user_minutes_originales', '240', 'Minutos que dura un original destacado por un usuario con credito de aura (las del admin no caducan). En las portadas manda pin_user_minutes');
