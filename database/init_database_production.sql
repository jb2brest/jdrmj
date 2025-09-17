-- =====================================================
-- SCRIPT D'INITIALISATION POUR LA PRODUCTION
-- Application JDR MJ - D&D 5e
-- =====================================================
-- 
-- Ce script crée l'ensemble de la structure de base de données
-- pour l'application de gestion de campagnes D&D 5e
-- ENVIRONNEMENT PRODUCTION
--
-- =====================================================

-- Configuration de la base de données
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. UTILISATION DE LA BASE DE DONNÉES EXISTANTE
-- =====================================================

-- Utiliser la base de données de production existante
USE u839591438_jdrmj;

-- =====================================================
-- 2. TABLES PRINCIPALES
-- =====================================================

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('player', 'dm') DEFAULT 'player',
    bio TEXT,
    avatar VARCHAR(255),
    experience_level ENUM('debutant', 'intermediaire', 'expert') DEFAULT 'debutant',
    preferred_game_system VARCHAR(50) DEFAULT 'D&D 5e',
    timezone VARCHAR(50) DEFAULT 'Europe/Paris',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
);

-- Table des races D&D
CREATE TABLE IF NOT EXISTS races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    ability_score_bonus VARCHAR(100),
    traits TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
);

-- Table des classes D&D
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    hit_die INT NOT NULL,
    primary_ability VARCHAR(50),
    saving_throw_proficiencies VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
);

-- Table des historiques/backgrounds
CREATE TABLE IF NOT EXISTS backgrounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    skill_proficiencies TEXT, -- JSON array des compétences maîtrisées
    tool_proficiencies TEXT,  -- JSON array des outils maîtrisés
    languages TEXT,           -- JSON array des langues
    equipment TEXT,           -- Description de l'équipement
    feature VARCHAR(255),     -- Nom de la capacité spéciale
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
);

-- Table des langues
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    typical_races VARCHAR(255),
    script VARCHAR(100),
    type ENUM('standard', 'exotique') DEFAULT 'standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table des niveaux d'expérience
CREATE TABLE IF NOT EXISTS experience_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level INT NOT NULL UNIQUE,
    experience_points_required INT NOT NULL,
    proficiency_bonus INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level (level)
);

-- =====================================================
-- 3. TABLE DES PERSONNAGES
-- =====================================================

CREATE TABLE IF NOT EXISTS characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    race_id INT NOT NULL,
    class_id INT NOT NULL,
    background_id INT DEFAULT NULL,
    level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    
    -- Statistiques de base
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    
    -- Informations de combat
    armor_class INT DEFAULT 10,
    initiative INT DEFAULT 0,
    speed INT DEFAULT 30,
    hit_points_max INT DEFAULT 0,
    hit_points_current INT DEFAULT 0,
    
    -- Compétences et proficiens
    proficiency_bonus INT DEFAULT 2,
    saving_throws TEXT,
    skills TEXT,
    languages TEXT,
    
    -- Équipement et trésor
    equipment TEXT,
    money_gold INT DEFAULT 0,
    money_silver INT DEFAULT 0,
    money_copper INT DEFAULT 0,
    
    -- Informations personnelles
    background VARCHAR(100),
    alignment VARCHAR(20),
    personality_traits TEXT,
    ideals TEXT,
    bonds TEXT,
    flaws TEXT,
    
    -- Sorts (pour les lanceurs de sorts)
    spells_known TEXT,
    spell_slots TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (race_id) REFERENCES races(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (background_id) REFERENCES backgrounds(id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_race_id (race_id),
    INDEX idx_class_id (class_id),
    INDEX idx_level (level)
);

-- =====================================================
-- 4. TABLES DE CAMPAGNES ET SESSIONS
-- =====================================================

-- Table des campagnes
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    game_system VARCHAR(50) DEFAULT 'D&D 5e',
    is_public BOOLEAN DEFAULT TRUE,
    invite_code VARCHAR(16) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dm_id (dm_id),
    INDEX idx_title (title),
    INDEX idx_is_public (is_public),
    INDEX idx_invite_code (invite_code)
);

