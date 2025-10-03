<?php
/**
 * Test de la correction de session pour select_starting_equipment.php
 */

// Simuler une session active
session_start();
$_SESSION['user_id'] = 1;

echo "=== Test de la session ===\n";
echo "Session démarrée: " . (session_status() === PHP_SESSION_ACTIVE ? 'OUI' : 'NON') . "\n";
echo "User ID en session: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "\n";

// Test de la fonction isLoggedIn
require_once 'classes/init.php';
require_once 'includes/functions.php';

echo "isLoggedIn(): " . (isLoggedIn() ? 'OUI' : 'NON') . "\n";

// Test de la connexion à la base
try {
    $pdo = getPDO();
    echo "Connexion DB: OK\n";
    
    // Test d'une requête simple
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM characters WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn();
    echo "Personnages trouvés: $count\n";
    
} catch (Exception $e) {
    echo "Erreur DB: " . $e->getMessage() . "\n";
}

echo "\n✅ Test de session terminé!\n";
?>

