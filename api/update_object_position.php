<?php
/**
 * API Endpoint: Mettre à jour la position d'un objet
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['object_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $objectId = (int)$input['object_id'];
    $positionX = (int)($input['position_x'] ?? 0);
    $positionY = (int)($input['position_y'] ?? 0);
    $isOnMap = (bool)($input['is_on_map'] ?? true);
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Mettre à jour la position de l'objet
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE items 
        SET position_x = ?, position_y = ?, is_on_map = ?
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$positionX, $positionY, $isOnMap, $objectId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Position de l\'objet mise à jour'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour');
    }
    
} catch (Exception $e) {
    error_log("Erreur update_object_position.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
