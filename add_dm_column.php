<?php
/**
 * Script pour ajouter la colonne is_dm à la table users
 * Usage: php add_dm_column.php
 */

require_once 'config/database.php';

try {
    // Vérifier si la colonne is_dm existe déjà
    $check_query = "SHOW COLUMNS FROM users LIKE 'is_dm'";
    $check_stmt = $pdo->query($check_query);
    
    if ($check_stmt->rowCount() == 0) {
        // Ajouter la colonne is_dm
        $alter_query = "ALTER TABLE users ADD COLUMN is_dm TINYINT(1) DEFAULT 0";
        $pdo->exec($alter_query);
        echo "✅ Colonne 'is_dm' ajoutée à la table 'users'\n";
    } else {
        echo "ℹ️  La colonne 'is_dm' existe déjà\n";
    }
    
    // Vérifier si la colonne role existe déjà
    $check_role_query = "SHOW COLUMNS FROM users LIKE 'role'";
    $check_role_stmt = $pdo->query($check_role_query);
    
    if ($check_role_stmt->rowCount() == 0) {
        // Ajouter la colonne role
        $alter_role_query = "ALTER TABLE users ADD COLUMN role ENUM('player', 'dm', 'admin') DEFAULT 'player'";
        $pdo->exec($alter_role_query);
        echo "✅ Colonne 'role' ajoutée à la table 'users'\n";
    } else {
        echo "ℹ️  La colonne 'role' existe déjà\n";
    }
    
    // Mettre à jour les utilisateurs existants
    // Si is_dm = 1, alors role = 'dm'
    $update_query = "UPDATE users SET role = 'dm' WHERE is_dm = 1";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute();
    $updated_count = $update_stmt->rowCount();
    
    if ($updated_count > 0) {
        echo "✅ $updated_count utilisateur(s) mis à jour avec le rôle 'dm'\n";
    }
    
    // Vérifier la structure finale
    $structure_query = "DESCRIBE users";
    $structure_stmt = $pdo->query($structure_query);
    $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Structure de la table 'users':\n";
    foreach ($columns as $column) {
        echo "   - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Vérifier les utilisateurs avec leurs rôles
    $users_query = "SELECT id, username, email, is_dm, role FROM users";
    $users_stmt = $pdo->query($users_query);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n👥 Utilisateurs dans la base de données:\n";
    foreach ($users as $user) {
        echo "   - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, is_dm: {$user['is_dm']}, role: {$user['role']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎯 La table 'users' est maintenant prête pour les tests!\n";
echo "💡 Vous pouvez maintenant exécuter: ./test_campaigns_fixed.sh\n";
?>