-- Table des membres de campagne
CREATE TABLE IF NOT EXISTS campaign_members (
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('player', 'dm') DEFAULT 'player',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (campaign_id, user_id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des candidatures aux campagnes
CREATE TABLE IF NOT EXISTS campaign_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    player_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','approved','declined','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uniq_application (campaign_id, player_id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_player_id (player_id),
    INDEX idx_status (status)
);

-- Table des sessions de jeu
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
    campaign_id INT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    game_system VARCHAR(50) DEFAULT 'D&D 5e',
    max_players INT DEFAULT 6,
    current_players INT DEFAULT 0,
    session_date DATETIME,
    duration_hours INT DEFAULT 4,
    location VARCHAR(100),
    is_online BOOLEAN DEFAULT FALSE,
    meeting_link VARCHAR(255),
    status ENUM('planning', 'recruiting', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    INDEX idx_dm_id (dm_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status),
    INDEX idx_session_date (session_date)
);

-- Table des inscriptions aux sessions
CREATE TABLE IF NOT EXISTS session_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    player_id INT NOT NULL,
    character_id INT NULL,
    status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
    UNIQUE KEY unique_session_player (session_id, player_id),
    INDEX idx_session_id (session_id),
    INDEX idx_player_id (player_id),
    INDEX idx_status (status)
);

-- =====================================================
-- 5. TABLES DE SCÈNES ET TOKENS
-- =====================================================

-- Table des scènes
CREATE TABLE IF NOT EXISTS scenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    map_url VARCHAR(255),
    notes TEXT,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_position (position)
);

-- Table des joueurs dans les scènes
CREATE TABLE IF NOT EXISTS scene_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    player_id INT NOT NULL,
    character_id INT NULL,
    status ENUM('active', 'inactive', 'absent') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
    UNIQUE KEY unique_scene_player (scene_id, player_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_player_id (player_id),
    INDEX idx_status (status)
);

-- Table des PNJ dans les scènes
CREATE TABLE IF NOT EXISTS scene_npcs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    npc_character_id INT NULL,
    monster_id INT NULL,
    quantity INT DEFAULT 1,
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
    FOREIGN KEY (npc_character_id) REFERENCES characters(id) ON DELETE SET NULL,
    INDEX idx_scene_id (scene_id),
    INDEX idx_name (name),
    INDEX idx_monster_id (monster_id)
);

-- Table des positions des tokens sur les scènes
CREATE TABLE IF NOT EXISTS scene_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scene_id INT NOT NULL,
    token_type ENUM('player', 'npc', 'monster') NOT NULL,
    entity_id INT NOT NULL,
    x_position DECIMAL(10,2) NOT NULL,
    y_position DECIMAL(10,2) NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    label VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_scene_entity (scene_id, token_type, entity_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_token_type (token_type),
    INDEX idx_entity_id (entity_id)
);

-- =====================================================
-- 6. TABLES DE DONNÉES D&D
-- =====================================================

-- Table des sorts
CREATE TABLE IF NOT EXISTS spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level INT DEFAULT 0,
    school VARCHAR(50),
    casting_time VARCHAR(50),
    range_sp VARCHAR(50),
    components TEXT,
    duration VARCHAR(50),
    description TEXT,
    classes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_level (level),
    INDEX idx_school (school),
    FULLTEXT idx_search (name, description, classes)
);

-- Table de liaison personnage-sorts
CREATE TABLE IF NOT EXISTS character_spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    spell_id INT NOT NULL,
    is_prepared BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (spell_id) REFERENCES spells(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_spell (character_id, spell_id),
    INDEX idx_character_id (character_id),
    INDEX idx_spell_id (spell_id)
);

-- Table des monstres D&D
CREATE TABLE IF NOT EXISTS dnd_monsters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    size VARCHAR(50),
    alignment VARCHAR(50),
    challenge_rating VARCHAR(20),
    hit_points INT,
    armor_class INT,
    speed VARCHAR(100),
    proficiency_bonus INT,
    description TEXT,
    actions TEXT,
    special_abilities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_cr (challenge_rating),
    FULLTEXT idx_search (name, type, description)
);

-- Table des objets magiques
CREATE TABLE IF NOT EXISTS magical_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);

