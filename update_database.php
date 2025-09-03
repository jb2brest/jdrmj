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
            challenge_rating VARCHAR(20),
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
    if ($column && strpos($column['Type'], 'decimal') !== false) {
        echo "<p>Modification de la colonne challenge_rating pour supporter les CR fractionnaires...</p>";
        $pdo->exec("ALTER TABLE dnd_monsters MODIFY COLUMN challenge_rating VARCHAR(20)");
        echo "<p style='color: green;'>✓ Colonne challenge_rating modifiée pour supporter les CR fractionnaires</p>";
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
    
    // Vérifier la colonne quantity dans scene_npcs
    $col = $pdo->query("SHOW COLUMNS FROM scene_npcs LIKE 'quantity'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'quantity' à la table scene_npcs...</p>";
        $pdo->exec("ALTER TABLE scene_npcs ADD COLUMN quantity INT DEFAULT 1 AFTER monster_id");
        echo "<p style='color: green;'>✓ Colonne 'quantity' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'quantity' existe déjà</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Table 'scene_npcs' n'existe pas</p>";
}

// ===== NOUVELLES TABLES D'ÉQUIPEMENT =====

// Vérifier la table character_equipment
$stmt = $pdo->query("SHOW TABLES LIKE 'character_equipment'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'character_equipment'...</p>";
    $pdo->exec("
        CREATE TABLE character_equipment (
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
            INDEX idx_magical_item_id (magical_item_id)
        )
    ");
    echo "<p style='color: green;'>✓ Table 'character_equipment' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'character_equipment' existe déjà</p>";
}

// Vérifier la table npc_equipment
$stmt = $pdo->query("SHOW TABLES LIKE 'npc_equipment'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'npc_equipment'...</p>";
    $pdo->exec("
        CREATE TABLE npc_equipment (
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
        )
    ");
    echo "<p style='color: green;'>✓ Table 'npc_equipment' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'npc_equipment' existe déjà</p>";
}

// Vérifier la table monster_equipment
$stmt = $pdo->query("SHOW TABLES LIKE 'monster_equipment'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'monster_equipment'...</p>";
    $pdo->exec("
        CREATE TABLE monster_equipment (
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
        )
    ");
    echo "<p style='color: green;'>✓ Table 'monster_equipment' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'monster_equipment' existe déjà</p>";
}

// ===== NOUVELLES TABLES DE DONNÉES =====

// Vérifier la table poisons
$stmt = $pdo->query("SHOW TABLES LIKE 'poisons'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'poisons'...</p>";
    $pdo->exec("
        CREATE TABLE poisons (
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
        )
    ");
    echo "<p style='color: green;'>✓ Table 'poisons' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'poisons' existe déjà</p>";
}

// Vérifier la table magical_items
$stmt = $pdo->query("SHOW TABLES LIKE 'magical_items'");
if ($stmt->rowCount() == 0) {
    echo "<p>Création de la table 'magical_items'...</p>";
    $pdo->exec("
        CREATE TABLE magical_items (
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
        )
    ");
    echo "<p style='color: green;'>✓ Table 'magical_items' créée</p>";
} else {
    echo "<p style='color: blue;'>ℹ Table 'magical_items' existe déjà</p>";
}

// Mettre à jour la table dnd_monsters existante si nécessaire
$stmt = $pdo->query("SHOW TABLES LIKE 'dnd_monsters'");
if ($stmt->rowCount() > 0) {
    // Vérifier et ajouter la colonne csv_id si absente
    $col = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'csv_id'");
    if ($col->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'csv_id' à la table dnd_monsters...</p>";
        $pdo->exec("ALTER TABLE dnd_monsters ADD COLUMN csv_id VARCHAR(50) UNIQUE AFTER id");
        $pdo->exec("ALTER TABLE dnd_monsters ADD INDEX idx_csv_id (csv_id)");
        echo "<p style='color: green;'>✓ Colonne 'csv_id' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'csv_id' existe déjà</p>";
    }
    
    // Vérifier et ajouter l'index fulltext si absent
    $indexes = $pdo->query("SHOW INDEX FROM dnd_monsters WHERE Key_name = 'idx_search'");
    if ($indexes->rowCount() == 0) {
        echo "<p>Ajout de l'index fulltext à la table dnd_monsters...</p>";
        $pdo->exec("ALTER TABLE dnd_monsters ADD FULLTEXT idx_search (name, type)");
        echo "<p style='color: green;'>✓ Index fulltext ajouté</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Index fulltext existe déjà</p>";
    }
}

// ===== IMPORT AUTOMATIQUE DES DONNÉES CSV =====

echo "<h3>Import des données CSV...</h3>";

// Import des poisons
if (file_exists('aidednddata/poisons.csv')) {
    echo "<p>Import des poisons depuis poisons.csv...</p>";
    try {
        importPoisons($pdo);
        echo "<p style='color: green;'>✓ Poisons importés avec succès</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erreur lors de l'import des poisons: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Fichier poisons.csv non trouvé</p>";
}

// Import des objets magiques
if (file_exists('aidednddata/objet_magiques.csv')) {
    echo "<p>Import des objets magiques depuis objet_magiques.csv...</p>";
    try {
        importMagicalItems($pdo);
        echo "<p style='color: green;'>✓ Objets magiques importés avec succès</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erreur lors de l'import des objets magiques: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Fichier objet_magiques.csv non trouvé</p>";
}

// Import des monstres (si le fichier existe)
if (file_exists('aidednddata/monstre.csv')) {
    echo "<p>Import des monstres depuis monstre.csv...</p>";
    try {
        importMonsters($pdo);
        echo "<p style='color: green;'>✓ Monstres importés avec succès</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Erreur lors de l'import des monstres: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Fichier monstre.csv non trouvé</p>";
}

echo "<h2>Mise à jour terminée !</h2>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";

// ===== FONCTIONS D'IMPORT CSV =====

function importPoisons($pdo) {
    $csvFile = 'aidednddata/poisons.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des poisons introuvable: $csvFile");
    }
    
    // Vider la table existante
    $pdo->exec("TRUNCATE TABLE poisons");
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        throw new Exception("Impossible d'ouvrir le fichier des poisons");
    }
    
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    $stmt = $pdo->prepare("INSERT INTO poisons (csv_id, nom, cle, description, type, source) VALUES (?, ?, ?, ?, ?, ?)");
    $count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 6) {
            $stmt->execute([
                $data[0], // csv_id
                $data[1], // nom
                $data[2], // cle
                $data[3], // description
                $data[4], // type
                $data[5]  // source
            ]);
            $count++;
        }
    }
    
    fclose($handle);
    echo "<p style='color: green;'>✓ $count poisons importés</p>";
}

function importMagicalItems($pdo) {
    $csvFile = 'aidednddata/objet_magiques.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des objets magiques introuvable: $csvFile");
    }
    
    // Vider la table existante
    $pdo->exec("TRUNCATE TABLE magical_items");
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        throw new Exception("Impossible d'ouvrir le fichier des objets magiques");
    }
    
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    $stmt = $pdo->prepare("INSERT INTO magical_items (csv_id, nom, cle, description, type, source) VALUES (?, ?, ?, ?, ?, ?)");
    $count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 6) {
            $stmt->execute([
                $data[0], // csv_id
                $data[1], // nom
                $data[2], // cle
                $data[3], // description
                $data[4], // type
                $data[5]  // source
            ]);
            $count++;
        }
    }
    
    fclose($handle);
    echo "<p style='color: green;'>✓ $count objets magiques importés</p>";
}

function importMonsters($pdo) {
    $csvFile = 'aidednddata/monstre.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des monstres introuvable: $csvFile");
    }
    
    // Utiliser la fonction utilitaire pour gérer les contraintes de clé étrangère
    executeWithForeignKeyHandling($pdo, 'dnd_monsters', function($pdo) use ($csvFile) {
        // Vider la table existante
        $pdo->exec("DELETE FROM dnd_monsters");
        echo "<p style='color: blue;'>ℹ Table dnd_monsters vidée</p>";
        
        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            throw new Exception("Impossible d'ouvrir le fichier des monstres");
        }
        
        // Ignorer l'en-tête
        fgetcsv($handle);
        
        $stmt = $pdo->prepare("INSERT INTO dnd_monsters (csv_id, name, type, size, challenge_rating, hit_points, armor_class) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $count = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 21) { // Le CSV a 21 colonnes
                // Extraire et nettoyer les valeurs
                $csv_id = $data[0];
                $name = $data[1];
                $type = $data[2];
                $size = $data[3];
                
                // Challenge Rating (dernière colonne FP)
                $challenge_rating = cleanChallengeRating($data[20]); // FP
                
                // Points de vie (colonne PV)
                $hit_points = extractHitPoints($data[6]); // PV
                
                // Classe d'armure (colonne CA)
                $armor_class = extractArmorClass($data[5]); // CA
                
                // Nettoyer les valeurs
                $name = trim($name);
                $type = trim($type);
                $size = trim($size);
                
                // Insérer seulement si les données sont valides
                if (!empty($name) && !empty($type)) {
                    $stmt->execute([
                        $csv_id,
                        $name,
                        $type,
                        $size,
                        $challenge_rating,
                        $hit_points,
                        $armor_class
                    ]);
                    $count++;
                }
            }
        }
        
        fclose($handle);
        echo "<p style='color: green;'>✓ $count monstres importés</p>";
    });
}

