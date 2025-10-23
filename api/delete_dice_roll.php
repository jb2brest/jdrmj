<?php
/**
 * API Endpoint: Supprimer un lancer de dés
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
    
    if (!$input || !isset($input['roll_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $rollId = (int)$input['roll_id'];
    $userId = getCurrentUserId();
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Supprimer le lancer de dés
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        DELETE FROM dice_rolls 
        WHERE id = ? AND user_id = ?
    ");
    
    $success = $stmt->execute([$rollId, $userId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Lancer de dés supprimé'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression');
    }
    
} catch (Exception $e) {
    error_log("Erreur delete_dice_roll.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
