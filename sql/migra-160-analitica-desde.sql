-- migra-160 (17/10/2026): la analitica solo enseña datos desde la apertura al publico (06/10/2026). Lo anterior era la beta cerrada:
-- sigue en ana_daily y hits, pero no entra en /analitica ni en la analitica completa (rango, atajos, «este año»). '' = todo.
INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('analytics_since', '2026-10-06', 'Primer dia que cuenta en la analitica (AAAA-MM-DD; apertura al publico). Lo anterior no se enseña. Vacio = todo');
