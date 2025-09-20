-- Création de la table pour stocker les objets dans les lieux
CREATE TABLE IF NOT EXISTS place_objects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    object_type ENUM('poison', 'vial', 'coins', 'letter', 'equipment', 'other') NOT NULL DEFAULT 'other',
    is_visible BOOLEAN NOT NULL DEFAULT TRUE,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    is_on_map BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    INDEX idx_place_id (place_id),
    INDEX idx_object_type (object_type),
    INDEX idx_is_visible (is_visible)
);
