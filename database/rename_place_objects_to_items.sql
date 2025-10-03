-- Script pour renommer la table place_objects en items
-- Ce script renomme la table et met à jour les références

-- 1. Renommer la table place_objects en items
RENAME TABLE place_objects TO items;

-- 2. Mettre à jour les contraintes de clés étrangères si nécessaire
-- (Les contraintes existantes seront automatiquement mises à jour)

-- 3. Vérifier que la table a été correctement renommée
-- Cette requête peut être exécutée pour vérifier
-- SHOW TABLES LIKE 'items';

-- 4. Vérifier la structure de la nouvelle table
-- Cette requête peut être exécutée pour vérifier
-- DESCRIBE items;

-- Note: Les index et contraintes de clés étrangères sont automatiquement
-- renommés lors du renommage de la table.

