-- Mise à jour de la table place_objects pour supporter les nouveaux types et champs
ALTER TABLE place_objects 
MODIFY COLUMN object_type ENUM('poison', 'coins', 'letter', 'weapon', 'armor', 'magical_item', 'other') NOT NULL DEFAULT 'other';

-- Ajouter les nouveaux champs
ALTER TABLE place_objects 
ADD COLUMN item_id VARCHAR(50) NULL COMMENT 'ID de l\'objet sélectionné (poison, objet magique, arme, armure)',
ADD COLUMN item_name VARCHAR(255) NULL COMMENT 'Nom de l\'objet sélectionné',
ADD COLUMN item_description TEXT NULL COMMENT 'Description de l\'objet sélectionné',
ADD COLUMN letter_content TEXT NULL COMMENT 'Contenu de la lettre',
ADD COLUMN is_sealed BOOLEAN DEFAULT FALSE COMMENT 'Si la lettre est cachetée ou pas';

-- Ajouter des index pour les nouveaux champs
ALTER TABLE place_objects 
ADD INDEX idx_item_id (item_id),
ADD INDEX idx_item_name (item_name);
