<?php
/**
 * API Endpoint: Sauvegarder la position d'un token
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['place_id']) || !isset($input['token_data'])) {
        throw new Exception('Données manquantes');
    }
    
    $placeId = (int)$input['place_id'];
    $tokenData = $input['token_data'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur a accès à ce lieu
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT p.id, pc.campaign_id 
        FROM places p 
        JOIN place_campaigns pc ON p.id = pc.place_id
        WHERE p.id = ?
    ");
    $stmt->execute([$placeId]);
    $place = $stmt->fetch();
    
    if (!$place) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Vérifier que l'utilisateur est membre de la campagne ou est le DM
    $stmt = $pdo->prepare("
        SELECT role FROM campaign_members 
        WHERE campaign_id = ? AND user_id = ?
    ");
    $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
    $membership = $stmt->fetch();
    
    // Si pas membre, vérifier si c'est le DM de la campagne
    if (!$membership) {
        $stmt = $pdo->prepare("
            SELECT dm_id FROM campaigns 
            WHERE id = ? AND dm_id = ?
        ");
        $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
        $is_dm = $stmt->fetch();
        
        if (!$is_dm) {
            throw new Exception('Accès refusé - Vous devez être membre de la campagne ou le DM');
        }
    }
    
    // Sauvegarder la position du token
    $tokenType = $tokenData['token_type'];
    $entityId = (int)$tokenData['entity_id'];
    $positionX = (int)$tokenData['position_x'];
    $positionY = (int)$tokenData['position_y'];
    $isOnMap = (bool)$tokenData['is_on_map'];
    
    // Vérifier si le token existe déjà
    $stmt = $pdo->prepare("
        SELECT id FROM place_tokens 
        WHERE place_id = ? AND token_type = ? AND entity_id = ?
    ");
    $stmt->execute([$placeId, $tokenType, $entityId]);
    $existingToken = $stmt->fetch();
    
    if ($existingToken) {
        // Mettre à jour la position existante
        $stmt = $pdo->prepare("
            UPDATE place_tokens 
            SET position_x = ?, position_y = ?, is_on_map = ?, updated_at = NOW()
            WHERE place_id = ? AND token_type = ? AND entity_id = ?
        ");
        $stmt->execute([$positionX, $positionY, $isOnMap ? 1 : 0, $placeId, $tokenType, $entityId]);
    } else {
        // Créer une nouvelle entrée
        $stmt = $pdo->prepare("
            INSERT INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$placeId, $tokenType, $entityId, $positionX, $positionY, $isOnMap ? 1 : 0]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Position du token sauvegardée avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur save_token_position.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
