-- Mise à jour de la table character_equipment existante pour supporter les armes et armures

-- Ajouter les colonnes nécessaires si elles n'existent pas
ALTER TABLE character_equipment 
ADD COLUMN IF NOT EXISTS equipped_slot ENUM('main_hand', 'off_hand', 'armor', 'shield') NULL AFTER equipped;

-- Renommer la colonne equipped en is_equipped pour la cohérence
ALTER TABLE character_equipment 
CHANGE COLUMN equipped is_equipped BOOLEAN DEFAULT FALSE;

-- Ajouter un index sur is_equipped si il n'existe pas
CREATE INDEX IF NOT EXISTS idx_character_equipment_equipped ON character_equipment(is_equipped);
CREATE INDEX IF NOT EXISTS idx_character_equipment_slot ON character_equipment(equipped_slot);
