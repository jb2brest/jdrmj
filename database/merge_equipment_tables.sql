-- Fusion des tables place_objects et character_equipment
-- Étendre place_objects pour inclure tous les champs de character_equipment

-- Ajouter les champs manquants à place_objects
ALTER TABLE place_objects
ADD COLUMN magical_item_id VARCHAR(50) NULL COMMENT 'ID de l\'objet magique (référence vers magical_items.csv_id)',
ADD COLUMN item_source VARCHAR(100) NULL COMMENT 'Source de l\'objet',
ADD COLUMN quantity INT NOT NULL DEFAULT 1 COMMENT 'Quantité de l\'objet',
ADD COLUMN equipped_slot ENUM('main_hand','off_hand','armor','shield') NULL COMMENT 'Slot d\'équipement',
ADD COLUMN notes TEXT NULL COMMENT 'Notes sur l\'objet',
ADD COLUMN obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'obtention',
ADD COLUMN obtained_from VARCHAR(100) DEFAULT 'Attribution MJ' COMMENT 'Méthode d\'obtention';

-- Migrer les données de character_equipment vers place_objects
INSERT INTO place_objects (
    place_id, display_name, object_type, type_precis, description,
    is_identified, is_visible, is_equipped, position_x, position_y, is_on_map,
    owner_type, owner_id, poison_id, weapon_id, armor_id,
    gold_coins, silver_coins, copper_coins, letter_content, is_sealed,
    magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from,
    created_at, updated_at
)
SELECT 
    NULL as place_id, -- Les objets d'équipement n'ont pas de place_id
    ce.item_name as display_name,
    CASE 
        WHEN ce.item_type = 'weapon' THEN 'weapon'
        WHEN ce.item_type = 'armor' THEN 'armor'
        WHEN ce.item_type = 'coins' THEN 'bourse'
        WHEN ce.magical_item_id IS NOT NULL THEN 'weapon' -- Les objets magiques sont traités comme des armes par défaut
        ELSE 'weapon' -- Par défaut
    END as object_type,
    ce.item_name as type_precis, -- Utiliser le nom de l'item comme type précis
    ce.item_description as description,
    TRUE as is_identified, -- Les objets d'équipement sont identifiés par défaut
    FALSE as is_visible, -- Les objets d'équipement ne sont pas visibles sur la carte
    ce.is_equipped as is_equipped,
    0 as position_x,
    0 as position_y,
    FALSE as is_on_map,
    'player' as owner_type,
    ce.character_id as owner_id,
    NULL as poison_id,
    CASE WHEN ce.item_type = 'weapon' THEN NULL ELSE NULL END as weapon_id, -- À déterminer selon le type
    CASE WHEN ce.item_type = 'armor' THEN NULL ELSE NULL END as armor_id, -- À déterminer selon le type
    0 as gold_coins,
    0 as silver_coins,
    0 as copper_coins,
    NULL as letter_content,
    FALSE as is_sealed,
    ce.magical_item_id,
    ce.item_source,
    ce.quantity,
    ce.equipped_slot,
    ce.notes,
    ce.obtained_at,
    ce.obtained_from,
    ce.obtained_at as created_at,
    ce.obtained_at as updated_at
FROM character_equipment ce;

-- Ajouter des index pour les nouveaux champs
CREATE INDEX idx_magical_item_id ON place_objects (magical_item_id);
CREATE INDEX idx_equipped_slot ON place_objects (equipped_slot);
CREATE INDEX idx_obtained_at ON place_objects (obtained_at);

-- Vérifier la migration
SELECT 
    'Migration terminée' as status,
    COUNT(*) as total_objects,
    SUM(CASE WHEN owner_type = 'place' THEN 1 ELSE 0 END) as place_objects,
    SUM(CASE WHEN owner_type = 'player' THEN 1 ELSE 0 END) as player_objects,
    SUM(CASE WHEN owner_type = 'npc' THEN 1 ELSE 0 END) as npc_objects,
    SUM(CASE WHEN owner_type = 'monster' THEN 1 ELSE 0 END) as monster_objects
FROM place_objects;
