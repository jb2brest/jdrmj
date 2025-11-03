-- =====================================================
-- Ajout du support des monstres pour les accès aux informations et sujets
-- =====================================================

-- Modifier la table information_access pour ajouter le support des monstres
ALTER TABLE information_access 
    MODIFY COLUMN access_type ENUM('player', 'group', 'npc', 'monster') NOT NULL;

-- Les monstres utilisent aussi npc_id car ils sont dans place_npcs
-- Pas besoin d'ajouter de nouvelle colonne, on réutilise npc_id

-- Modifier la table sujet_access pour ajouter le support des monstres
ALTER TABLE sujet_access 
    MODIFY COLUMN access_type ENUM('player', 'group', 'npc', 'monster') NOT NULL;

-- Les monstres utilisent aussi npc_id car ils sont dans place_npcs
-- Pas besoin d'ajouter de nouvelle colonne, on réutilise npc_id


