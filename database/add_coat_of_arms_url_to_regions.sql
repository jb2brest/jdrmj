-- database/add_coat_of_arms_url_to_regions.sql
-- Script pour ajouter la colonne coat_of_arms_url à la table regions

-- Ajouter la colonne coat_of_arms_url si elle n'existe pas
ALTER TABLE regions 
ADD COLUMN IF NOT EXISTS coat_of_arms_url VARCHAR(255) AFTER map_url;

-- Vérification
DESCRIBE regions;

