<?php
/**
 * API Endpoint: Basculer l'identification d'un PNJ
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
    
    if (!$input || !isset($input['npc_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $npcId = (int)$input['npc_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur est DM ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé : Vous n\'êtes pas le MJ ou un administrateur');
    }
    
    $pdo = getPDO();
    
    // Basculer l'identification du PNJ
    $stmt = $pdo->prepare("
        UPDATE place_npcs 
        SET is_identified = NOT is_identified 
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$npcId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Identification du PNJ mise à jour'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour de l\'identification');
    }
    
} catch (Exception $e) {
    error_log("Erreur toggle_npc_identification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