-- Table des poisons
CREATE TABLE IF NOT EXISTS poisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    csv_id VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    cle VARCHAR(255),
    description TEXT,
    type VARCHAR(255),
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_csv_id (csv_id),
    INDEX idx_nom (nom),
    INDEX idx_type (type),
    FULLTEXT idx_search (nom, cle, description, type)
);

-- =====================================================
-- 7. TABLES D'ÉQUIPEMENT
-- =====================================================

-- Table des armes
CREATE TABLE IF NOT EXISTS weapons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    damage VARCHAR(50) NOT NULL,
    weight VARCHAR(20) NOT NULL,
    price VARCHAR(20) NOT NULL,
    properties TEXT,
    hands INT NOT NULL DEFAULT 1,
    type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table des armures
CREATE TABLE IF NOT EXISTS armor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    ac_formula VARCHAR(50) NOT NULL,
    strength_requirement VARCHAR(20),
    stealth_disadvantage VARCHAR(20),
    weight VARCHAR(20) NOT NULL,
    price VARCHAR(20) NOT NULL,
    type VARCHAR(100) NOT NULL,
    don_time VARCHAR(20) NOT NULL,
    doff_time VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_type (type)
);

-- Table de l'équipement des personnages
CREATE TABLE IF NOT EXISTS character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character_id (character_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id),
    INDEX idx_equipped (equipped)
);

-- Table de l'équipement des PNJ
CREATE TABLE IF NOT EXISTS npc_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    npc_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    INDEX idx_npc_id (npc_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);

-- Table de l'équipement des monstres
CREATE TABLE IF NOT EXISTS monster_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monster_id INT NOT NULL,
    scene_id INT NOT NULL,
    magical_item_id VARCHAR(50),
    item_name VARCHAR(255) NOT NULL,
    item_type VARCHAR(100),
    item_description TEXT,
    item_source VARCHAR(100),
    quantity INT DEFAULT 1,
    equipped BOOLEAN DEFAULT FALSE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    obtained_from VARCHAR(100) DEFAULT 'Attribution MJ',
    
    INDEX idx_monster_id (monster_id),
    INDEX idx_scene_id (scene_id),
    INDEX idx_item_name (item_name),
    INDEX idx_magical_item_id (magical_item_id)
);

-- =====================================================
-- 8. TABLES DE NOTIFICATIONS
-- =====================================================

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- 9. DONNÉES INITIALES
-- =====================================================

-- Insertion des niveaux d'expérience D&D 5e
INSERT INTO experience_levels (level, experience_points_required, proficiency_bonus) VALUES
(1, 0, 2),
(2, 300, 2),
(3, 900, 2),
(4, 2700, 2),
(5, 6500, 3),
(6, 14000, 3),
(7, 23000, 3),
(8, 34000, 3),
(9, 48000, 4),
(10, 64000, 4),
(11, 85000, 4),
(12, 100000, 4),
(13, 120000, 5),
(14, 140000, 5),
(15, 165000, 5),
(16, 195000, 5),
(17, 225000, 6),
(18, 265000, 6),
(19, 305000, 6),
(20, 355000, 6)
ON DUPLICATE KEY UPDATE
    experience_points_required = VALUES(experience_points_required),
    proficiency_bonus = VALUES(proficiency_bonus);

-- Insertion des races de base D&D 5e
INSERT INTO races (name, description, ability_score_bonus, traits) VALUES
('Humain', 'Les humains sont la race la plus adaptable et ambitieuse.', '+1 à toutes les caractéristiques', 'Versatilité humaine'),
('Elfe', 'Les elfes sont une race gracieuse et mystérieuse.', '+2 Dextérité, +1 Intelligence', 'Vision dans le noir, Fey Ancestry'),
('Nain', 'Les nains sont une race robuste et résistante.', '+2 Constitution, +2 Force ou +2 Sagesse', 'Vision dans le noir, Résistance aux poisons'),
('Halfelin', 'Les halfelins sont une race courageuse et optimiste.', '+2 Dextérité, +1 Charisme', 'Chance, Courage'),
('Demi-elfe', 'Les demi-elfes combinent le meilleur des humains et des elfes.', '+2 Charisme, +1 à deux autres caractéristiques', 'Vision dans le noir, Fey Ancestry'),
('Demi-orc', 'Les demi-orcs sont une race forte et féroce.', '+2 Force, +1 Constitution', 'Vision dans le noir, Relentless Endurance'),
('Gnome', 'Les gnomes sont une race curieuse et inventive.', '+2 Intelligence, +1 Dextérité ou +1 Constitution', 'Vision dans le noir, Gnome Cunning'),
('Tieffelin', 'Les tieffelins sont une race avec du sang diabolique.', '+2 Charisme, +1 Intelligence', 'Vision dans le noir, Résistance au feu')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    ability_score_bonus = VALUES(ability_score_bonus),
    traits = VALUES(traits);

