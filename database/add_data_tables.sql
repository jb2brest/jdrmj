-- Ajout des tables pour stocker les données des poisons et objets magiques
-- Ce script doit être exécuté après la création de la base de données principale

USE dnd_characters;

-- Table pour stocker les poisons
CREATE TABLE IF NOT EXISTS poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);

-- Table pour stocker les objets magiques
CREATE TABLE IF NOT EXISTS magical_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);

-- Table pour stocker les monstres (si pas déjà existante)
CREATE TABLE IF NOT EXISTS dnd_monsters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    size VARCHAR(50),
    challenge_rating VARCHAR(20),
    hit_points INT,
    armor_class INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_name (name),
    INDEX idx_type (type),
    FULLTEXT idx_search (name, type)
);














