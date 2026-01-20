<?php
/**
 * API Endpoint: Ajouter un joueur à une pièce
 */

require_once '../includes/functions.php';
require_once '../classes/Room.php';

// Ne pas définir Content-Type JSON car on va rediriger

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
    
    // Créer l'instance de la pièce
    $lieu = Room::findById($placeId);
    if (!$lieu) {
        throw new Exception('Pièce non trouvé');
    }
    
    // Vérifier que le personnage est accepté dans la campagne
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT 1 FROM campaign_applications
        WHERE campaign_id = ? AND player_id = ? AND character_id = ? AND status = 'approved'
    ");
    $stmt->execute([$campaignId, $playerId, $characterId]);
    if (!$stmt->fetch()) {
        throw new Exception('Ce personnage n\'est pas accepté dans cette campagne.');
    }
    
    // Ajouter le joueur
    $result = $lieu->addPlayer($playerId, $characterId, $campaignId);
    
    if ($result['success']) {
        // Rediriger vers la page de la pièce pour recharger
        header('Location: ../view_place.php?id=' . $placeId . '&player_added=1');
        exit();
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Erreur add_player.php: " . $e->getMessage());
    // Rediriger vers la page de la pièce avec un message d'erreur
    $placeId = isset($_POST['place_id']) ? (int)$_POST['place_id'] : 0;
    if ($placeId) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: ../view_place.php?id=' . $placeId);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}
?>
