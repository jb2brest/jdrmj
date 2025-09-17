<?php
// Script simple pour rafraîchir la session utilisateur
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Rafraîchissement de la session</h1>\n";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Aucun utilisateur connecté</p>\n";
    echo "<p><a href='login.php'>Se connecter</a></p>\n";
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations utilisateur depuis la base de données
try {
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_dm FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p>❌ Utilisateur non trouvé</p>\n";
        exit;
    }
    
    echo "<p>✅ Utilisateur: " . $user['username'] . " (" . $user['email'] . ")</p>\n";
    echo "<p>✅ Rôle en base: <strong>" . $user['role'] . "</strong></p>\n";
    
    // Mettre à jour la session
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_dm'] = $user['is_dm'];
    
    echo "<p>✅ Session mise à jour</p>\n";
    
    // Tester les fonctions
    echo "<h2>Test des fonctions:</h2>\n";
    echo "<ul>\n";
    echo "<li>isAdmin(): " . (isAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDM(): " . (isDM() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isPlayer(): " . (isPlayer() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "</ul>\n";
    
    echo "<h2>Actions:</h2>\n";
    echo "<p><a href='profile.php'>Voir le profil</a></p>\n";
    if (isAdmin()) {
        echo "<p><a href='admin_versions.php'>Page d'administration</a></p>\n";
    }
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
