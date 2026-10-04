-- migra-115 (04/10/2026): los agentes trabajan el triple (pedido por Angel). Se multiplican por 3 los dos ritmos de cada agente:
-- comentarios al dia (agents.per_day, migra-089/090) y envios al radar al dia (agents.submits_per_day, migra-091). Vale para todos los
-- agentes dados de alta, activos o no; los que tuvieran 0 envios siguen en 0. El cupo del dia y next_submit se recalculan solos
-- (next_submit se adelanta a ahora para que el nuevo ritmo empiece en el siguiente minuto del cron).
SET NAMES utf8mb4;
-- Topes de la ficha: 200 comentarios/dia y 48 envios/dia (el cron deja al menos 30 min entre envios, asi que mas de 48 no se notaria).
UPDATE agents SET per_day = LEAST(per_day * 3, 200), submits_per_day = LEAST(submits_per_day * 3, 48), next_submit = NOW();