-- Insertion des classes de base D&D 5e
INSERT INTO classes (name, description, hit_die, primary_ability, saving_throw_proficiencies) VALUES
('Barbare', 'Un guerrier sauvage qui puise sa force dans la rage.', 12, 'Force', 'Force, Constitution'),
('Barde', 'Un artiste magique qui inspire ses alliés.', 8, 'Charisme', 'Dextérité, Charisme'),
('Clerc', 'Un prêtre divin qui canalise la puissance divine.', 8, 'Sagesse', 'Sagesse, Charisme'),
('Druide', 'Un gardien de la nature qui peut se transformer.', 8, 'Sagesse', 'Intelligence, Sagesse'),
('Guerrier', 'Un maître des armes et de l\'armure.', 10, 'Force ou Dextérité', 'Force, Constitution'),
('Moine', 'Un maître des arts martiaux et de la discipline.', 8, 'Dextérité et Sagesse', 'Force, Dextérité'),
('Paladin', 'Un champion divin qui jure de protéger les innocents.', 10, 'Force et Charisme', 'Sagesse, Charisme'),
('Rôdeur', 'Un gardien de la nature qui excelle en survie.', 10, 'Dextérité et Sagesse', 'Force, Dextérité'),
('Roublard', 'Un maître de la furtivité et de la précision.', 8, 'Dextérité', 'Dextérité, Intelligence'),
('Ensorceleur', 'Un lanceur de sorts inné avec du sang magique.', 6, 'Charisme', 'Constitution, Charisme'),
('Magicien', 'Un érudit qui étudie la magie arcanique.', 6, 'Intelligence', 'Intelligence, Sagesse'),
('Occultiste', 'Un pacte avec une entité extraplanaire.', 8, 'Charisme', 'Sagesse, Charisme')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    hit_die = VALUES(hit_die),
    primary_ability = VALUES(primary_ability),
    saving_throw_proficiencies = VALUES(saving_throw_proficiencies);

-- Insertion des langues de base D&D 5e
INSERT INTO languages (name, typical_races, script, type) VALUES
('Commun', 'Toutes les races', 'Commun', 'standard'),
('Elfe', 'Elfes, Demi-elfes', 'Elfe', 'standard'),
('Nain', 'Nains', 'Nain', 'standard'),
('Gnomique', 'Gnomes', 'Nain', 'standard'),
('Halfelin', 'Halfelins', 'Commun', 'standard'),
('Orc', 'Demi-orcs, Orcs', 'Orc', 'standard'),
('Draconique', 'Dragonnés, Dragons', 'Draconique', 'exotique'),
('Céleste', 'Anges, Dévots', 'Céleste', 'exotique'),
('Infernal', 'Démons, Tieffelins', 'Infernal', 'exotique'),
('Primordial', 'Élémentaires', 'Primordial', 'exotique')
ON DUPLICATE KEY UPDATE
    typical_races = VALUES(typical_races),
    script = VALUES(script),
    type = VALUES(type);

