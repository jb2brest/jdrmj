-- =====================================================
-- SCRIPT DE SUPPRESSION DU SYSTÈME DE SESSIONS
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script supprime complètement le système de sessions
-- et les tables associées de la base de données
--
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. SUPPRESSION DES CONTRAINTES DE CLÉS ÉTRANGÈRES
-- =====================================================

-- Supprimer la contrainte de clé étrangère dans la table scenes
ALTER TABLE scenes DROP FOREIGN KEY IF EXISTS scenes_ibfk_1;

-- =====================================================
-- 2. SUPPRESSION DES COLONNES LIÉES AUX SESSIONS
-- =====================================================

-- Supprimer la colonne session_id de la table scenes
ALTER TABLE scenes DROP COLUMN IF EXISTS session_id;

-- =====================================================
-- 3. SUPPRESSION DES TABLES DE SESSIONS
-- =====================================================

-- Supprimer la table des inscriptions aux sessions
DROP TABLE IF EXISTS session_registrations;

-- Supprimer la table des sessions de jeu
DROP TABLE IF EXISTS game_sessions;

-- =====================================================
-- 4. VÉRIFICATION POST-SUPPRESSION
-- =====================================================

-- Vérifier que les tables ont été supprimées
SELECT 'Tables de sessions supprimées avec succès' as Status;

-- Lister les tables restantes (optionnel)
-- SHOW TABLES;

-- =====================================================
-- NOTES IMPORTANTES
-- =====================================================
-- 
-- Ce script supprime définitivement :
-- - La table game_sessions (sessions de jeu)
-- - La table session_registrations (inscriptions aux sessions)
-- - La colonne session_id de la table scenes
-- - Les contraintes de clés étrangères associées
--
-- Les lieux (places) sont maintenant directement liés aux campagnes
-- via la colonne campaign_id dans la table places.
--
-- =====================================================
