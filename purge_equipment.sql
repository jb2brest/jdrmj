-- Script pour purger tous les équipements attribués de la base de données
-- ATTENTION: Cette opération est IRRÉVERSIBLE !

-- 1. Purger l'équipement des personnages joueurs
DELETE FROM character_equipment;

-- 2. Purger l'équipement des PNJ
DELETE FROM npc_equipment;

-- 3. Purger l'équipement des monstres
DELETE FROM monster_equipment;

-- Vérification des tables vides
SELECT 'character_equipment' as table_name, COUNT(*) as remaining_records FROM character_equipment
UNION ALL
SELECT 'npc_equipment' as table_name, COUNT(*) as remaining_records FROM npc_equipment
UNION ALL
SELECT 'monster_equipment' as table_name, COUNT(*) as remaining_records FROM monster_equipment;













