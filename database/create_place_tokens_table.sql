-- Table pour stocker les positions des pions sur les plans
CREATE TABLE IF NOT EXISTS place_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    token_type ENUM('player', 'npc', 'monster') NOT NULL,
    entity_id INT NOT NULL,
    position_x INT NOT NULL DEFAULT 0,
    position_y INT NOT NULL DEFAULT 0,
    is_on_map BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (place_id, token_type, entity_id)
);
