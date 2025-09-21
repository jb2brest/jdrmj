-- Migration pour permettre à un lieu d'être associé à plusieurs campagnes
-- Date: 2024

-- 1. Créer la table de liaison place_campaigns
CREATE TABLE IF NOT EXISTS place_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    campaign_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_place_campaign (place_id, campaign_id)
);

-- 2. Migrer les données existantes de places.campaign_id vers place_campaigns
INSERT INTO place_campaigns (place_id, campaign_id)
SELECT id, campaign_id 
FROM places 
WHERE campaign_id IS NOT NULL;

-- 3. Supprimer la colonne campaign_id de la table places
-- (Cette étape sera faite manuellement après vérification)
-- ALTER TABLE places DROP COLUMN campaign_id;

-- 4. Créer des index pour optimiser les performances
CREATE INDEX idx_place_campaigns_place_id ON place_campaigns(place_id);
CREATE INDEX idx_place_campaigns_campaign_id ON place_campaigns(campaign_id);

-- 5. Vérification des données migrées
SELECT 
    'Lieux avec associations' as description,
    COUNT(*) as count
FROM place_campaigns
UNION ALL
SELECT 
    'Lieux sans campagne' as description,
    COUNT(*) as count
FROM places 
WHERE id NOT IN (SELECT DISTINCT place_id FROM place_campaigns);