// ===== FONCTIONS UTILITAIRES DE NETTOYAGE DES DONNÉES =====

/**
 * Nettoie et extrait le Challenge Rating depuis la colonne FP
 */
function cleanChallengeRating($fp_value) {
    $fp_value = trim($fp_value);
    
    // Extraire le CR numérique (ex: "1/4 (50 PX)" -> "1/4")
    if (preg_match('/^(\d+\/\d+|\d+)/', $fp_value, $matches)) {
        return $matches[1];
    }
    
    // Si pas de match, retourner la valeur brute nettoyée
    return $fp_value;
}

/**
 * Extrait les points de vie depuis la colonne PV
 */
function extractHitPoints($pv_value) {
    $pv_value = trim($pv_value);
    
    // Extraire le nombre de points de vie (ex: "13 (3d8)" -> 13)
    if (preg_match('/^(\d+)/', $pv_value, $matches)) {
        return (int)$matches[1];
    }
    
    // Valeur par défaut si pas de match
    return 0;
}

/**
 * Extrait la classe d'armure depuis la colonne CA
 */
function extractArmorClass($ca_value) {
    $ca_value = trim($ca_value);
    
    // Extraire la CA numérique (ex: "12 (armure naturelle)" -> 12)
    if (preg_match('/^(\d+)/', $ca_value, $matches)) {
        return (int)$matches[1];
    }
    
    // Valeur par défaut si pas de match
    return 10;
}

