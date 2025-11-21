<?php
/**
 * API Endpoint: Supprimer toutes les positions de la cartographie d'une région
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Region.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['region_id'])) {
        throw new Exception('region_id manquant');
    }
    
    $regionId = (int)$input['region_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur a accès à cette région
    $region = Region::findById($regionId);
    if (!$region) {
        throw new Exception('Région non trouvée');
    }
    
    $monde = $region->getMonde();
    if (!$monde || $monde['created_by'] != $_SESSION['user_id']) {
        throw new Exception('Accès refusé - Vous n\'avez pas la permission de modifier cette région');
    }
    
    // Supprimer toutes les positions
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        DELETE FROM region_cartography_positions 
        WHERE region_id = ?
    ");
    $stmt->execute([$regionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Positions réinitialisées avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur delete_region_cartography_positions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

