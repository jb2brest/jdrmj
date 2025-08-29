<?php
require_once 'config/database.php';

echo "<h1>Mise à jour de la base de données</h1>";

// Vérifier la table users et ajouter les rôles si absents
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'role' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('player', 'dm') DEFAULT 'player' AFTER email");
        echo "<p style='color: green;'>✓ Colonne 'role' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'role' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'users' n'existe pas</p>";
}

// Vérifier la table users et ajouter is_dm si absente
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_dm'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'is_dm' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_dm BOOLEAN DEFAULT FALSE AFTER role");
        echo "<p style='color: green;'>✓ Colonne 'is_dm' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'is_dm' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'users' n'existe pas</p>";
}

// Vérifier la table users et ajouter profile_photo si absente
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'profile_photo' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER is_dm");
        echo "<p style='color: green;'>✓ Colonne 'profile_photo' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'profile_photo' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'users' n'existe pas</p>";
}

// Vérifier la table characters et ajouter profile_photo si absente
$stmt = $pdo->query("SHOW TABLES LIKE 'characters'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM characters LIKE 'profile_photo'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'profile_photo' à la table characters...</p>";
        $pdo->exec("ALTER TABLE characters ADD COLUMN profile_photo VARCHAR(255) NULL AFTER flaws");
        echo "<p style='color: green;'>✓ Colonne 'profile_photo' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'profile_photo' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'characters' n'existe pas</p>";
}

// Vérifier la table scene_npcs et ajouter profile_photo si absente
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_npcs'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM scene_npcs LIKE 'profile_photo'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'profile_photo' à la table scene_npcs...</p>";
        $pdo->exec("ALTER TABLE scene_npcs ADD COLUMN profile_photo VARCHAR(255) NULL AFTER npc_character_id");
        echo "<p style='color: green;'>✓ Colonne 'profile_photo' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'profile_photo' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'scene_npcs' n'existe pas</p>";
}

