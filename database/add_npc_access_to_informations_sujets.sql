-- =====================================================
-- Ajout du support des PNJ pour les accès aux informations et sujets
-- =====================================================

-- Modifier la table information_access pour ajouter le support des PNJ
ALTER TABLE information_access 
    MODIFY COLUMN access_type ENUM('player', 'group', 'npc') NOT NULL;

ALTER TABLE information_access 
    ADD COLUMN npc_id INT NULL AFTER groupe_id;

ALTER TABLE information_access 
    ADD FOREIGN KEY (npc_id) REFERENCES place_npcs(id) ON DELETE CASCADE;

-- Supprimer les anciennes contraintes UNIQUE et en créer de nouvelles
ALTER TABLE information_access 
    DROP INDEX IF EXISTS unique_player_access,
    DROP INDEX IF EXISTS unique_group_access;

ALTER TABLE information_access 
    ADD UNIQUE KEY unique_player_access (information_id, access_type, player_id),
    ADD UNIQUE KEY unique_group_access (information_id, access_type, groupe_id, niveau_min, niveau_max),
    ADD UNIQUE KEY unique_npc_access (information_id, access_type, npc_id),
    ADD INDEX idx_npc_id (npc_id);

-- Modifier la table sujet_access pour ajouter le support des PNJ
ALTER TABLE sujet_access 
    MODIFY COLUMN access_type ENUM('player', 'group', 'npc') NOT NULL;

ALTER TABLE sujet_access 
    ADD COLUMN npc_id INT NULL AFTER groupe_id;

ALTER TABLE sujet_access 
    ADD FOREIGN KEY (npc_id) REFERENCES place_npcs(id) ON DELETE CASCADE;

-- Supprimer les anciennes contraintes UNIQUE et en créer de nouvelles
ALTER TABLE sujet_access 
    DROP INDEX IF EXISTS unique_player_access,
    DROP INDEX IF EXISTS unique_group_access;

ALTER TABLE sujet_access 
    ADD UNIQUE KEY unique_player_access (sujet_id, access_type, player_id),
    ADD UNIQUE KEY unique_group_access (sujet_id, access_type, groupe_id, niveau_min, niveau_max),
    ADD UNIQUE KEY unique_npc_access (sujet_id, access_type, npc_id),
    ADD INDEX idx_npc_id (npc_id);
