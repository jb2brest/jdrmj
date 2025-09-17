<?php
// Script de test pour vérifier l'accès aux campagnes pour les admins
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test d'Accès aux Campagnes pour Admin</h1>\n";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Aucun utilisateur connecté</p>\n";
    echo "<p><a href='login.php'>Se connecter</a></p>\n";
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_dm FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p>❌ Utilisateur non trouvé</p>\n";
        exit;
    }
    
    echo "<h2>1. Informations Utilisateur</h2>\n";
    echo "<ul>\n";
    echo "<li>ID: " . $user['id'] . "</li>\n";
    echo "<li>Username: " . $user['username'] . "</li>\n";
    echo "<li>Email: " . $user['email'] . "</li>\n";
    echo "<li>Rôle: <strong>" . $user['role'] . "</strong></li>\n";
    echo "<li>Est MJ: " . ($user['is_dm'] ? 'Oui' : 'Non') . "</li>\n";
    echo "</ul>\n";
    
    // Tester les fonctions de rôle
    echo "<h2>2. Test des Fonctions de Rôle</h2>\n";
    echo "<ul>\n";
    echo "<li>isAdmin(): " . (isAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDM(): " . (isDM() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isPlayer(): " . (isPlayer() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDMOrAdmin(): " . (isDMOrAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>hasElevatedPrivileges(): " . (hasElevatedPrivileges() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "</ul>\n";
    
    // Tester l'accès aux campagnes
    echo "<h2>3. Test d'Accès aux Campagnes</h2>\n";
    
    if (isAdmin()) {
        echo "<p>✅ L'utilisateur est admin - peut voir toutes les campagnes</p>\n";
        
        // Récupérer toutes les campagnes
        $stmt = $pdo->query("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id ORDER BY c.created_at DESC");
        $campaigns = $stmt->fetchAll();
        
        echo "<p><strong>Campagnes disponibles (toutes):</strong></p>\n";
        if (empty($campaigns)) {
            echo "<p>Aucune campagne trouvée</p>\n";
        } else {
            echo "<ul>\n";
            foreach ($campaigns as $campaign) {
                $owner = ($campaign['dm_id'] == $user_id) ? ' (VOTRE CAMPAGNE)' : '';
                echo "<li>ID: {$campaign['id']} - {$campaign['title']} - MJ: {$campaign['dm_name']}{$owner}</li>\n";
            }
            echo "</ul>\n";
        }
    } elseif (isDMOrAdmin()) {
        echo "<p>✅ L'utilisateur est MJ - peut voir ses campagnes</p>\n";
        
        // Récupérer les campagnes du MJ
        $stmt = $pdo->prepare("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id WHERE c.dm_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$user_id]);
        $campaigns = $stmt->fetchAll();
        
        echo "<p><strong>Vos campagnes:</strong></p>\n";
        if (empty($campaigns)) {
            echo "<p>Aucune campagne trouvée</p>\n";
        } else {
            echo "<ul>\n";
            foreach ($campaigns as $campaign) {
                echo "<li>ID: {$campaign['id']} - {$campaign['title']}</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p>❌ L'utilisateur n'a pas les privilèges pour gérer les campagnes</p>\n";
    }
    
    // Tester l'accès aux pages
    echo "<h2>4. Test d'Accès aux Pages</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='campaigns.php'>campaigns.php</a> - " . (isDMOrAdmin() ? '✅ Accessible' : '❌ Non accessible') . "</li>\n";
    if (!empty($campaigns)) {
        $first_campaign = $campaigns[0];
        echo "<li><a href='view_campaign.php?id={$first_campaign['id']}'>view_campaign.php?id={$first_campaign['id']}</a> - " . (isDMOrAdmin() ? '✅ Accessible' : '❌ Non accessible') . "</li>\n";
    }
    echo "</ul>\n";
    
    // Instructions
    echo "<h2>5. Instructions de Test</h2>\n";
    echo "<ol>\n";
    echo "<li>Accédez à <a href='campaigns.php'>campaigns.php</a></li>\n";
    if (isAdmin()) {
        echo "<li>Vous devriez voir <strong>Toutes les Campagnes</strong> avec le nom du MJ pour chaque campagne</li>\n";
        echo "<li>Vous devriez pouvoir supprimer et modifier la visibilité de toutes les campagnes</li>\n";
    } else {
        echo "<li>Vous devriez voir <strong>Mes Campagnes</strong> avec seulement vos campagnes</li>\n";
    }
    echo "<li>Cliquez sur une campagne pour accéder à <strong>view_campaign.php</strong></li>\n";
    echo "<li>Vérifiez que vous pouvez voir le contenu de la campagne</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
