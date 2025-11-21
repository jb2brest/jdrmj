-- Table pour stocker les positions des lieux dans la cartographie de la région
CREATE TABLE IF NOT EXISTS region_cartography_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    place_id INT NOT NULL,
    position_x INT NOT NULL DEFAULT 0,
    position_y INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    UNIQUE KEY unique_region_place (region_id, place_id),
    INDEX idx_region_id (region_id),
    INDEX idx_place_id (place_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