// Vérifier la table campaigns
$stmt = $pdo->query("SHOW TABLES LIKE 'campaigns'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'campaigns'...</p>";
    $pdo->exec("
        CREATE TABLE campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dm_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            game_system VARCHAR(50) DEFAULT 'D&D 5e',
            is_public BOOLEAN DEFAULT TRUE,
            invite_code VARCHAR(20) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ Table 'campaigns' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'campaigns' existe déjà</p>";
}

// Vérifier la table campaign_members
$stmt = $pdo->query("SHOW TABLES LIKE 'campaign_members'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'campaign_members'...</p>";
    $pdo->exec("
        CREATE TABLE campaign_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('player', 'dm') DEFAULT 'player',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_campaign_user (campaign_id, user_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'campaign_members' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'campaign_members' existe déjà</p>";
}

// Vérifier la table campaign_applications
$stmt = $pdo->query("SHOW TABLES LIKE 'campaign_applications'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'campaign_applications'...</p>";
    $pdo->exec("
        CREATE TABLE campaign_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            player_id INT NOT NULL,
            character_id INT NULL,
            message TEXT,
            status ENUM('pending', 'approved', 'declined', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
            UNIQUE KEY unique_campaign_player (campaign_id, player_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'campaign_applications' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'campaign_applications' existe déjà</p>";
}

// Vérifier la table game_sessions
$stmt = $pdo->query("SHOW TABLES LIKE 'game_sessions'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'game_sessions'...</p>";
    $pdo->exec("
        CREATE TABLE game_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dm_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            session_date DATETIME,
            location VARCHAR(255),
            is_online BOOLEAN DEFAULT FALSE,
            meeting_link VARCHAR(255),
            max_players INT DEFAULT 6,
            status ENUM('planning', 'recruiting', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
            campaign_id INT NULL,
            start_context TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
        )
    ");
    echo "<p style='color: green;'>✓ Table 'game_sessions' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'game_sessions' existe déjà</p>";
}

// Vérifier la table session_registrations
$stmt = $pdo->query("SHOW TABLES LIKE 'session_registrations'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'session_registrations'...</p>";
    $pdo->exec("
        CREATE TABLE session_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            player_id INT NOT NULL,
            character_id INT NULL,
            status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
            UNIQUE KEY unique_session_player (session_id, player_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'session_registrations' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'session_registrations' existe déjà</p>";
}

// Vérifier la table scenes
$stmt = $pdo->query("SHOW TABLES LIKE 'scenes'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'scenes'...</p>";
    $pdo->exec("
        CREATE TABLE scenes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            map_url VARCHAR(255),
            notes TEXT,
            position INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ Table 'scenes' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'scenes' existe déjà</p>";
}

// Vérifier la table scene_players
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_players'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'scene_players'...</p>";
    $pdo->exec("
        CREATE TABLE scene_players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scene_id INT NOT NULL,
            player_id INT NOT NULL,
            character_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL,
            UNIQUE KEY unique_scene_player (scene_id, player_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'scene_players' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'scene_players' existe déjà</p>";
}

// Vérifier la table scene_npcs
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_npcs'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'scene_npcs'...</p>";
    $pdo->exec("
        CREATE TABLE scene_npcs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scene_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            npc_character_id INT NULL,
            profile_photo VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
            FOREIGN KEY (npc_character_id) REFERENCES characters(id) ON DELETE SET NULL
        )
    ");
    echo "<p style='color: green;'>✓ Table 'scene_npcs' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'scene_npcs' existe déjà</p>";
}

// Vérifier la table scene_npcs et ajouter npc_character_id si absente
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_npcs'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM scene_npcs LIKE 'npc_character_id'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'npc_character_id' à la table scene_npcs...</p>";
        $pdo->exec("ALTER TABLE scene_npcs ADD COLUMN npc_character_id INT NULL AFTER description");
        $pdo->exec("ALTER TABLE scene_npcs ADD CONSTRAINT fk_scene_npcs_character FOREIGN KEY (npc_character_id) REFERENCES characters(id) ON DELETE SET NULL");
        echo "<p style='color: green;'>✓ Colonne 'npc_character_id' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'npc_character_id' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'scene_npcs' n'existe pas</p>";
}

// Vérifier la table scene_tokens (positions des pions)
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_tokens'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'scene_tokens'...</p>";
    $pdo->exec("
        CREATE TABLE scene_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scene_id INT NOT NULL,
            token_type ENUM('player', 'npc') NOT NULL,
            entity_id INT NOT NULL,
            x_position DECIMAL(10,2) NOT NULL,
            y_position DECIMAL(10,2) NOT NULL,
            color VARCHAR(7) DEFAULT '#007bff',
            label VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_scene_entity (scene_id, token_type, entity_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'scene_tokens' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'scene_tokens' existe déjà</p>";
}

// Vérifier la table notifications
$stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'notifications'...</p>";
    $pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ Table 'notifications' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'notifications' existe déjà</p>";
}

// Vérifier la table dnd_monsters
$stmt = $pdo->query("SHOW TABLES LIKE 'dnd_monsters'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'dnd_monsters'...</p>";
    $pdo->exec("
        CREATE TABLE dnd_monsters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100),
            size VARCHAR(20),
            alignment VARCHAR(50),
            challenge_rating DECIMAL(4,2),
            hit_points INT,
            armor_class INT,
            speed VARCHAR(100),
            proficiency_bonus INT,
            description TEXT,
            actions TEXT,
            special_abilities TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_type (type),
            INDEX idx_cr (challenge_rating)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'dnd_monsters' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'dnd_monsters' existe déjà</p>";
    
    // Vérifier et modifier la colonne challenge_rating si nécessaire
    $stmt = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'challenge_rating'");
    $column = $stmt->fetch();
    if ($column && strpos($column['Type'], 'decimal(3,2)') !== false) {
        echo "<p>Modification de la colonne challenge_rating pour supporter les CR élevés...</p>";
        $pdo->exec("ALTER TABLE dnd_monsters MODIFY COLUMN challenge_rating DECIMAL(4,2)");
        echo "<p style='color: green;'>✓ Colonne challenge_rating modifiée</p>";
    }
}

// Vérifier la table user_monster_collection
$stmt = $pdo->query("SHOW TABLES LIKE 'user_monster_collection'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'user_monster_collection'...</p>";
    $pdo->exec("
        CREATE TABLE user_monster_collection (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            monster_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (monster_id) REFERENCES dnd_monsters(id) ON DELETE CASCADE,
            UNIQUE KEY unique_collection (user_id, monster_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'user_monster_collection' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'user_monster_collection' existe déjà</p>";
}

// Vérifier la colonne monster_id dans scene_npcs
$stmt = $pdo->query("SHOW TABLES LIKE 'scene_npcs'");
if ($stmt->rowCount() > 0) {
    $col = $pdo->query("SHOW COLUMNS FROM scene_npcs LIKE 'monster_id'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'monster_id' à la table scene_npcs...</p>";
        $pdo->exec("ALTER TABLE scene_npcs ADD COLUMN monster_id INT NULL AFTER npc_character_id");
        $pdo->exec("ALTER TABLE scene_npcs ADD CONSTRAINT fk_scene_npcs_monster FOREIGN KEY (monster_id) REFERENCES dnd_monsters(id) ON DELETE SET NULL");
        echo "<p style='color: green;'>✓ Colonne 'monster_id' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'monster_id' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'scene_npcs' n'existe pas</p>";
}

echo "<h2>Mise à jour terminée !</h2>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>

