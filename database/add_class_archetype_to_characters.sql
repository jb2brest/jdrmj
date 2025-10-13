-- Ajouter la colonne class_archetype_id à la table characters
-- Date: 2025-10-13
-- Description: Permet de stocker l'archetype choisi lors de la création

-- Ajouter la colonne class_archetype_id
ALTER TABLE characters 
ADD COLUMN class_archetype_id INT NULL AFTER class_id,
ADD FOREIGN KEY (class_archetype_id) REFERENCES class_archetypes(id) ON DELETE SET NULL;

-- Ajouter un index pour améliorer les performances
CREATE INDEX idx_characters_class_archetype ON characters(class_archetype_id);

-- Vérifier la structure mise à jour
DESCRIBE characters;
