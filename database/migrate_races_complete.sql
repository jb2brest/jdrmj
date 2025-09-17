-- Script complet pour migrer le système de races
-- À exécuter sur la base de données existante

USE u839591438_jdrmj;

-- 1. Sauvegarder les données existantes
CREATE TABLE races_backup AS SELECT * FROM races;
CREATE TABLE characters_backup AS SELECT * FROM characters;

-- 2. Mettre à jour les personnages pour utiliser des race_id temporaires
-- Créer une race temporaire pour les personnages existants
INSERT INTO races (id, name, description, ability_score_bonus, traits) 
VALUES (999, 'Race Temporaire', 'Race temporaire pour migration', '', '');

-- Mettre à jour tous les personnages pour utiliser cette race temporaire
UPDATE characters SET race_id = 999;

-- 3. Supprimer la contrainte de clé étrangère
ALTER TABLE characters DROP FOREIGN KEY characters_ibfk_2;

-- 4. Supprimer l'ancienne table races
DROP TABLE races;

-- 5. Créer la nouvelle table races avec une structure plus détaillée
CREATE TABLE races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- Modificateurs de caractéristiques
    strength_bonus INT DEFAULT 0,
    dexterity_bonus INT DEFAULT 0,
    constitution_bonus INT DEFAULT 0,
    intelligence_bonus INT DEFAULT 0,
    wisdom_bonus INT DEFAULT 0,
    charisma_bonus INT DEFAULT 0,
    
    -- Informations physiques
    size VARCHAR(10) DEFAULT 'M', -- P (Petit), M (Moyen), G (Grand)
    speed INT DEFAULT 30,
    vision VARCHAR(255), -- Vision dans le noir, vision normale, etc.
    
    -- Langues
    languages TEXT, -- Langues parlées par défaut
    
    -- Traits et capacités
    traits TEXT, -- Traits raciaux détaillés
    
    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Créer une race par défaut pour les personnages existants
INSERT INTO races (id, name, description, strength_bonus, dexterity_bonus, constitution_bonus, intelligence_bonus, wisdom_bonus, charisma_bonus, size, speed, vision, languages, traits) 
VALUES (1, 'Humain (Migration)', 'Race par défaut pour les personnages existants', 1, 1, 1, 1, 1, 1, 'M', 30, 'Vision normale', 'commun, une langue de votre choix', 'Versatilité humaine');

-- 7. Mettre à jour les personnages pour utiliser la race par défaut
UPDATE characters SET race_id = 1;

-- 8. Recréer la contrainte de clé étrangère
ALTER TABLE characters ADD CONSTRAINT characters_ibfk_2 FOREIGN KEY (race_id) REFERENCES races(id);

-- 9. Supprimer la race temporaire
DELETE FROM races WHERE id = 999;
