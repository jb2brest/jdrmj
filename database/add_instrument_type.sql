-- Ajouter le type 'instrument' à la table Object
ALTER TABLE Object MODIFY COLUMN type ENUM('sac', 'outils', 'nourriture', 'instrument') NOT NULL;
