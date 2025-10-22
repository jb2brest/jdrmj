<?php
/**
 * API Endpoint: Mettre à jour la position d'un token
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
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['place_id']) || !isset($input['token_type']) || !isset($input['entity_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $placeId = (int)$input['place_id'];
    $tokenType = $input['token_type'];
    $entityId = (int)$input['entity_id'];
    $positionX = (int)($input['position_x'] ?? 0);
    $positionY = (int)($input['position_y'] ?? 0);
    $isOnMap = (bool)($input['is_on_map'] ?? true);
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Mettre à jour la position du token
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        position_x = VALUES(position_x),
        position_y = VALUES(position_y),
        is_on_map = VALUES(is_on_map)
    ");
    
    $success = $stmt->execute([$placeId, $tokenType, $entityId, $positionX, $positionY, $isOnMap]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Position du token mise à jour'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur update_token_position.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
