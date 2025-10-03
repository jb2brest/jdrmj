-- Création des tables pour le système de choix d'équipement de départ

-- Table des choix d'équipement de départ
CREATE TABLE IF NOT EXISTS starting_equipment_choix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    src ENUM('class', 'background') NOT NULL COMMENT 'Source du choix (classe ou background)',
    src_id INT NOT NULL COMMENT 'ID de la classe ou du background',
    no_choix INT NOT NULL COMMENT 'Numéro du choix',
    description TEXT COMMENT 'Description du choix',
    is_default BOOLEAN DEFAULT FALSE COMMENT 'Si true, items attribués par défaut. Si false, choix avec options.',
    default_items JSON COMMENT 'Items attribués par défaut (si is_default = true)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_src_src_id (src, src_id),
    INDEX idx_no_choix (no_choix),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des options d'équipement de départ
CREATE TABLE IF NOT EXISTS starting_equipment_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    choix_id INT NOT NULL COMMENT 'ID du choix parent',
    option_letter CHAR(1) NOT NULL COMMENT 'Lettre de l\'option (A, B, C, etc.)',
    description TEXT COMMENT 'Description de l\'option',
    items JSON NOT NULL COMMENT 'Items de cette option',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (choix_id) REFERENCES starting_equipment_choix(id) ON DELETE CASCADE,
    INDEX idx_choix_id (choix_id),
    INDEX idx_option_letter (option_letter),
    UNIQUE KEY unique_choix_option (choix_id, option_letter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




