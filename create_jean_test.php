<?php
/**
 * Script pour créer l'utilisateur Jean dans l'environnement de test
 * Usage: php create_jean_test.php
 */

// Forcer l'environnement de test
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

require_once '/var/www/html/jdrmj_test/config/database.php';

// Données de l'utilisateur Jean
$username = 'Jean';
$email = 'jean.m.bernard@gmail.com';
$password = 'TestPassword123!'; // Mot de passe par défaut pour les tests
$role = 'dm';
$is_dm = 1;

try {
    // Vérifier si l'utilisateur existe déjà
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        // L'utilisateur existe, mettre à jour ses droits
        $update_query = "UPDATE users SET role = ?, is_dm = ?, password_hash = ? WHERE username = ?";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$role, $is_dm, $hashed_password, $username]);
        
        echo "✅ Utilisateur '$username' mis à jour dans l'environnement de test\n";
    } else {
        // Créer un nouvel utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password_hash, role, is_dm, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([$username, $email, $hashed_password, $role, $is_dm]);
        
        echo "✅ Utilisateur '$username' créé dans l'environnement de test\n";
    }
    
    // Vérifier les droits
    $verify_query = "SELECT id, username, email, role, is_dm FROM users WHERE username = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$username]);
    $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "📋 Informations de l'utilisateur (test):\n";
        echo "   - Nom d'utilisateur: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n";
        echo "   - Rôle: " . $user['role'] . "\n";
        echo "   - Est DM: " . ($user['is_dm'] ? 'Oui' : 'Non') . "\n";
        echo "   - Mot de passe: $password\n";
    }
    
    // Créer une campagne de test pour Jean s'il n'en a pas
    if ($user && isset($user['id'])) {
        $campaigns_query = "SELECT COUNT(*) as count FROM campaigns WHERE dm_id = ?";
        $campaigns_stmt = $pdo->prepare($campaigns_query);
        $campaigns_stmt->execute([$user['id']]);
        $campaign_count = $campaigns_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($campaign_count == 0) {
            // Créer une campagne de test
            $campaign_query = "INSERT INTO campaigns (dm_id, title, description, game_system, is_public, invite_code, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $campaign_stmt = $pdo->prepare($campaign_query);
            $invite_code = strtoupper(substr(md5(uniqid()), 0, 8));
            $campaign_stmt->execute([$user['id'], 'Campagne de Test', 'Description de test', 'D&D 5e', 1, $invite_code]);
            
            echo "✅ Campagne de test créée pour $username\n";
        } else {
            echo "ℹ️  $username a déjà $campaign_count campagne(s)\n";
        }
    } else {
        echo "⚠️  Impossible de créer une campagne - utilisateur non trouvé\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 L'utilisateur Jean est maintenant prêt pour les tests (environnement test)!\n";
?>
