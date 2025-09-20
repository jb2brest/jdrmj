-- Ajouter les champs pour les quantités de pièces
ALTER TABLE place_objects 
ADD COLUMN gold_coins INT DEFAULT 0 COMMENT 'Quantité de pièces d\'or',
ADD COLUMN silver_coins INT DEFAULT 0 COMMENT 'Quantité de pièces d\'argent',
ADD COLUMN copper_coins INT DEFAULT 0 COMMENT 'Quantité de pièces de cuivre';