-- Insertion des historiques de base D&D 5e
INSERT INTO backgrounds (name, description, skill_proficiencies, tool_proficiencies, languages, equipment, feature) VALUES
('Acolyte', 'Vous avez passé votre vie au service d\'un temple.', '["Insight", "Religion"]', '[]', 'Deux langues de votre choix', 'Un symbole sacré, un livre de prières, 5 bâtons d\'encens, des vêtements communs, une ceinture avec une bourse contenant 15 po', 'Shelter of the Faithful'),
('Artisan', 'Vous avez appris un métier et pouvez créer des objets utiles.', '["Insight", "Persuasion"]', '["Un type d\'outils d\'artisan", "Véhicules terrestres"]', 'Une langue de votre choix', 'Un ensemble d\'outils d\'artisan, une lettre de recommandation, des vêtements de voyage, une ceinture avec une bourse contenant 15 po', 'Guild Membership'),
('Charlatan', 'Vous avez toujours su comment obtenir ce que vous voulez.', '["Deception", "Sleight of Hand"]', '["Disguise kit", "Forgery kit"]', 'Une langue de votre choix', 'Un kit de déguisement fin, des outils de contrefaçon, 15 po', 'False Identity'),
('Criminel', 'Vous avez un passé criminel et connaissez les rouages du crime.', '["Deception", "Stealth"]', '["One type of gaming set", "Thieves\' tools"]', 'Une langue de votre choix', 'Un pied-de-biche, des vêtements sombres avec capuche, 15 po', 'Criminal Contact'),
('Ermite', 'Vous avez vécu en isolement pour étudier ou méditer.', '["Medicine", "Religion"]', '["Herbalism kit"]', 'Une langue de votre choix', 'Un étui à parchemins, un kit d\'herboristerie, 5 po', 'Discovery'),
('Folk Hero', 'Vous êtes un héros local reconnu par votre communauté.', '["Animal Handling", "Survival"]', '["One type of artisan\'s tools", "Vehicles (land)"]', 'Une langue de votre choix', 'Un ensemble d\'outils d\'artisan, une pelle, un pot de fer, des vêtements communs, une ceinture avec une bourse contenant 10 po', 'Rustic Hospitality'),
('Noble', 'Vous possédez une terre, des titres et des richesses.', '["History", "Persuasion"]', '["One type of gaming set"]', 'Une langue de votre choix', 'Un signet, un parchemin de généalogie, une bourse de soie, 25 po', 'Position of Privilege'),
('Sage', 'Vous avez passé des années à étudier et à rechercher la connaissance.', '["Arcana", "History"]', '[]', 'Deux langues de votre choix', 'Une bouteille d\'encre noire, une plume, un petit couteau, une lettre d\'un collègue mort posant une question à laquelle vous n\'avez pas encore trouvé de réponse, des vêtements communs, une bourse contenant 10 po', 'Researcher'),
('Soldat', 'Vous avez servi dans une armée et connaissez la guerre.', '["Athletics", "Intimidation"]', '["One type of gaming set", "Vehicles (land)"]', 'Une langue de votre choix', 'Un insigne de rang, un trophée pris d\'un ennemi tombé, un jeu de dés ou des cartes, des vêtements communs, une bourse contenant 10 po', 'Military Rank'),
('Vagabond', 'Vous avez grandi dans les rues et connaissez la survie urbaine.', '["Deception", "Stealth"]', '["Disguise kit", "Thieves\' tools"]', 'Une langue de votre choix', 'Un petit couteau, une carte de la ville où vous avez grandi, un animal familier, des vêtements communs, une bourse contenant 10 po', 'City Secrets')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    skill_proficiencies = VALUES(skill_proficiencies),
    tool_proficiencies = VALUES(tool_proficiencies),
    languages = VALUES(languages),
    equipment = VALUES(equipment),
    feature = VALUES(feature);

-- =====================================================
-- 10. RÉACTIVATION DES CONTRAINTES
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 11. VÉRIFICATION DE L'INSTALLATION
-- =====================================================

-- Affichage des tables créées
SELECT 'Tables créées avec succès:' as Status;
SHOW TABLES;

-- Comptage des enregistrements insérés
SELECT 'Données initiales insérées:' as Status;
SELECT 'Races' as Table_Name, COUNT(*) as Count FROM races
UNION ALL
SELECT 'Classes', COUNT(*) FROM classes
UNION ALL
SELECT 'Backgrounds', COUNT(*) FROM backgrounds
UNION ALL
SELECT 'Languages', COUNT(*) FROM languages
UNION ALL
SELECT 'Experience Levels', COUNT(*) FROM experience_levels;

-- =====================================================
-- FIN DU SCRIPT D'INITIALISATION
-- =====================================================

-- Message de confirmation
SELECT 'Base de données de production initialisée avec succès!' as Message,
       'Prêt pour l\'importation des données CSV' as Next_Step;
