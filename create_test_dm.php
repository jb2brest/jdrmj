<?php
/**
 * Script pour créer un utilisateur DM de test
 * Usage: php create_test_dm.php
 */

require_once 'config/database.php';

// Données de l'utilisateur DM de test
$username = 'test_user';
$email = 'test@example.com';
$password = 'TestPassword123!';
$is_dm = 1; // 1 = DM, 0 = joueur normal

try {
    // Vérifier si l'utilisateur existe déjà
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        // L'utilisateur existe, mettre à jour ses droits DM
        $update_query = "UPDATE users SET is_dm = ? WHERE username = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$is_dm, $username]);
        
        echo "✅ Utilisateur '$username' mis à jour avec les droits de DM\n";
    } else {
        // Créer un nouvel utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, is_dm, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([$username, $email, $hashed_password, $is_dm]);
        
        echo "✅ Utilisateur DM '$username' créé avec succès\n";
    }
    
    // Vérifier les droits
    $verify_query = "SELECT username, email, is_dm FROM users WHERE username = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$username]);
    $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "📋 Informations de l'utilisateur:\n";
        echo "   - Nom d'utilisateur: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n";
        echo "   - Est DM: " . ($user['is_dm'] ? 'Oui' : 'Non') . "\n";
        echo "   - Mot de passe: $password\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 L'utilisateur DM est maintenant prêt pour les tests!\n";
echo "💡 Vous pouvez maintenant exécuter: ./test_campaigns_fixed.sh\n";
?>
