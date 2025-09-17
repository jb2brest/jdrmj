-- Table pour stocker les données d'expérience par niveau
CREATE TABLE IF NOT EXISTS experience_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level INT NOT NULL UNIQUE,
    experience_points_required INT NOT NULL,
    proficiency_bonus INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des données d'expérience depuis le CSV
INSERT INTO experience_levels (level, experience_points_required, proficiency_bonus) VALUES
(1, 0, 2),
(2, 300, 2),
(3, 900, 2),
(4, 2700, 2),
(5, 6500, 3),
(6, 14000, 3),
(7, 23000, 3),
(8, 34000, 3),
(9, 48000, 4),
(10, 64000, 4),
(11, 85000, 4),
(12, 100000, 4),
(13, 120000, 5),
(14, 140000, 5),
(15, 165000, 5),
(16, 195000, 5),
(17, 225000, 6),
(18, 265000, 6),
(19, 305000, 6),
(20, 355000, 6)
ON DUPLICATE KEY UPDATE
    experience_points_required = VALUES(experience_points_required),
    proficiency_bonus = VALUES(proficiency_bonus);

