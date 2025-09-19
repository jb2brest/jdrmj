-- database/update_countries_schema.sql
-- Script pour ajouter les colonnes carte et blason à la table countries

USE u839591438_jdrmj; -- Assurez-vous que c'est la bonne base de données

-- Ajouter les colonnes pour la carte et le blason
ALTER TABLE countries 
ADD COLUMN map_url VARCHAR(255) AFTER description,
ADD COLUMN coat_of_arms_url VARCHAR(255) AFTER map_url;

-- Vérification
DESCRIBE countries;
