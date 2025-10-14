-- Ajouter les colonnes pour l'attribution des objets
ALTER TABLE place_objects
ADD COLUMN owner_type ENUM('none', 'player', 'npc') NOT NULL DEFAULT 'none' COMMENT 'Type de propriétaire de l''objet',
ADD COLUMN owner_id INT NULL COMMENT 'ID du propriétaire (player_id ou npc_id selon owner_type)';

-- Ajouter des index pour améliorer les performances
CREATE INDEX idx_place_objects_owner ON place_objects(owner_type, owner_id);
