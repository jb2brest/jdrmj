<?php
/**
 * API Endpoint: Réinitialiser les positions des tokens d'une pièce
 */

require_once '../includes/functions.php';
require_once '../classes/Room.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['place_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $placeId = (int)$input['place_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Créer l'instance de la pièce
    $lieu = Room::findById($placeId);
    if (!$lieu) {
        throw new Exception('Pièce non trouvé');
    }
    
    // Réinitialiser les positions des tokens
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        UPDATE place_tokens 
        SET position_x = 0, position_y = 0, is_on_map = 0
        WHERE place_id = ?
    ");
    
    $success = $stmt->execute([$placeId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Positions des tokens réinitialisées'
        ]);
    } else {
        throw new Exception('Erreur lors de la réinitialisation');
    }
    
} catch (Exception $e) {
    error_log("Erreur reset_token_positions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
