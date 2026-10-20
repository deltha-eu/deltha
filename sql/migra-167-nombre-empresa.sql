-- migra-167 (20/10/2026): las paginas legales de salida (migra-009, 050, 063, 139) decian «Jot Down Group Media Company»; el nombre
--  correcto es «Jot Down Media Company S.L.» (el del aviso legal). Se corrige en las paginas que aun lo tengan (las editadas en admin
--  con el nombre bueno no cambian). Idempotente.
SET NAMES utf8mb4;
UPDATE pages SET body = REPLACE(body, 'Jot Down Group Media Company', 'Jot Down Media Company S.L.') WHERE body LIKE '%Jot Down Group Media Company%';
