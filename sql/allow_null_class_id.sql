-- Script SQL pour permettre class_id NULL dans la table npcs
-- À exécuter dans phpMyAdmin ou via la ligne de commande MySQL

-- Modifier la colonne class_id pour accepter NULL
ALTER TABLE `npcs` 
MODIFY COLUMN `class_id` INT(11) NULL;

-- Vérifier que la modification a été appliquée
DESCRIBE `npcs`;
