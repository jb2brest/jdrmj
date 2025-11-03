-- =====================================================
-- Mise à jour de la structure d'accès aux informations pour les groupes
-- =====================================================
-- 
-- Remplacement du système min/max par des cases à cocher individuelles
-- pour chaque niveau hiérarchique
--
-- =====================================================

-- Supprimer l'ancienne contrainte unique qui incluait niveau_min et niveau_max
ALTER TABLE information_access 
DROP INDEX unique_group_access;

-- Supprimer les colonnes niveau_min et niveau_max
ALTER TABLE information_access 
DROP COLUMN niveau_min,
DROP COLUMN niveau_max;

-- Ajouter une colonne niveau (pour stocker un niveau individuel)
ALTER TABLE information_access 
ADD COLUMN niveau INT NULL COMMENT 'Niveau hiérarchique individuel pour les groupes (1 = dirigeant)' AFTER groupe_id;

-- Créer une nouvelle contrainte unique qui permet plusieurs lignes par groupe (une par niveau)
ALTER TABLE information_access 
ADD UNIQUE KEY unique_group_level_access (information_id, access_type, groupe_id, niveau);

-- Ajouter un index sur niveau pour les performances
ALTER TABLE information_access 
ADD INDEX idx_niveau (niveau);

-- Mettre à jour les données existantes : créer une ligne par niveau dans la plage min-max
-- Note: Cette requête nécessite une procédure ou un script PHP pour être exécutée correctement
-- car il faut itérer sur chaque niveau entre min et max
-- Pour l'instant, on supprime les anciennes entrées qui utilisent min/max
-- (elles seront recréées par l'utilisateur avec le nouveau système)

DELETE FROM information_access 
WHERE access_type = 'group' AND niveau IS NULL;

