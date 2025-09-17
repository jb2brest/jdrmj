-- =====================================================
-- SCRIPT DE VÉRIFICATION POUR LA PRODUCTION
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script vérifie l'intégrité et la structure de la base de données
-- après l'initialisation ou après des modifications
-- ENVIRONNEMENT PRODUCTION
--
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. VÉRIFICATION DES TABLES
-- =====================================================

SELECT '=== VÉRIFICATION DES TABLES ===' as Status;

-- Liste de toutes les tables
SELECT 'Tables présentes dans la base:' as Info;
SHOW TABLES;

-- Vérification des tables principales
SELECT 'Tables principales:' as Info;
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME, UPDATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'u839591438_jdrmj' 
AND TABLE_NAME IN ('users', 'characters', 'races', 'classes', 'campaigns', 'game_sessions', 'scenes')
ORDER BY TABLE_NAME;

-- =====================================================
-- 2. VÉRIFICATION DES CONTRAINTES DE CLÉS ÉTRANGÈRES
-- =====================================================

SELECT '=== VÉRIFICATION DES CONTRAINTES ===' as Status;

-- Contraintes de clés étrangères
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'u839591438_jdrmj' 
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;

-- =====================================================
-- 3. VÉRIFICATION DES INDEX
-- =====================================================

SELECT '=== VÉRIFICATION DES INDEX ===' as Status;

-- Index sur les tables principales
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'u839591438_jdrmj' 
AND TABLE_NAME IN ('users', 'characters', 'campaigns', 'game_sessions', 'scenes')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- 4. VÉRIFICATION DES DONNÉES INITIALES
-- =====================================================

SELECT '=== VÉRIFICATION DES DONNÉES INITIALES ===' as Status;

-- Comptage des races
SELECT 'Races disponibles:' as Info, COUNT(*) as Count FROM races;

-- Comptage des classes
SELECT 'Classes disponibles:' as Info, COUNT(*) as Count FROM classes;

-- Comptage des historiques
SELECT 'Backgrounds disponibles:' as Info, COUNT(*) as Count FROM backgrounds;

-- Comptage des langues
SELECT 'Langues disponibles:' as Info, COUNT(*) as Count FROM languages;

-- Comptage des niveaux d'expérience
SELECT 'Niveaux d\'expérience:' as Info, COUNT(*) as Count FROM experience_levels;

-- =====================================================
-- 5. VÉRIFICATION DE L'INTÉGRITÉ DES DONNÉES
-- =====================================================

SELECT '=== VÉRIFICATION DE L\'INTÉGRITÉ ===' as Status;

-- Vérification des niveaux d'expérience (doivent être de 1 à 20)
SELECT 
    'Niveaux d\'expérience manquants:' as Info,
    GROUP_CONCAT(missing_levels.level SEPARATOR ', ') as Missing_Levels
FROM (
    SELECT n.level
    FROM (SELECT 1 as level UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
          UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
          UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
          UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20) n
    LEFT JOIN experience_levels el ON n.level = el.level
    WHERE el.level IS NULL
) missing_levels;

-- Vérification des bonus de compétence (doivent correspondre aux règles D&D 5e)
SELECT 
    'Niveaux avec bonus de compétence incorrect:' as Info,
    GROUP_CONCAT(CONCAT('Niveau ', level, ' (bonus: ', proficiency_bonus, ')') SEPARATOR ', ') as Incorrect_Bonuses
FROM experience_levels 
WHERE (level <= 4 AND proficiency_bonus != 2) OR
      (level >= 5 AND level <= 8 AND proficiency_bonus != 3) OR
      (level >= 9 AND level <= 12 AND proficiency_bonus != 4) OR
      (level >= 13 AND level <= 16 AND proficiency_bonus != 5) OR
      (level >= 17 AND level <= 20 AND proficiency_bonus != 6);

-- =====================================================
-- 6. VÉRIFICATION DES PERMISSIONS ET SÉCURITÉ
-- =====================================================

SELECT '=== VÉRIFICATION DE LA SÉCURITÉ ===' as Status;

-- Vérification des utilisateurs de la base de données
SELECT 'Utilisateurs avec accès à la base:' as Info;
SELECT User, Host, Select_priv, Insert_priv, Update_priv, Delete_priv
FROM mysql.user 
WHERE User IN (SELECT DISTINCT User FROM mysql.db WHERE Db = 'u839591438_jdrmj');

-- =====================================================
-- 7. VÉRIFICATION DES PERFORMANCES
-- =====================================================

SELECT '=== VÉRIFICATION DES PERFORMANCES ===' as Status;

-- Taille des tables
SELECT 
    TABLE_NAME as 'Table',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Taille (MB)',
    TABLE_ROWS as 'Lignes estimées'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'u839591438_jdrmj'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- =====================================================
-- 8. TESTS DE FONCTIONNALITÉ
-- =====================================================

SELECT '=== TESTS DE FONCTIONNALITÉ ===' as Status;

-- Test de création d'un utilisateur de test
INSERT IGNORE INTO users (username, email, password_hash, role) 
VALUES ('test_user_prod', 'test@example.com', 'test_hash', 'player');

-- Test de création d'un personnage de test
INSERT IGNORE INTO characters (user_id, name, race_id, class_id, level) 
SELECT u.id, 'Test Character Prod', r.id, c.id, 1
FROM users u, races r, classes c
WHERE u.username = 'test_user_prod' 
AND r.name = 'Humain' 
AND c.name = 'Guerrier'
LIMIT 1;

-- Vérification de la création
SELECT 'Personnage de test créé:' as Info, COUNT(*) as Count 
FROM characters c 
JOIN users u ON c.user_id = u.id 
WHERE u.username = 'test_user_prod';

-- Nettoyage des données de test
DELETE FROM characters WHERE user_id IN (SELECT id FROM users WHERE username = 'test_user_prod');
DELETE FROM users WHERE username = 'test_user_prod';

-- =====================================================
-- 9. RÉSUMÉ DE LA VÉRIFICATION
-- =====================================================

SELECT '=== RÉSUMÉ DE LA VÉRIFICATION ===' as Status;

-- Statistiques générales
SELECT 
    'Statistiques générales:' as Info,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'u839591438_jdrmj') as 'Tables',
    (SELECT COUNT(*) FROM races) as 'Races',
    (SELECT COUNT(*) FROM classes) as 'Classes',
    (SELECT COUNT(*) FROM backgrounds) as 'Backgrounds',
    (SELECT COUNT(*) FROM languages) as 'Langues',
    (SELECT COUNT(*) FROM experience_levels) as 'Niveaux';

-- Vérification finale
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM races) >= 8 
         AND (SELECT COUNT(*) FROM classes) >= 12 
         AND (SELECT COUNT(*) FROM experience_levels) = 20
        THEN '✅ Base de données de production correctement initialisée'
        ELSE '❌ Problèmes détectés dans l\'initialisation'
    END as 'Statut Final';

-- =====================================================
-- FIN DU SCRIPT DE VÉRIFICATION
-- =====================================================
