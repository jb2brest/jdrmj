-- =====================================================
-- Système d'Informations
-- =====================================================
-- 
-- Une information contient :
-- - un titre
-- - une description
-- - un niveau de confidentialité
-- - un statut (vraie, fausse, à vérifier)
-- - une image (uploadable)
--
-- Une information peut être connue d'un joueur, PNJ, monstre ou groupe
-- Les informations sont associées aux thématiques dans un ordre spécifique
--
-- =====================================================

-- Table des informations
CREATE TABLE IF NOT EXISTS informations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    niveau_confidentialite ENUM('archi_connu', 'connu', 'connu_du_milieu', 'confidentiel', 'secret') NOT NULL DEFAULT 'connu',
    statut ENUM('vraie', 'fausse', 'a_verifier') NOT NULL DEFAULT 'a_verifier',
    image_path VARCHAR(500) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_niveau_confidentialite (niveau_confidentialite),
    INDEX idx_statut (statut),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison thématique-information (avec ordre)
CREATE TABLE IF NOT EXISTS thematique_informations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thematique_id INT NOT NULL,
    information_id INT NOT NULL,
    ordre INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thematique_id) REFERENCES thematiques(id) ON DELETE CASCADE,
    FOREIGN KEY (information_id) REFERENCES informations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_thematique_information (thematique_id, information_id),
    INDEX idx_thematique_id (thematique_id),
    INDEX idx_information_id (information_id),
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'accès aux informations par joueur, PNJ, monstre ou groupe
CREATE TABLE IF NOT EXISTS information_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    information_id INT NOT NULL,
    access_type ENUM('player', 'npc', 'monster', 'group') NOT NULL,
    player_id INT NULL,
    npc_id INT NULL COMMENT 'Référence place_npcs.id pour PNJ et monstres',
    groupe_id INT NULL,
    niveau_min INT NULL COMMENT 'Niveau hiérarchique minimum pour les groupes (1 = dirigeant)',
    niveau_max INT NULL COMMENT 'Niveau hiérarchique maximum pour les groupes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (information_id) REFERENCES informations(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (groupe_id) REFERENCES groupes(id) ON DELETE CASCADE,
    FOREIGN KEY (npc_id) REFERENCES place_npcs(id) ON DELETE CASCADE,
    CHECK (
        (access_type = 'player' AND player_id IS NOT NULL AND npc_id IS NULL AND groupe_id IS NULL) OR
        (access_type = 'npc' AND npc_id IS NOT NULL AND player_id IS NULL AND groupe_id IS NULL) OR
        (access_type = 'monster' AND npc_id IS NOT NULL AND player_id IS NULL AND groupe_id IS NULL) OR
        (access_type = 'group' AND groupe_id IS NOT NULL AND player_id IS NULL AND npc_id IS NULL)
    ),
    UNIQUE KEY unique_player_access (information_id, access_type, player_id),
    UNIQUE KEY unique_npc_access (information_id, access_type, npc_id),
    UNIQUE KEY unique_group_access (information_id, access_type, groupe_id, niveau_min, niveau_max),
    INDEX idx_information_id (information_id),
    INDEX idx_player_id (player_id),
    INDEX idx_npc_id (npc_id),
    INDEX idx_groupe_id (groupe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


