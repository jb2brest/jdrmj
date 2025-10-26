-- Script pour renommer les colonnes de monnaie dans la table characters
-- Pour avoir la même convention que la table npcs (gold, silver, copper)

-- Renommer les colonnes de monnaie
ALTER TABLE characters 
CHANGE COLUMN money_gold gold INT DEFAULT 0 COMMENT 'Quantité de pièces d\'or',
CHANGE COLUMN money_silver silver INT DEFAULT 0 COMMENT 'Quantité de pièces d\'argent',
CHANGE COLUMN money_copper copper INT DEFAULT 0 COMMENT 'Quantité de pièces de cuivre';

-- Vérifier que les colonnes ont été renommées
DESCRIBE characters;
