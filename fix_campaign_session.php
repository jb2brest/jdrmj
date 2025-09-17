<?php
// Script pour corriger la session et l'accès aux campagnes
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Correction Session et Accès Campagnes</h1>\n";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Aucun utilisateur connecté</p>\n";
    echo "<p><a href='login.php'>Se connecter</a></p>\n";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ Utilisateur connecté: ID $user_id</p>\n";

try {
    // Récupérer les informations utilisateur depuis la base de données
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_dm FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p>❌ Utilisateur non trouvé en base de données</p>\n";
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
    
    // Forcer la mise à jour de la session
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_dm'] = $user['is_dm'];
    
    echo "<p>✅ Session forcée mise à jour</p>\n";
    
    // Tester les fonctions de rôle
    echo "<h2>2. Test des Fonctions de Rôle</h2>\n";
    echo "<ul>\n";
    echo "<li>getUserRole(): " . getUserRole() . "</li>\n";
    echo "<li>isAdmin(): " . (isAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDM(): " . (isDM() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "<li>isDMOrAdmin(): " . (isDMOrAdmin() ? '✅ Oui' : '❌ Non') . "</li>\n";
    echo "</ul>\n";
    
    // Tester la récupération des campagnes
    echo "<h2>3. Test de Récupération des Campagnes</h2>\n";
    
    if (isAdmin()) {
        echo "<p>✅ Mode admin - récupération de toutes les campagnes</p>\n";
        $stmt = $pdo->prepare("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id ORDER BY c.created_at DESC");
        $stmt->execute();
    } else {
        echo "<p>✅ Mode MJ - récupération des campagnes de l'utilisateur</p>\n";
        $stmt = $pdo->prepare("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id WHERE c.dm_id = ? ORDER BY c.created_at DESC");
        $stmt->execute([$user_id]);
    }
    
    $campaigns = $stmt->fetchAll();
    
    echo "<p><strong>Campagnes trouvées: " . count($campaigns) . "</strong></p>\n";
    
    if (empty($campaigns)) {
        echo "<p>❌ Aucune campagne trouvée</p>\n";
        
        // Vérifier toutes les campagnes en base
        echo "<h3>Vérification de toutes les campagnes en base:</h3>\n";
        $stmt = $pdo->query("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id ORDER BY c.created_at DESC");
        $all_campaigns = $stmt->fetchAll();
        
        if (empty($all_campaigns)) {
            echo "<p>❌ Aucune campagne en base de données</p>\n";
        } else {
            echo "<p>✅ Campagnes en base de données:</p>\n";
            echo "<ul>\n";
            foreach ($all_campaigns as $campaign) {
                $is_owner = ($campaign['dm_id'] == $user_id) ? ' (VOTRE CAMPAGNE)' : '';
                echo "<li>ID: {$campaign['id']} - {$campaign['title']} - MJ: {$campaign['dm_name']} (ID: {$campaign['dm_id']}){$is_owner}</li>\n";
            }
            echo "</ul>\n";
            
            // Si l'utilisateur est admin mais ne voit pas ses campagnes, c'est un bug
            if (isAdmin() && count($all_campaigns) > 0) {
                echo "<p>⚠️ BUG DÉTECTÉ: L'admin devrait voir toutes les campagnes mais n'en voit aucune</p>\n";
            }
        }
    } else {
        echo "<p>✅ Campagnes récupérées:</p>\n";
        echo "<ul>\n";
        foreach ($campaigns as $campaign) {
            $is_owner = ($campaign['dm_id'] == $user_id) ? ' (VOTRE CAMPAGNE)' : '';
            echo "<li>ID: {$campaign['id']} - {$campaign['title']} - MJ: {$campaign['dm_name']} (ID: {$campaign['dm_id']}){$is_owner}</li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "<h2>4. Actions</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='campaigns.php'>Accéder à campaigns.php</a></li>\n";
    echo "<li><a href='debug_campaign_access.php'>Script de diagnostic complet</a></li>\n";
    echo "<li><a href='logout.php'>Se déconnecter et se reconnecter</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}
?>
