-- =====================================================
-- Suppression du système d'Informations et Sujets
-- =====================================================
-- 
-- Ce script supprime toutes les tables liées au système
-- d'informations et de sujets
--
-- ATTENTION : Cette opération est IRREVERSIBLE
-- Assurez-vous d'avoir une sauvegarde avant d'exécuter ce script
--
-- =====================================================

-- Supprimer les tables dans l'ordre (en respectant les contraintes de clés étrangères)
DROP TABLE IF EXISTS sujet_access;
DROP TABLE IF EXISTS information_access;
DROP TABLE IF EXISTS sujet_informations;
DROP TABLE IF EXISTS sujets;
DROP TABLE IF EXISTS informations;


