<?php
/**
 * API Endpoint: Récupérer les personnages d'un joueur
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
    
    if (!$input || !isset($input['player_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $playerId = (int)$input['player_id'];
    $campaignId = isset($input['campaign_id']) ? (int)$input['campaign_id'] : null;
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $pdo = getPDO();
    
    // Si une campagne est spécifiée, filtrer les personnages acceptés dans cette campagne
    if ($campaignId) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name
            FROM characters c
            INNER JOIN campaign_applications ca ON c.id = ca.character_id
            WHERE c.user_id = ? 
            AND ca.campaign_id = ?
            AND ca.status = 'approved'
            ORDER BY c.name
        ");
        $stmt->execute([$playerId, $campaignId]);
    } else {
        // Sinon, retourner tous les personnages du joueur
        $stmt = $pdo->prepare("
            SELECT id, name
            FROM characters 
            WHERE user_id = ?
            ORDER BY name
        ");
        $stmt->execute([$playerId]);
    }
    
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'characters' => $characters
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_player_characters.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
