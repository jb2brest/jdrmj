<?php
// Script pour vérifier et corriger l'utilisateur admin
require_once 'config/database.php';

echo "<h1>Vérification de l'utilisateur admin</h1>\n";

try {
    // Vérifier tous les utilisateurs
    $stmt = $pdo->query("SELECT id, username, email, role, is_dm FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<h2>Tous les utilisateurs:</h2>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Est MJ</th></tr>\n";
    
    foreach ($users as $user) {
        $highlight = ($user['email'] === 'jean.m.bernard@gmail.com') ? 'style="background-color: yellow;"' : '';
        echo "<tr $highlight>\n";
        echo "<td>" . $user['id'] . "</td>\n";
        echo "<td>" . $user['username'] . "</td>\n";
        echo "<td>" . $user['email'] . "</td>\n";
        echo "<td><strong>" . $user['role'] . "</strong></td>\n";
        echo "<td>" . ($user['is_dm'] ? 'Oui' : 'Non') . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Vérifier spécifiquement jean.m.bernard@gmail.com
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_dm FROM users WHERE email = ?");
    $stmt->execute(['jean.m.bernard@gmail.com']);
    $admin_user = $stmt->fetch();
    
    echo "<h2>Utilisateur admin (jean.m.bernard@gmail.com):</h2>\n";
    
    if ($admin_user) {
        echo "<p>✅ Utilisateur trouvé:</p>\n";
        echo "<ul>\n";
        echo "<li>ID: " . $admin_user['id'] . "</li>\n";
        echo "<li>Username: " . $admin_user['username'] . "</li>\n";
        echo "<li>Email: " . $admin_user['email'] . "</li>\n";
        echo "<li>Rôle: <strong>" . $admin_user['role'] . "</strong></li>\n";
        echo "<li>Est MJ: " . ($admin_user['is_dm'] ? 'Oui' : 'Non') . "</li>\n";
        echo "</ul>\n";
        
        if ($admin_user['role'] === 'admin') {
            echo "<p>✅ Le rôle est correctement défini comme 'admin'</p>\n";
        } else {
            echo "<p>❌ Le rôle n'est pas 'admin' mais '" . $admin_user['role'] . "'</p>\n";
            echo "<p>Correction en cours...</p>\n";
            
            // Corriger le rôle
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin', is_dm = 1 WHERE email = ?");
            $stmt->execute(['jean.m.bernard@gmail.com']);
            
            echo "<p>✅ Rôle corrigé en base de données</p>\n";
        }
    } else {
        echo "<p>❌ Utilisateur jean.m.bernard@gmail.com non trouvé</p>\n";
    }
    
    // Vérifier les rôles disponibles
    echo "<h2>Rôles disponibles dans le système:</h2>\n";
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roles = $stmt->fetchAll();
    
    foreach ($roles as $role) {
        echo "<p>- " . $role['role'] . ": " . $role['count'] . " utilisateur(s)</p>\n";
    }
    
    echo "<h2>Instructions:</h2>\n";
    echo "<ol>\n";
    echo "<li>Si le rôle n'était pas 'admin', il a été corrigé</li>\n";
    echo "<li>Déconnectez-vous et reconnectez-vous avec jean.m.bernard@gmail.com</li>\n";
    echo "<li>Ou accédez à <a href='fix_admin_session.php'>fix_admin_session.php</a> après connexion</li>\n";
    echo "<li>Vérifiez ensuite <a href='profile.php'>profile.php</a></li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
