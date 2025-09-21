-- Création de la base de données
CREATE DATABASE IF NOT EXISTS dnd_characters CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dnd_characters;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des races D&D
CREATE TABLE races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    ability_score_bonus VARCHAR(100),
    traits TEXT
);

-- Table des classes D&D
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    hit_die INT NOT NULL,
    primary_ability VARCHAR(50),
    saving_throw_proficiencies VARCHAR(100)
);

-- Table des personnages
CREATE TABLE characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    race_id INT NOT NULL,
    class_id INT NOT NULL,
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
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Table des sorts
CREATE TABLE spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level INT DEFAULT 0,
    school VARCHAR(50),
    casting_time VARCHAR(50),
    range_sp VARCHAR(50),
    components TEXT,
    duration VARCHAR(50),
    description TEXT,
    classes TEXT
);

-- Table de liaison personnage-sorts
CREATE TABLE character_spells (
    character_id INT NOT NULL,
    spell_id INT NOT NULL,
    prepared BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (character_id, spell_id),
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (spell_id) REFERENCES spells(id) ON DELETE CASCADE
);

-- Insertion des races de base
INSERT INTO races (name, description, ability_score_bonus, traits) VALUES
('Humain', 'Les humains sont les plus adaptables et ambitieux parmi les races communes.', '+1 à toutes les caractéristiques', 'Versatilité humaine'),
('Elfe', 'Les elfes sont un peuple magique de grâce surnaturelle.', '+2 Dextérité, +1 Intelligence', 'Vision dans le noir, Fey Ancestry'),
('Nain', 'Les nains sont robustes et résistants, parfaits pour la guerre.', '+2 Constitution, +2 Force', 'Vision dans le noir, Résistance aux poisons'),
('Halfelin', 'Les halfelins sont petits mais courageux.', '+2 Dextérité, +1 Charisme', 'Chanceux, Brave, Agilité halfeline'),
('Demi-elfe', 'Les demi-elfes combinent ce qu\'il y a de mieux chez les humains et les elfes.', '+2 Charisme, +1 à deux autres caractéristiques', 'Vision dans le noir, Fey Ancestry'),
('Demi-orc', 'Les demi-orcs combinent la force physique des orcs avec la persévérance des humains.', '+2 Force, +1 Constitution', 'Vision dans le noir, Menace'),
('Dragonborn', 'Les dragonborns descendent des dragons et possèdent une puissance draconique.', '+2 Force, +1 Charisme', 'Souffle draconique, Résistance aux dégâts'),
('Tieffelin', 'Les tieffelins descendent des humains et des créatures infernales.', '+2 Charisme, +1 Intelligence', 'Vision dans le noir, Résistance aux dégâts de feu');

-- Insertion des classes de base
INSERT INTO classes (name, description, hit_die, primary_ability, saving_throw_proficiencies) VALUES
('Guerrier', 'Maître du combat martial, expert avec une variété d\'armes et d\'armures.', 10, 'Force ou Dextérité', 'Force, Constitution'),
('Magicien', 'Érudit magique capable de manipuler les structures de la réalité.', 6, 'Intelligence', 'Intelligence, Sagesse'),
('Clerc', 'Intermédiaire entre le monde mortel et les plans divins.', 8, 'Sagesse', 'Sagesse, Charisme'),
('Rôdeur', 'Guerrier qui utilise la puissance primordiale de la nature.', 10, 'Dextérité et Sagesse', 'Force, Dextérité'),
('Roublard', 'Scélérat qui se fie à la furtivité et à la précision.', 8, 'Dextérité', 'Dextérité, Intelligence'),
('Paladin', 'Guerrier saint lié par un serment sacré.', 10, 'Force et Charisme', 'Sagesse, Charisme'),
('Ensorceleur', 'Lanceur de sorts qui tire sa magie de la force de sa lignée.', 6, 'Charisme', 'Constitution, Charisme'),
('Barde', 'Artiste inspirant dont la magie éveille l\'esprit et le cœur.', 8, 'Charisme', 'Dextérité, Charisme'),
('Druide', 'Gardien de la nature, doté de la capacité de prendre forme animale.', 8, 'Sagesse', 'Intelligence, Sagesse'),
('Moine', 'Maître des arts martiaux, utilisant la puissance du corps en harmonie avec l\'esprit.', 8, 'Dextérité et Sagesse', 'Force, Dextérité'),
('Rôdeur', 'Guerrier qui utilise la puissance primordiale de la nature.', 10, 'Dextérité et Sagesse', 'Force, Dextérité'),
('Artificier', 'Inventeur qui utilise la magie pour créer des objets magiques.', 8, 'Intelligence', 'Constitution, Intelligence');






























