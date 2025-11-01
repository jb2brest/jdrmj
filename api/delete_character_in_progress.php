<?php
/**
 * API Endpoint: Supprimer un personnage en cours de création (PJ ou PNJ)
 * 
 * Ce endpoint permet de supprimer complètement un personnage temporaire (PT_character)
 * avec toutes ses données associées.
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

try {
    // Récupérer les données
    $input = json_decode(file_get_contents('php://input'), true);
    $ptCharacterId = isset($input['pt_character_id']) ? (int)$input['pt_character_id'] : 0;
    
    if ($ptCharacterId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de personnage temporaire invalide']);
        exit();
    }
    
    // Charger le personnage temporaire
    require_once '../classes/PTCharacter.php';
    $pdo = \Database::getInstance()->getPdo();
    $ptCharacter = PTCharacter::findById($ptCharacterId, $pdo);
    
    if (!$ptCharacter) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Personnage temporaire non trouvé']);
        exit();
    }
    
    // Vérifier que l'utilisateur est le propriétaire
    if ($ptCharacter->user_id != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
        exit();
    }
    
    if ($ptCharacter->delete()) {
        echo json_encode([
            'success' => true,
            'message' => 'Personnage en cours de création supprimé avec succès',
            'character_type' => $ptCharacter->character_type ?? 'player'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du personnage']);
    }
    
} catch (Exception $e) {
    error_log("Erreur delete_character_in_progress.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

