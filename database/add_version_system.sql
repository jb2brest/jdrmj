-- =====================================================
-- SCRIPT D'AJOUT DU SYSTÈME DE VERSIONING
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script ajoute un système de versioning pour la base de données
-- et permet de suivre les versions déployées
--
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. CRÉATION DE LA TABLE DES VERSIONS
-- =====================================================

CREATE TABLE IF NOT EXISTS system_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_type ENUM('database', 'application') NOT NULL,
    version_number VARCHAR(20) NOT NULL,
    build_id VARCHAR(50),
    git_commit VARCHAR(40),
    deploy_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deploy_user VARCHAR(100),
    environment VARCHAR(20) NOT NULL,
    release_notes TEXT,
    is_current BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_version_type (version_type),
    INDEX idx_version_number (version_number),
    INDEX idx_environment (environment),
    INDEX idx_is_current (is_current),
    INDEX idx_deploy_date (deploy_date)
);

-- =====================================================
-- 2. CRÉATION DE LA TABLE DES MIGRATIONS
-- =====================================================

CREATE TABLE IF NOT EXISTS database_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    version_from VARCHAR(20),
    version_to VARCHAR(20) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_by VARCHAR(100),
    execution_time_ms INT,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    
    INDEX idx_migration_name (migration_name),
    INDEX idx_version_to (version_to),
    INDEX idx_executed_at (executed_at),
    INDEX idx_success (success)
);

-- =====================================================
-- 3. INSERTION DE LA VERSION INITIALE
-- =====================================================

-- Version de la base de données
INSERT INTO system_versions (
    version_type, 
    version_number, 
    build_id, 
    deploy_date, 
    deploy_user, 
    environment, 
    release_notes
) VALUES (
    'database',
    '1.0.0',
    '20250917-154611',
    '2025-09-17 15:46:11',
    'system',
    'production',
    'Initialisation de la base de données avec toutes les tables D&D 5e'
);

-- Version de l'application
INSERT INTO system_versions (
    version_type, 
    version_number, 
    build_id, 
    git_commit, 
    deploy_date, 
    deploy_user, 
    environment, 
    release_notes
) VALUES (
    'application',
    '1.0.0',
    '20250917-154611',
    'unknown',
    '2025-09-17 15:46:11',
    'system',
    'production',
    'Ajout du rôle admin - jean.m.bernard@gmail.com promu administrateur'
);

-- =====================================================
-- 4. INSERTION DES MIGRATIONS EXÉCUTÉES
-- =====================================================

INSERT INTO database_migrations (
    migration_name,
    version_from,
    version_to,
    executed_at,
    executed_by,
    success
) VALUES 
('001_initial_schema', NULL, '1.0.0', '2025-09-17 15:46:11', 'system', TRUE),
('002_add_admin_role', '1.0.0', '1.0.0', '2025-09-17 15:46:11', 'system', TRUE);

-- =====================================================
-- 5. VÉRIFICATION
-- =====================================================

-- Afficher les versions actuelles
SELECT 'Versions actuelles du système:' as Status;
SELECT 
    version_type as 'Type',
    version_number as 'Version',
    build_id as 'Build ID',
    deploy_date as 'Date de déploiement',
    environment as 'Environnement',
    release_notes as 'Notes'
FROM system_versions 
WHERE is_current = TRUE
ORDER BY version_type, deploy_date DESC;

-- Afficher l'historique des migrations
SELECT 'Historique des migrations:' as Status;
SELECT 
    migration_name as 'Migration',
    version_from as 'De',
    version_to as 'Vers',
    executed_at as 'Exécutée le',
    success as 'Succès'
FROM database_migrations 
ORDER BY executed_at DESC;

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

SELECT 'Système de versioning ajouté avec succès!' as Message;
