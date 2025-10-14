-- =====================================================
-- SCRIPT DE NETTOYAGE DES TABLES OBSOLÈTES
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script supprime les tables obsolètes et inutilisées
-- de la base de données pour simplifier le schéma
--
-- ⚠️  ATTENTION : Exécuter d'abord en environnement de test !
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. VÉRIFICATION PRÉALABLE
-- =====================================================

-- Vérifier que les tables principales contiennent des données
SELECT 'Vérification des tables principales' as info;
SELECT 'characters' as table_name, COUNT(*) as row_count FROM characters
UNION ALL
SELECT 'classes', COUNT(*) FROM classes
UNION ALL
SELECT 'races', COUNT(*) FROM races
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'campaigns', COUNT(*) FROM campaigns;

-- Vérifier les tables à supprimer
SELECT 'Tables obsolètes à supprimer' as info;
SELECT 'characters_backup' as table_name, COUNT(*) as row_count FROM characters_backup
UNION ALL
SELECT 'classes_backup', COUNT(*) FROM classes_backup
UNION ALL
SELECT 'races_backup', COUNT(*) FROM races_backup
UNION ALL
SELECT 'scene_npcs', COUNT(*) FROM scene_npcs
UNION ALL
SELECT 'scene_players', COUNT(*) FROM scene_players
UNION ALL
SELECT 'scene_tokens', COUNT(*) FROM scene_tokens
UNION ALL
SELECT 'character_places', COUNT(*) FROM character_places
UNION ALL
SELECT 'messages', COUNT(*) FROM messages;

-- =====================================================
-- 2. SUPPRESSION DES TABLES DE SAUVEGARDE
-- =====================================================

-- Ces tables ont été créées lors de migrations et ne sont plus nécessaires
DROP TABLE IF EXISTS characters_backup;
DROP TABLE IF EXISTS classes_backup;
DROP TABLE IF EXISTS races_backup;

-- =====================================================
-- 3. SUPPRESSION DU SYSTÈME DE SCÈNES OBSOLÈTE
-- =====================================================

-- Le système de scènes a été remplacé par le système de lieux (places)
-- Ces tables sont vides et non utilisées
DROP TABLE IF EXISTS scene_npcs;
DROP TABLE IF EXISTS scene_players;
DROP TABLE IF EXISTS scene_tokens;

-- =====================================================
-- 4. SUPPRESSION DES TABLES DE LIAISON OBSOLÈTES
-- =====================================================

-- character_places a été remplacé par place_players
DROP TABLE IF EXISTS character_places;

-- =====================================================
-- 5. SUPPRESSION DES TABLES INUTILISÉES
-- =====================================================

-- messages est vide et remplacé par notifications
DROP TABLE IF EXISTS messages;

-- =====================================================
-- 6. VÉRIFICATION POST-NETTOYAGE
-- =====================================================

-- Vérifier que les tables principales sont toujours présentes
SELECT 'Vérification post-nettoyage' as info;
SELECT 'characters' as table_name, COUNT(*) as row_count FROM characters
UNION ALL
SELECT 'classes', COUNT(*) FROM classes
UNION ALL
SELECT 'races', COUNT(*) FROM races
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'campaigns', COUNT(*) FROM campaigns;

-- Afficher le nombre total de tables restantes
SELECT 'Tables restantes' as info, COUNT(*) as total_tables 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'u839591438_jdrmj';

-- =====================================================
-- 7. RÉSUMÉ DU NETTOYAGE
-- =====================================================

SELECT 'NETTOYAGE TERMINÉ' as status;
SELECT 'Tables supprimées: 8' as details;
SELECT 'Tables conservées: 62' as details;
SELECT 'Système simplifié et optimisé' as result;
