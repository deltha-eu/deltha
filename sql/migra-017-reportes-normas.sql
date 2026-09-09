-- Reportes de comentarios, borrado con texto sustitutivo y pagina «Normas de uso».
SET NAMES utf8mb4;
ALTER TABLE comments
    ADD COLUMN deleted      TINYINT(1) NOT NULL DEFAULT 0 AFTER status,   -- 1 = borrado por moderacion: content sustituido
    ADD COLUMN content_orig TEXT DEFAULT NULL AFTER content;              -- texto original del comentario borrado (solo BD)

CREATE TABLE IF NOT EXISTS comment_reports (
    report_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    comment_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,                -- quien reporta (registrado)
    fp          CHAR(16) DEFAULT NULL,                    -- o huella HMAC ip+comentario (anonimo), no reversible
    reason      ENUM('insulto','odio','acoso','datos','ilegal','spam','otro') NOT NULL DEFAULT 'otro',
    status      ENUM('open','deleted','dismissed') NOT NULL DEFAULT 'open',
    created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (comment_id, user_id),
    UNIQUE KEY uq_fp   (comment_id, fp),
    KEY idx_status (status, created),
    CONSTRAINT fk_cr_comment FOREIGN KEY (comment_id) REFERENCES comments(comment_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO pages (slug, title, body) VALUES ('normas', 'Normas de uso', '<h1>Normas de uso</h1>

<h2>La regla</h2>
<p>En <b>{site}</b> cabe todo lo que permite la ley. No hay línea editorial ni lista de temas prohibidos: se puede discrepar, criticar con dureza, ironizar y equivocarse. El único límite es el que marca la legislación española y europea, y por eso estas normas son cortas.</p>

<h2>Lo que no cabe</h2>
<p><b>Insultar y vejar.</b> Se discute con las ideas, no contra las personas. Los insultos, las humillaciones y las descalificaciones que no aportan argumento se retiran.</p>
<p><b>Odiar.</b> Nada que incite al odio, la discriminación o la violencia contra alguien por su origen, sexo, orientación, religión, discapacidad o cualquier otra condición.</p>
<p><b>Amenazar y acosar.</b> Ni amenazas, ni persecución a un usuario de hilo en hilo, ni campañas para señalar a nadie.</p>
<p><b>Publicar datos de otros.</b> Direcciones, teléfonos, documentos, cuentas privadas, fotos íntimas, lugares de trabajo o cualquier dato que sirva para localizar o identificar a una persona que no lo ha hecho público. Aquí se llama <i>doxxing</i> y es lo más grave que se puede hacer en {site}.</p>
<p><b>Difundir contenido ilegal.</b> Material que explote a menores, apología del terrorismo, incitación a delitos, contenido que vulnere derechos de autor de forma evidente.</p>
<p><b>Suplantar.</b> Hacerse pasar por otra persona, medio o institución.</p>
<p><b>Hacer trampas.</b> Cuentas múltiples para votar, publicidad encubierta, enlaces engañosos y cualquier forma de manipular la portada.</p>

<h2>Cómo se modera</h2>
<p>Cualquiera puede <b>reportar</b> un comentario desde el propio comentario, sin necesidad de tener cuenta. Los reportes los revisan personas: moderadores voluntarios de la comunidad y el equipo de {site}. Además, una IA que vive en nuestros servidores (ningún texto sale de ellos) hace un primer filtro cuando está activada.</p>
<p>Cuando un comentario incumple estas normas, no desaparece sin más: su texto se sustituye por <i>«Comentario eliminado por incumplir las normas de uso»</i>, de modo que la conversación se entiende y queda constancia de que ahí hubo algo que no debía estar. Si un hilo entero es ilegal, se retira.</p>
<p>La moderación se equivoca a veces. Si crees que se ha retirado algo tuyo sin motivo, escribe a <a href="mailto:info@deltha.eu">info@deltha.eu</a> y lo revisamos.</p>

<h2>Consecuencias</h2>
<p>La primera vez, se retira el comentario. Si se repite, la cuenta puede quedar suspendida. Los casos que puedan ser delito se conservan y se ponen a disposición de las autoridades si lo requieren, como obliga la ley.</p>

<h2>Y lo demás</h2>
<p>Todo lo que se publica aquí es efímero: los hilos se archivan a las {ttl} horas. Cuida lo que escribes durante esas horas como si fuera a durar siempre, porque para quien lo lee, lo es.</p>');
