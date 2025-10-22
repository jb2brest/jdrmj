<?php
/**
 * API Endpoint: Ajouter un joueur à un lieu
 */

require_once '../includes/functions.php';
require_once '../classes/Lieu.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $placeId = (int)($_POST['place_id'] ?? 0);
    $playerId = (int)($_POST['player_id'] ?? 0);
    $characterId = (int)($_POST['character_id'] ?? 0);
    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    
    if (!$placeId || !$playerId || !$characterId || !$campaignId) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Ajouter le joueur
    $result = $lieu->addPlayer($playerId, $characterId, $campaignId);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Erreur add_player.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
