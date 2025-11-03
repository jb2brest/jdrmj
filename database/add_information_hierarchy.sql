-- =====================================================
-- Ajout de la hiérarchie récursive aux informations
-- =====================================================
-- 
-- Une information peut contenir une liste ordonnée de sous-informations
-- Structure récursive : informations (1) ←→ (N) information_informations
--
-- =====================================================

-- Ajouter la colonne parent_id à la table informations (optionnel, pour faciliter les requêtes)
ALTER TABLE informations 
ADD COLUMN parent_id INT NULL AFTER id,
ADD FOREIGN KEY (parent_id) REFERENCES informations(id) ON DELETE CASCADE,
ADD INDEX idx_parent_id (parent_id);

-- Table de liaison information-information (sous-informations avec ordre)
CREATE TABLE IF NOT EXISTS information_informations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_information_id INT NOT NULL,
    child_information_id INT NOT NULL,
    ordre INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_information_id) REFERENCES informations(id) ON DELETE CASCADE,
    FOREIGN KEY (child_information_id) REFERENCES informations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parent_child (parent_information_id, child_information_id),
    INDEX idx_parent_information_id (parent_information_id),
    INDEX idx_child_information_id (child_information_id),
    INDEX idx_ordre (ordre),
    CHECK (parent_information_id != child_information_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

