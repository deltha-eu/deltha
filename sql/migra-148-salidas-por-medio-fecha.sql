-- migra-148 (13/10/2026): ANULADA. Pretendia pasar la semilla por dominio de clicks_source al 12/10/2026, pero fallo por collation
-- (ERROR 1267: clicks_source salio con utf8mb4_0900_ai_ci y stories es utf8mb4_unicode_ci). Todo lo hace la migra-149, de forma
-- idempotente. Se deja vacia para que el hook pueda darla por aplicada sin tocar nada.
SELECT 1;
