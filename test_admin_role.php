<?php
// Script de test pour vérifier le rôle admin
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test du Rôle Admin</h1>\n";

// Test 1: Vérifier la base de données
echo "<h2>1. Vérification en base de données</h2>\n";
try {
    $stmt = $pdo->query("SELECT id, username, email, role, is_dm FROM users WHERE email = 'jean.m.bernard@gmail.com'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ Utilisateur trouvé:</p>\n";
        echo "<ul>\n";
        echo "<li>ID: " . $user['id'] . "</li>\n";
        echo "<li>Username: " . $user['username'] . "</li>\n";
        echo "<li>Email: " . $user['email'] . "</li>\n";
        echo "<li>Rôle: <strong>" . $user['role'] . "</strong></li>\n";
        echo "<li>Est MJ: " . ($user['is_dm'] ? 'Oui' : 'Non') . "</li>\n";
        echo "</ul>\n";
        
        if ($user['role'] === 'admin') {
            echo "<p>✅ Le rôle est correctement défini comme 'admin'</p>\n";
        } else {
            echo "<p>❌ Le rôle n'est pas 'admin' mais '" . $user['role'] . "'</p>\n";
        }
    } else {
        echo "<p>❌ Utilisateur jean.m.bernard@gmail.com non trouvé</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

// Test 2: Vérifier les fonctions de rôle (simulation)
echo "<h2>2. Test des fonctions de rôle (simulation)</h2>\n";

// Simuler une session admin
$_SESSION['user_id'] = 2; // ID de jean.m.bernard@gmail.com
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'Jean';
$_SESSION['email'] = 'jean.m.bernard@gmail.com';
$_SESSION['is_dm'] = 1;

echo "<p>Session simulée pour l'utilisateur admin</p>\n";

// Tester les fonctions
echo "<ul>\n";
echo "<li>isAdmin(): " . (isAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
echo "<li>isDM(): " . (isDM() ? '✅ Oui' : '❌ Non') . "</li>\n";
echo "<li>isPlayer(): " . (isPlayer() ? '✅ Oui' : '❌ Non') . "</li>\n";
echo "<li>isDMOrAdmin(): " . (isDMOrAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
echo "<li>hasElevatedPrivileges(): " . (hasElevatedPrivileges() ? '✅ Oui' : '❌ Non') . "</li>\n";
echo "</ul>\n";

// Test 3: Vérifier les labels et couleurs
echo "<h2>3. Test des labels et couleurs</h2>\n";
echo "<ul>\n";
echo "<li>Label admin: " . getRoleLabel('admin') . "</li>\n";
echo "<li>Couleur admin: " . getRoleColor('admin') . "</li>\n";
echo "</ul>\n";

// Test 4: Vérifier l'accès aux pages admin
echo "<h2>4. Test d'accès aux pages admin</h2>\n";
echo "<p>Pages accessibles avec le rôle admin:</p>\n";
echo "<ul>\n";
echo "<li><a href='profile.php'>profile.php</a> - Devrait afficher 'Administrateur'</li>\n";
echo "<li><a href='admin_versions.php'>admin_versions.php</a> - Page d'administration des versions</li>\n";
echo "<li><a href='campaigns.php'>campaigns.php</a> - Gestion des campagnes</li>\n";
echo "<li><a href='refresh_session.php'>refresh_session.php</a> - Rafraîchir la session</li>\n";
echo "</ul>\n";

// Test 5: Vérifier le CSS
echo "<h2>5. Test du CSS pour le rôle admin</h2>\n";
echo "<p>Le CSS devrait inclure:</p>\n";
echo "<ul>\n";
echo "<li>Classe .role-badge.admin avec gradient violet</li>\n";
echo "<li>Icône shield-alt pour les admins</li>\n";
echo "</ul>\n";

echo "<h2>Instructions de test:</h2>\n";
echo "<ol>\n";
echo "<li>Connectez-vous avec jean.m.bernard@gmail.com</li>\n";
echo "<li>Accédez à <a href='profile.php'>profile.php</a></li>\n";
echo "<li>Vérifiez que le rôle affiché est 'Administrateur' avec une icône de bouclier</li>\n";
echo "<li>Testez l'accès à <a href='admin_versions.php'>admin_versions.php</a></li>\n";
echo "</ol>\n";

echo "<h2>Si le rôle n'apparaît pas correctement:</h2>\n";
echo "<ol>\n";
echo "<li>Déconnectez-vous et reconnectez-vous</li>\n";
echo "<li>Ou accédez à <a href='refresh_session.php'>refresh_session.php</a></li>\n";
echo "<li>Ou videz le cache du navigateur</li>\n";
echo "</ol>\n";
?>
