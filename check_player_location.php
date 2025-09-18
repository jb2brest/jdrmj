<?php
/**
 * API pour vérifier la localisation actuelle d'un joueur
 * Utilisé par view_scene_player.php pour détecter les changements de lieu
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Trouver le lieu où se trouve actuellement le joueur
    $stmt = $pdo->prepare("
        SELECT p.id as place_id, p.title as place_title
        FROM places p 
        JOIN place_players pp ON p.id = pp.place_id 
        WHERE pp.player_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $location = $stmt->fetch();
    
    if ($location) {
        // Le joueur est dans un lieu
        echo json_encode([
            'success' => true,
            'place_id' => (int)$location['place_id'],
            'place_title' => $location['place_title'],
            'has_location' => true
        ]);
    } else {
        // Le joueur n'est dans aucun lieu
        echo json_encode([
            'success' => true,
            'place_id' => null,
            'place_title' => null,
            'has_location' => false
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
