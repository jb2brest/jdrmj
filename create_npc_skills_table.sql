-- Création de la table npc_skills pour les compétences des NPCs
CREATE TABLE IF NOT EXISTS npc_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    npc_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    proficiency_bonus INT DEFAULT 2,
    is_proficient BOOLEAN DEFAULT FALSE,
    is_expertise BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    learned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (npc_id) REFERENCES npcs(id) ON DELETE CASCADE,
    INDEX idx_npc_id (npc_id),
    INDEX idx_skill_name (skill_name),
    UNIQUE KEY unique_npc_skill (npc_id, skill_name)
);
