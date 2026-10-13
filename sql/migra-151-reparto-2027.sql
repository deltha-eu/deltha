-- migra-151 (13/10/2026): el reparto del 50 % de la propiedad entre quienes tengan aura maxima se aplaza del 31/12/2026 al 31/12/2027:
-- llegar a aura 50 antes de fin de 2026 es imposible y asi entra mas gente. Las paginas (que-es en los 10 idiomas) pintan la fecha
-- con {auradl}, que lee esta config y la escribe en el idioma de cada cual (fecha_larga), asi que no hay que tocar ningun texto.
UPDATE config SET cvalue = '2027-12-31' WHERE ckey = 'aura_deadline';
