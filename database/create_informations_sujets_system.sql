-- =====================================================
-- Système d'Informations et Sujets
-- =====================================================
-- 
-- Structure :
-- informations (1) ←→ (N) sujet_informations ←→ (N) sujets
-- informations (1) ←→ (N) information_access (joueurs ou groupes avec niveaux)
-- sujets (1) ←→ (N) sujet_access (joueurs ou groupes avec niveaux)
--
-- Une information contient un titre, une description et un niveau de confidentialité
-- Un sujet contient un titre, une description et une liste d'informations
-- Les accès peuvent être par joueur ou par groupe (avec indication des niveaux)
--
-- =====================================================

-- Table des informations
CREATE TABLE IF NOT EXISTS informations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    niveau_confidentialite ENUM('archi_connu', 'connu', 'connu_du_milieu', 'confidentiel', 'secret') NOT NULL DEFAULT 'connu',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_niveau_confidentialite (niveau_confidentialite),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sujets
CREATE TABLE IF NOT EXISTS sujets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison sujet-information (many-to-many)
CREATE TABLE IF NOT EXISTS sujet_informations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sujet_id INT NOT NULL,
    information_id INT NOT NULL,
    ordre INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sujet_id) REFERENCES sujets(id) ON DELETE CASCADE,
    FOREIGN KEY (information_id) REFERENCES informations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sujet_information (sujet_id, information_id),
    INDEX idx_sujet_id (sujet_id),
    INDEX idx_information_id (information_id),
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'accès aux informations par joueur ou groupe
CREATE TABLE IF NOT EXISTS information_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    information_id INT NOT NULL,
    access_type ENUM('player', 'group') NOT NULL,
    player_id INT NULL,
    groupe_id INT NULL,
    niveau_min INT NULL COMMENT 'Niveau hiérarchique minimum pour les groupes (1 = dirigeant)',
    niveau_max INT NULL COMMENT 'Niveau hiérarchique maximum pour les groupes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (information_id) REFERENCES informations(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (groupe_id) REFERENCES groupes(id) ON DELETE CASCADE,
    CHECK (
        (access_type = 'player' AND player_id IS NOT NULL AND groupe_id IS NULL) OR
        (access_type = 'group' AND groupe_id IS NOT NULL AND player_id IS NULL)
    ),
    UNIQUE KEY unique_player_access (information_id, access_type, player_id),
    UNIQUE KEY unique_group_access (information_id, access_type, groupe_id, niveau_min, niveau_max),
    INDEX idx_information_id (information_id),
    INDEX idx_player_id (player_id),
    INDEX idx_groupe_id (groupe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'accès aux sujets par joueur ou groupe
CREATE TABLE IF NOT EXISTS sujet_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sujet_id INT NOT NULL,
    access_type ENUM('player', 'group') NOT NULL,
    player_id INT NULL,
    groupe_id INT NULL,
    niveau_min INT NULL COMMENT 'Niveau hiérarchique minimum pour les groupes (1 = dirigeant)',
    niveau_max INT NULL COMMENT 'Niveau hiérarchique maximum pour les groupes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sujet_id) REFERENCES sujets(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (groupe_id) REFERENCES groupes(id) ON DELETE CASCADE,
    CHECK (
        (access_type = 'player' AND player_id IS NOT NULL AND groupe_id IS NULL) OR
        (access_type = 'group' AND groupe_id IS NOT NULL AND player_id IS NULL)
    ),
    UNIQUE KEY unique_player_access (sujet_id, access_type, player_id),
    UNIQUE KEY unique_group_access (sujet_id, access_type, groupe_id, niveau_min, niveau_max),
    INDEX idx_sujet_id (sujet_id),
    INDEX idx_player_id (player_id),
    INDEX idx_groupe_id (groupe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


