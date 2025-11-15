-- Ajouter le champ max_hierarchy_levels à la table groupes
-- Par défaut, les groupes existants auront 5 niveaux (comportement actuel)
ALTER TABLE groupes 
ADD COLUMN max_hierarchy_levels INT NOT NULL DEFAULT 5 
AFTER headquarters_place_id;

-- Mettre à jour les groupes existants pour qu'ils aient 5 niveaux (déjà fait par DEFAULT, mais on s'assure)
UPDATE groupes SET max_hierarchy_levels = 5 WHERE max_hierarchy_levels IS NULL OR max_hierarchy_levels = 0;

