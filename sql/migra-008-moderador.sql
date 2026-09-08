-- Perfil moderador: ve y resuelve la cola de revision humana (/moderacion.php). Lo asigna el admin.
ALTER TABLE users MODIFY level ENUM('normal','mod','admin') NOT NULL DEFAULT 'normal';
