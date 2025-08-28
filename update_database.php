<?php
require_once 'config/database.php';

echo "<h1>Mise à jour de la base de données</h1>";

try {
    // Vérifier si la colonne role existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'role' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('player', 'dm') DEFAULT 'player' AFTER email");
        echo "<p style='color: green;'>✓ Colonne 'role' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'role' existe déjà</p>";
    }

    // Vérifier si la colonne bio existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'bio'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'bio' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT AFTER role");
        echo "<p style='color: green;'>✓ Colonne 'bio' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'bio' existe déjà</p>";
    }

    // Vérifier si la colonne avatar existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'avatar' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) AFTER bio");
        echo "<p style='color: green;'>✓ Colonne 'avatar' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'avatar' existe déjà</p>";
    }

    // Vérifier si la colonne experience_level existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'experience_level'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'experience_level' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN experience_level ENUM('debutant', 'intermediaire', 'expert') DEFAULT 'debutant' AFTER avatar");
        echo "<p style='color: green;'>✓ Colonne 'experience_level' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'experience_level' existe déjà</p>";
    }

    // Vérifier si la colonne preferred_game_system existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'preferred_game_system'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'preferred_game_system' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN preferred_game_system VARCHAR(50) DEFAULT 'D&D 5e' AFTER experience_level");
        echo "<p style='color: green;'>✓ Colonne 'preferred_game_system' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'preferred_game_system' existe déjà</p>";
    }

    // Vérifier si la colonne timezone existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'timezone'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'timezone' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'Europe/Paris' AFTER preferred_game_system");
        echo "<p style='color: green;'>✓ Colonne 'timezone' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'timezone' existe déjà</p>";
    }

    // Vérifier si la colonne is_active existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne 'is_active' à la table users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER timezone");
        echo "<p style='color: green;'>✓ Colonne 'is_active' ajoutée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Colonne 'is_active' existe déjà</p>";
    }

    // Créer les tables de campagne si absentes
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
                invite_code VARCHAR(16) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "<p style='color: green;'>✓ Table 'campaigns' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'campaigns' existe déjà</p>";
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_members'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'campaign_members'...</p>";
        $pdo->exec("
            CREATE TABLE campaign_members (
                campaign_id INT NOT NULL,
                user_id INT NOT NULL,
                role ENUM('player', 'dm') DEFAULT 'player',
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (campaign_id, user_id),
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "<p style='color: green;'>✓ Table 'campaign_members' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'campaign_members' existe déjà</p>";
    }

    // Candidatures aux campagnes
    $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_applications'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'campaign_applications'...</p>";
        $pdo->exec("
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
            )
        ");
        echo "<p style='color: green;'>✓ Table 'campaign_applications' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'campaign_applications' existe déjà</p>";
    }

    // Vérifier si la table game_sessions existe déjà
    $stmt = $pdo->query("SHOW TABLES LIKE 'game_sessions'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'game_sessions'...</p>";
        $pdo->exec("
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
                campaign_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (dm_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
            )
        ");
        echo "<p style='color: green;'>✓ Table 'game_sessions' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'game_sessions' existe déjà</p>";
        // Ajouter la colonne campaign_id si manquante
        $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'campaign_id'");
        if ($stmt->rowCount() == 0) {
            echo "<p>Ajout de la colonne 'campaign_id' à la table game_sessions...</p>";
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN campaign_id INT NULL AFTER dm_id");
            $pdo->exec("ALTER TABLE game_sessions ADD CONSTRAINT fk_game_sessions_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL");
            echo "<p style='color: green;'>✓ Colonne 'campaign_id' ajoutée</p>";
        } else {
            echo "<p style='color: blue;'>ℹ Colonne 'campaign_id' existe déjà</p>";
        }
    }

    // Vérifier si la table session_registrations existe déjà
    $stmt = $pdo->query("SHOW TABLES LIKE 'session_registrations'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'session_registrations'...</p>";
        $pdo->exec("
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
            )
        ");
        echo "<p style='color: green;'>✓ Table 'session_registrations' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'session_registrations' existe déjà</p>";
    }

    // Vérifier si la table messages existe déjà
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'messages'...</p>";
        $pdo->exec("
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
            )
        ");
        echo "<p style='color: green;'>✓ Table 'messages' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'messages' existe déjà</p>";
    }

    // Vérifier si la table notifications existe déjà
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Création de la table 'notifications'...</p>";
        $pdo->exec("
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
            )
        ");
        echo "<p style='color: green;'>✓ Table 'notifications' créée</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Table 'notifications' existe déjà</p>";
    }

    echo "<h2 style='color: green;'>✓ Mise à jour terminée avec succès !</h2>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

