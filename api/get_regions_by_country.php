<?php
/**
 * API Endpoint: Récupérer les régions d'un pays
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $countryId = (int)($_GET['country_id'] ?? 0);
    
    if (!$countryId) {
        echo json_encode([
            'success' => true,
            'regions' => []
        ]);
        exit;
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM regions 
        WHERE country_id = ?
        ORDER BY name
    ");
    $stmt->execute([$countryId]);
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'regions' => $regions
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_regions_by_country.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
