-- migra-190 (2026-11-09): el resumen final de /algoritmo dice «media hora de actividad» en vez de «de espera», con los votos que
-- equivalen a una (marcador {actk}, lo rellena page_body). Complemento de la migra-189. Idempotente.
SET NAMES utf8mb4;
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) por media hora de espera ·', '{decays} (Substacks) por media hora de actividad (= {actk} votos en todo el sitio) ·') WHERE slug = 'algoritmo';
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) per half hour of waiting ·', '{decays} (Substacks) per half hour of activity (= {actk} votes across the site) ·') WHERE slug = 'algoritmo.en';
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) per mitja hora d''espera ·', '{decays} (Substacks) per mitja hora d''activitat (= {actk} vots a tot el lloc) ·') WHERE slug = 'algoritmo.ca';
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) por media hora de espera ·', '{decays} (Substacks) por media hora de actividade (= {actk} votos en todo o sitio) ·') WHERE slug = 'algoritmo.gl';
UPDATE pages SET body = REPLACE(body, '{decays} (Substackak) itxaroteko ordu erdiko ·', '{decays} (Substackak) jarduerako ordu erdiko (= {actk} boto gune osoan) ·') WHERE slug = 'algoritmo.eu';
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) par demi-heure d''attente ·', '{decays} (Substacks) par demi-heure d''activité (= {actk} votes sur tout le site) ·') WHERE slug = 'algoritmo.fr';
UPDATE pages SET body = REPLACE(body, '{decays} (Substacks) pro halbe Stunde Wartezeit ·', '{decays} (Substacks) pro halbe Stunde Aktivität (= {actk} Stimmen auf der ganzen Seite) ·') WHERE slug = 'algoritmo.de';
UPDATE pages SET body = REPLACE(body, '{decays} (Substack) per mezz''ora di attesa ·', '{decays} (Substack) per mezz''ora di attività (= {actk} voti in tutto il sito) ·') WHERE slug = 'algoritmo.it';
UPDATE pages SET body = REPLACE(body, '{decays} (Newsletters) por meia hora de espera ·', '{decays} (Newsletters) por meia hora de atividade (= {actk} votos em todo o site) ·') WHERE slug = 'algoritmo.pt';
UPDATE pages SET body = REPLACE(body, 'beklenen her yarım saat için azalma {decay} (Medya) ve {decays} (Substacks) ·', 'her yarım saatlik etkinlik için (= sitenin tamamında {actk} oy) azalma {decay} (Medya) ve {decays} (Substacks) ·') WHERE slug = 'algoritmo.tr';
