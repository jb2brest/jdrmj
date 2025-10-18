-- Création de la table pour gérer les accès entre lieux
-- Un accès permet de passer d'un lieu à un autre avec des propriétés spécifiques

CREATE TABLE IF NOT EXISTS accesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Lieux concernés par l'accès
    from_place_id INT NOT NULL COMMENT 'Lieu de départ',
    to_place_id INT NOT NULL COMMENT 'Lieu de destination',
    
    -- Propriétés de l'accès
    name VARCHAR(255) NOT NULL COMMENT 'Nom de l\'accès (ex: Porte, Passage secret, Pont...)',
    description TEXT NULL COMMENT 'Description de l\'accès',
    
    -- Visibilité et état
    is_visible BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Visible des joueurs',
    is_open BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Ouvert/fermé',
    is_trapped BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Piégé ou non',
    
    -- Détails du piège (si applicable)
    trap_description TEXT NULL COMMENT 'Description du piège',
    trap_difficulty INT NULL COMMENT 'Difficulté du piège (1-20)',
    trap_damage VARCHAR(100) NULL COMMENT 'Dégâts du piège',
    
    -- Position sur la carte (optionnel)
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    is_on_map BOOLEAN DEFAULT FALSE,
    
    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Contraintes
    FOREIGN KEY (from_place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (to_place_id) REFERENCES places(id) ON DELETE CASCADE,
    
    -- Index pour les performances
    INDEX idx_from_place (from_place_id),
    INDEX idx_to_place (to_place_id),
    INDEX idx_visible (is_visible),
    INDEX idx_open (is_open),
    INDEX idx_trapped (is_trapped),
    
    -- Contrainte pour éviter les doublons
    UNIQUE KEY unique_access (from_place_id, to_place_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Accès entre lieux';