// ===== FONCTION UTILITAIRE POUR GÉRER LES CONTRAINTES =====

/**
 * Fonction utilitaire pour gérer les contraintes de clé étrangère
 * Supprime temporairement les contraintes, exécute une fonction, puis les recrée
 */
function executeWithForeignKeyHandling($pdo, $referencedTable, $callback) {
    // Vérifier s'il y a des contraintes de clé étrangère
    $foreignKeys = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = '$referencedTable' 
        AND REFERENCED_TABLE_SCHEMA = DATABASE()
    ")->fetchAll();
    
    $droppedConstraints = [];
    
    // Supprimer temporairement les contraintes de clé étrangère
    foreach ($foreignKeys as $fk) {
        try {
            $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
            $droppedConstraints[] = $fk;
            echo "<p style='color: blue;'>ℹ Contrainte {$fk['CONSTRAINT_NAME']} supprimée temporairement</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Impossible de supprimer la contrainte {$fk['CONSTRAINT_NAME']}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    try {
        // Exécuter la fonction de callback
        $callback($pdo);
        
        // Recréer les contraintes de clé étrangère
        foreach ($droppedConstraints as $fk) {
            try {
                // Déterminer le type de contrainte selon la table
                $onDelete = 'ON DELETE CASCADE';
                if ($fk['TABLE_NAME'] === 'scene_npcs') {
                    $onDelete = 'ON DELETE SET NULL';
                }
                
                $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` FOREIGN KEY ({$fk['COLUMN_NAME']}) REFERENCES $referencedTable(id) $onDelete");
                echo "<p style='color: green;'>✓ Contrainte {$fk['CONSTRAINT_NAME']} recréée</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Impossible de recréer la contrainte {$fk['CONSTRAINT_NAME']}: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
    } catch (Exception $e) {
        // En cas d'erreur, essayer de recréer les contraintes
        echo "<p style='color: red;'>✗ Erreur lors de l'exécution: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        foreach ($droppedConstraints as $fk) {
            try {
                $onDelete = 'ON DELETE CASCADE';
                if ($fk['TABLE_NAME'] === 'scene_npcs') {
                    $onDelete = 'ON DELETE SET NULL';
                }
                
                $pdo->exec("ALTER TABLE `{$fk['TABLE_NAME']}` ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` FOREIGN KEY ({$fk['COLUMN_NAME']}) REFERENCES $referencedTable(id) $onDelete");
                echo "<p style='color: green;'>✓ Contrainte {$fk['CONSTRAINT_NAME']} recréée après erreur</p>";
            } catch (Exception $e2) {
                echo "<p style='color: red;'>✗ Impossible de recréer la contrainte {$fk['CONSTRAINT_NAME']}: " . htmlspecialchars($e2->getMessage()) . "</p>";
            }
        }
        
        throw $e; // Relancer l'erreur
    }
}
?>

