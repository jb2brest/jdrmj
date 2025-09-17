-- =====================================================
-- SCRIPT D'AJOUT DU RÔLE ADMIN
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script ajoute le rôle 'admin' au système d'utilisateurs
-- et met à jour le compte jean.m.bernard@gmail.com
--
-- =====================================================

USE u839591438_jdrmj;

-- =====================================================
-- 1. MODIFICATION DE LA TABLE USERS
-- =====================================================

-- Modifier l'ENUM pour inclure le rôle 'admin'
ALTER TABLE users MODIFY COLUMN role ENUM('player', 'dm', 'admin') DEFAULT 'player';

-- =====================================================
-- 2. MISE À JOUR DU COMPTE ADMIN
-- =====================================================

-- Mettre à jour le compte jean.m.bernard@gmail.com en admin
UPDATE users 
SET role = 'admin', is_dm = 1 
WHERE email = 'jean.m.bernard@gmail.com';

-- =====================================================
-- 3. VÉRIFICATION
-- =====================================================

-- Vérifier que la modification a été effectuée
SELECT 'Utilisateur admin mis à jour:' as Status;
SELECT id, username, email, role, is_dm, is_active 
FROM users 
WHERE email = 'jean.m.bernard@gmail.com';

-- Afficher tous les rôles disponibles
SELECT 'Rôles disponibles dans le système:' as Status;
SELECT DISTINCT role, COUNT(*) as count 
FROM users 
GROUP BY role;

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

SELECT 'Rôle admin ajouté avec succès!' as Message;
