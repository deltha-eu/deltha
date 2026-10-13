-- migra-154 (13/10/2026): pins.cost guarda el aura que se pago al destacar (pin_cost del grupo en ese momento), para enseñarlo en la
-- etiqueta «destacada por nick (0,5 de aura) · 25 min» aunque el precio cambie despues. Las del admin no pagan (NULL).
ALTER TABLE pins ADD COLUMN cost DECIMAL(5,2) NULL DEFAULT NULL AFTER by_admin;
