<?php
// Script pour mettre à jour la session utilisateur après changement de rôle
require_once 'config/database.php';
require_once 'includes/functions.php';

// Démarrer la session
session_start();

echo "<h1>Mise à jour de la session utilisateur</h1>\n";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Aucun utilisateur connecté</p>\n";
    echo "<p><a href='login.php'>Se connecter</a></p>\n";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ Utilisateur connecté: ID $user_id</p>\n";

// Récupérer les informations utilisateur depuis la base de données
try {
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_dm FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p>❌ Utilisateur non trouvé en base de données</p>\n";
        exit;
    }
    
    echo "<p>✅ Utilisateur trouvé en base de données:</p>\n";
    echo "<ul>\n";
    echo "<li>ID: " . $user['id'] . "</li>\n";
    echo "<li>Username: " . $user['username'] . "</li>\n";
    echo "<li>Email: " . $user['email'] . "</li>\n";
    echo "<li>Rôle: " . $user['role'] . "</li>\n";
    echo "<li>Est MJ: " . ($user['is_dm'] ? 'Oui' : 'Non') . "</li>\n";
    echo "</ul>\n";
    
    // Mettre à jour la session avec les nouvelles informations
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_dm'] = $user['is_dm'];
    
    echo "<p>✅ Session mise à jour avec les nouvelles informations</p>\n";
    
    // Tester les fonctions de rôle
    echo "<h2>Test des fonctions de rôle:</h2>\n";
    echo "<ul>\n";
    echo "<li>isAdmin(): " . (isAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDM(): " . (isDM() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isPlayer(): " . (isPlayer() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDMOrAdmin(): " . (isDMOrAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>hasElevatedPrivileges(): " . (hasElevatedPrivileges() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "</ul>\n";
    
    // Afficher le label du rôle
    echo "<p><strong>Rôle affiché:</strong> " . getRoleLabel($user['role']) . "</p>\n";
    echo "<p><strong>Couleur du rôle:</strong> " . getRoleColor($user['role']) . "</p>\n";
    
    echo "<h2>Actions disponibles:</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='profile.php'>Voir le profil</a></li>\n";
    if (isAdmin()) {
        echo "<li><a href='admin_versions.php'>Page d'administration des versions</a></li>\n";
    }
    if (isDMOrAdmin()) {
        echo "<li><a href='campaigns.php'>Gérer les campagnes</a></li>\n";
    }
    echo "<li><a href='index.php'>Retour à l'accueil</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur lors de la récupération des informations utilisateur: " . $e->getMessage() . "</p>\n";
}
?>
