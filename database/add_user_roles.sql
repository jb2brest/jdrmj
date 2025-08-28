-- Script pour ajouter le système de rôles utilisateur
-- À exécuter sur la base de données existante

USE dnd_characters;

-- Ajouter la colonne role à la table users
ALTER TABLE users ADD COLUMN role ENUM('player', 'dm') DEFAULT 'player' AFTER email;

-- Ajouter la colonne bio pour les informations personnelles
ALTER TABLE users ADD COLUMN bio TEXT AFTER role;

-- Ajouter la colonne avatar pour les images de profil
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) AFTER bio;

-- Ajouter la colonne experience_level pour le niveau d'expérience
ALTER TABLE users ADD COLUMN experience_level ENUM('debutant', 'intermediaire', 'expert') DEFAULT 'debutant' AFTER avatar;

-- Ajouter la colonne preferred_game_system pour le système de jeu préféré
ALTER TABLE users ADD COLUMN preferred_game_system VARCHAR(50) DEFAULT 'D&D 5e' AFTER experience_level;

-- Ajouter la colonne timezone pour la gestion des fuseaux horaires
ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'Europe/Paris' AFTER preferred_game_system;

-- Ajouter la colonne is_active pour désactiver les comptes
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER timezone;

-- TABLES DE CAMPAGNE
-- Campagnes créées par les MJ
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    game_system VARCHAR(50) DEFAULT 'D&D 5e',
    is_public BOOLEAN DEFAULT TRUE,
    invite_code VARCHAR(16) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Membres d'une campagne (joueurs + éventuellement co-MJ plus tard)
CREATE TABLE campaign_members (
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('player', 'dm') DEFAULT 'player',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (campaign_id, user_id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Candidatures aux campagnes publiques
CREATE TABLE campaign_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    player_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','approved','declined','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_application (campaign_id, player_id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lier les sessions de jeu à une campagne (optionnel)
ALTER TABLE game_sessions ADD COLUMN campaign_id INT NULL AFTER dm_id;
ALTER TABLE game_sessions ADD CONSTRAINT fk_game_sessions_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;

-- Créer une table pour les sessions de jeu (pour les MJ)
CREATE TABLE game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
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
    FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Créer une table pour les inscriptions aux sessions
CREATE TABLE session_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    player_id INT NOT NULL,
    character_id INT,
    status ENUM('pending', 'approved', 'declined', 'cancelled') DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
    UNIQUE KEY unique_registration (session_id, player_id)
);

-- Créer une table pour les messages entre utilisateurs
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(100),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Créer une table pour les notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('message', 'session_invite', 'session_update', 'character_comment', 'system') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_experience_level ON users(experience_level);
CREATE INDEX idx_game_sessions_dm_id ON game_sessions(dm_id);
CREATE INDEX idx_game_sessions_status ON game_sessions(status);
CREATE INDEX idx_game_sessions_campaign ON game_sessions(campaign_id);
CREATE INDEX idx_campaigns_dm_id ON campaigns(dm_id);
CREATE INDEX idx_campaign_members_user ON campaign_members(user_id);
CREATE INDEX idx_campaign_applications_campaign ON campaign_applications(campaign_id);
CREATE INDEX idx_campaign_applications_player ON campaign_applications(player_id);
CREATE INDEX idx_session_registrations_session_id ON session_registrations(session_id);
CREATE INDEX idx_session_registrations_player_id ON session_registrations(player_id);
CREATE INDEX idx_messages_recipient_id ON messages(recipient_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);

-- Exemples (optionnels)
/*
INSERT INTO campaigns (dm_id, title, description, invite_code)
VALUES (1, 'La Tombe des Rois', 'Campagne épique dans des ruines anciennes', 'ABCD1234EFGH5678');
*/

