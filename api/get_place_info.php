<?php
/**
 * API Endpoint: Récupérer les informations d'une pièce (country_id, region_id)
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/classes/Room.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $placeId = (int)($_GET['place_id'] ?? 0);
    
    if (!$placeId) {
        throw new Exception('ID de la pièce requis');
    }
    
    $lieu = Room::findById($placeId);
    if (!$lieu) {
        throw new Exception('Pièce non trouvé');
    }
    
    $place = $lieu->toArray();
    
    echo json_encode([
        'success' => true,
        'place' => [
            'id' => $place['id'],
            'title' => $place['title'],
            'country_id' => $place['country_id'],
            'region_id' => $place['region_id']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_place_info.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

