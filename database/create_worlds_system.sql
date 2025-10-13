-- =====================================================
-- SCRIPT DE CRÉATION DU SYSTÈME DE MONDES
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script crée les tables nécessaires pour le système
-- de gestion des mondes par les MJ
--
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. CRÉATION DE LA TABLE WORLDS
-- =====================================================

CREATE TABLE IF NOT EXISTS worlds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    map_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_created_by (created_by),
    INDEX idx_name (name),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. MODIFICATION DE LA TABLE COUNTRIES
-- =====================================================

-- Ajouter la colonne world_id à la table countries
ALTER TABLE countries ADD COLUMN world_id INT AFTER id;

-- Ajouter l'index et la contrainte de clé étrangère
ALTER TABLE countries ADD INDEX idx_world_id (world_id);
ALTER TABLE countries ADD CONSTRAINT fk_countries_world_id 
    FOREIGN KEY (world_id) REFERENCES worlds(id) ON DELETE CASCADE;

-- =====================================================
-- 3. VÉRIFICATION DES MODIFICATIONS
-- =====================================================

-- Vérifier la structure de la table worlds
DESCRIBE worlds;

-- Vérifier la structure de la table countries
DESCRIBE countries;

-- =====================================================
-- NOTES IMPORTANTES
-- =====================================================
-- 
-- Structure hiérarchique des mondes :
-- worlds (1) ←→ (N) countries
-- countries (1) ←→ (N) regions  
-- regions (1) ←→ (N) places
--
-- Les mondes sont créés par les MJ (created_by)
-- Chaque pays appartient à un monde
-- Les lieux restent liés aux campagnes via campaign_id
--
-- =====================================================
