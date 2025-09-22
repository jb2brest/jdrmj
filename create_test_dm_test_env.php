<?php
/**
 * Script pour créer un utilisateur DM de test dans l'environnement de test
 * Usage: php create_test_dm_test_env.php
 */

// Forcer l'environnement de test
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

require_once '/var/www/html/jdrmj_test/config/database.php';

// Données de l'utilisateur DM de test
$username = 'test_user';
$email = 'test@example.com';
$password = 'TestPassword123!';
$is_dm = 1; // 1 = DM, 0 = joueur normal

try {
    // Vérifier si la colonne is_dm existe déjà
    $check_query = "SHOW COLUMNS FROM users LIKE 'is_dm'";
    $check_stmt = $pdo->query($check_query);
    
    if ($check_stmt->rowCount() == 0) {
        // Ajouter la colonne is_dm
        $alter_query = "ALTER TABLE users ADD COLUMN is_dm TINYINT(1) DEFAULT 0";
        $pdo->exec($alter_query);
        echo "✅ Colonne 'is_dm' ajoutée à la table 'users' (environnement test)\n";
    } else {
        echo "ℹ️  La colonne 'is_dm' existe déjà (environnement test)\n";
    }
    
    // Vérifier si la colonne role existe déjà
    $check_role_query = "SHOW COLUMNS FROM users LIKE 'role'";
    $check_role_stmt = $pdo->query($check_role_query);
    
    if ($check_role_stmt->rowCount() == 0) {
        // Ajouter la colonne role
        $alter_role_query = "ALTER TABLE users ADD COLUMN role ENUM('player', 'dm', 'admin') DEFAULT 'player'";
        $pdo->exec($alter_role_query);
        echo "✅ Colonne 'role' ajoutée à la table 'users' (environnement test)\n";
    } else {
        echo "ℹ️  La colonne 'role' existe déjà (environnement test)\n";
    }
    
    // Vérifier si l'utilisateur existe déjà
    $check_user_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_user_stmt = $pdo->prepare($check_user_query);
    $check_user_stmt->execute([$username, $email]);
    
    if ($check_user_stmt->rowCount() > 0) {
        // L'utilisateur existe, mettre à jour ses droits DM
        $update_query = "UPDATE users SET is_dm = ?, role = 'dm' WHERE username = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$is_dm, $username]);
        
        echo "✅ Utilisateur '$username' mis à jour avec les droits de DM (environnement test)\n";
    } else {
        // Créer un nouvel utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password_hash, is_dm, role, created_at) VALUES (?, ?, ?, ?, 'dm', NOW())";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([$username, $email, $hashed_password, $is_dm]);
        
        echo "✅ Utilisateur DM '$username' créé avec succès (environnement test)\n";
    }
    
    // Vérifier les droits
    $verify_query = "SELECT username, email, is_dm, role FROM users WHERE username = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$username]);
    $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "📋 Informations de l'utilisateur (environnement test):\n";
        echo "   - Nom d'utilisateur: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n";
        echo "   - Est DM: " . ($user['is_dm'] ? 'Oui' : 'Non') . "\n";
        echo "   - Rôle: " . $user['role'] . "\n";
        echo "   - Mot de passe: $password\n";
    }
    
    // Vérifier la structure de la table
    $structure_query = "DESCRIBE users";
    $structure_stmt = $pdo->query($structure_query);
    $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Structure de la table 'users' (environnement test):\n";
    foreach ($columns as $column) {
        echo "   - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 L'utilisateur DM est maintenant prêt pour les tests (environnement test)!\n";
echo "💡 Vous pouvez maintenant exécuter les tests avec l'URL: http://localhost/jdrmj_test/\n";
?>
