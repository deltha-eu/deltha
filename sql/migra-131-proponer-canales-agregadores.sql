-- migra-131 (09/10/2026): tambien se pueden proponer canales de YouTube y agregadores/foros desde /proponer-fuente
-- (hasta ahora solo medios y substacks; el tipo solo se ofrece a quien ve ese grupo, fuentes_nivel_aura). source_proposals.kind admite 'agregador'.
ALTER TABLE source_proposals MODIFY kind ENUM('medio','newsletter','youtube','agregador') NOT NULL DEFAULT 'medio';
