-- =====================================================
-- Ajout d'une colonne commentaire à la table groupe_membres
-- =====================================================
-- 
-- Permet d'ajouter un commentaire à chaque participation d'un membre à un groupe
--
-- =====================================================

ALTER TABLE groupe_membres 
ADD COLUMN comment TEXT NULL COMMENT 'Commentaire sur la participation du membre au groupe' 
AFTER is_secret;


