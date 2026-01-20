<?php
/**
 * API Endpoint: Supprimer un joueur
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Room.php';

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
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur est DM ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé : Vous n\'êtes pas le MJ ou un administrateur');
    }
    
    $pdo = getPDO();
    
    // Supprimer le joueur
    $stmt = $pdo->prepare("DELETE FROM place_players WHERE id = ?");
    $success = $stmt->execute([$playerId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Joueur supprimé avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression du joueur');
    }
    
} catch (Exception $e) {
    error_log("Erreur remove_player.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
