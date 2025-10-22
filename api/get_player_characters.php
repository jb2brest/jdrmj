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
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM characters 
        WHERE user_id = ?
        ORDER BY name
    ");
    $stmt->execute([$playerId]);
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
