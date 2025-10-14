-- database/update_regions_schema.sql
-- Script pour mettre à jour la table regions avec les colonnes nécessaires

USE u839591438_jdrmj; -- Assurez-vous que c'est la bonne base de données

-- Vérifier si la table regions existe, sinon la créer
CREATE TABLE IF NOT EXISTS regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),
    coat_of_arms_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_region_per_country (country_id, name)
);

-- Vérification
DESCRIBE regions;
