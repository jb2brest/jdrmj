-- Migration pour fusionner toutes les tables d'archetypes en une seule table unifiée
-- Date: 2025-10-13
-- Description: Fusionne les 12 tables d'archetypes en une table class_archetypes avec class_id

-- 1. Créer la nouvelle table unifiée
CREATE TABLE class_archetypes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    level_1_feature TEXT,
    level_2_feature TEXT,
    level_3_feature TEXT,
    level_6_feature TEXT,
    level_7_feature TEXT,
    level_8_feature TEXT,
    level_9_feature TEXT,
    level_10_feature TEXT,
    level_11_feature TEXT,
    level_13_feature TEXT,
    level_14_feature TEXT,
    level_15_feature TEXT,
    level_17_feature TEXT,
    level_18_feature TEXT,
    level_20_feature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_class_id (class_id),
    INDEX idx_name (name)
);

-- 2. Insérer les données des barbarian_paths (class_id = 1)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_6_feature, level_10_feature, level_14_feature, created_at)
SELECT 1, name, description, level_3_feature, level_6_feature, level_10_feature, level_14_feature, created_at
FROM barbarian_paths;

-- 3. Insérer les données des bard_colleges (class_id = 2)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_6_feature, level_14_feature, created_at, updated_at)
SELECT 2, name, description, level_3_feature, level_6_feature, level_14_feature, created_at, updated_at
FROM bard_colleges;

-- 4. Insérer les données des cleric_domains (class_id = 3)
INSERT INTO class_archetypes (class_id, name, description, level_1_feature, level_2_feature, level_6_feature, level_8_feature, level_17_feature, created_at, updated_at)
SELECT 3, name, description, level_1_feature, level_2_feature, level_6_feature, level_8_feature, level_17_feature, created_at, updated_at
FROM cleric_domains;

-- 5. Insérer les données des druid_circles (class_id = 4)
INSERT INTO class_archetypes (class_id, name, description, level_2_feature, level_6_feature, level_10_feature, level_14_feature, created_at, updated_at)
SELECT 4, name, description, level_2_feature, level_6_feature, level_10_feature, level_14_feature, created_at, updated_at
FROM druid_circles;

-- 6. Insérer les données des fighter_archetypes (class_id = 6)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_7_feature, level_10_feature, level_15_feature, level_18_feature, created_at, updated_at)
SELECT 6, name, description, level_3_feature, level_7_feature, level_10_feature, level_15_feature, level_18_feature, created_at, updated_at
FROM fighter_archetypes;

-- 7. Insérer les données des monk_traditions (class_id = 8)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_6_feature, level_11_feature, level_17_feature, created_at, updated_at)
SELECT 8, name, description, level_3_feature, level_6_feature, level_11_feature, level_17_feature, created_at, updated_at
FROM monk_traditions;

-- 8. Insérer les données des paladin_oaths (class_id = 10)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_7_feature, level_15_feature, level_20_feature, created_at)
SELECT 10, name, description, level_3_feature, level_7_feature, level_15_feature, level_20_feature, created_at
FROM paladin_oaths;

-- 9. Insérer les données des ranger_archetypes (class_id = 11)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_7_feature, level_11_feature, level_15_feature, created_at)
SELECT 11, name, description, level_3_feature, level_7_feature, level_11_feature, level_15_feature, created_at
FROM ranger_archetypes;

-- 10. Insérer les données des rogue_archetypes (class_id = 12)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_9_feature, level_13_feature, level_17_feature, created_at)
SELECT 12, name, description, level_3_feature, level_9_feature, level_13_feature, level_17_feature, created_at
FROM rogue_archetypes;

-- 11. Insérer les données des sorcerer_origins (class_id = 5)
INSERT INTO class_archetypes (class_id, name, description, level_1_feature, level_6_feature, level_14_feature, level_18_feature, created_at, updated_at)
SELECT 5, name, description, level_1_feature, level_6_feature, level_14_feature, level_18_feature, created_at, updated_at
FROM sorcerer_origins;

-- 12. Insérer les données des warlock_pacts (class_id = 9)
INSERT INTO class_archetypes (class_id, name, description, level_3_feature, level_7_feature, level_15_feature, level_20_feature, created_at, updated_at)
SELECT 9, name, description, level_3_feature, level_7_feature, level_15_feature, level_20_feature, created_at, updated_at
FROM warlock_pacts;

-- 13. Insérer les données des wizard_traditions (class_id = 7)
INSERT INTO class_archetypes (class_id, name, description, level_2_feature, level_6_feature, level_10_feature, level_14_feature, created_at, updated_at)
SELECT 7, name, description, level_2_feature, level_6_feature, level_10_feature, level_14_feature, created_at, updated_at
FROM wizard_traditions;

-- 14. Vérification des données migrées
SELECT 
    c.name as class_name,
    COUNT(ca.id) as archetype_count
FROM classes c
LEFT JOIN class_archetypes ca ON c.id = ca.class_id
GROUP BY c.id, c.name
ORDER BY c.name;

-- 15. Afficher le total des archetypes migrés
SELECT COUNT(*) as total_archetypes_migrated FROM class_archetypes;
