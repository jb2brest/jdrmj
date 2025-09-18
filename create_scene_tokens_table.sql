-- Table pour stocker les positions des pions sur le plan de la scène
CREATE TABLE IF NOT EXISTS scene_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    token_type ENUM('player', 'npc', 'monster') NOT NULL,
    entity_id INT NOT NULL, -- ID du joueur, PNJ ou monstre
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    is_on_map BOOLEAN DEFAULT FALSE, -- TRUE si sur le plan, FALSE si sur le côté
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scene_entity (scene_id, token_type, entity_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_token_type (token_type),
    INDEX idx_entity_id (entity_id)
);













