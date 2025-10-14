-- Modifier la colonne owner_type pour inclure les monstres
ALTER TABLE place_objects 
MODIFY COLUMN owner_type ENUM('none', 'player', 'npc', 'monster') NOT NULL DEFAULT 'none' COMMENT 'Type de propriétaire de l''objet';
