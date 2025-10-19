-- Ajouter la colonne is_npc à la table characters
ALTER TABLE characters ADD COLUMN is_npc TINYINT(1) DEFAULT 0;

-- Ajouter un index pour améliorer les performances
CREATE INDEX idx_characters_is_npc ON characters(is_npc);
