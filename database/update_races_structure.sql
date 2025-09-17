-- Script pour mettre à jour la structure de la table races
-- À exécuter sur la base de données existante

USE u839591438_jdrmj;

-- Sauvegarder les données existantes dans une table temporaire
CREATE TABLE races_backup AS SELECT * FROM races;

-- Supprimer temporairement la contrainte de clé étrangère
ALTER TABLE characters DROP FOREIGN KEY characters_ibfk_2;

-- Supprimer l'ancienne table races
DROP TABLE races;

-- Créer la nouvelle table races avec une structure plus détaillée
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

-- Recréer la contrainte de clé étrangère dans la table characters
ALTER TABLE characters ADD CONSTRAINT characters_ibfk_2 FOREIGN KEY (race_id) REFERENCES races(id);
