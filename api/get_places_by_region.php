<?php
/**
 * API Endpoint: Récupérer les lieux d'une région
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
    
    $regionId = (int)($_GET['region_id'] ?? 0);
    $excludePlaceId = (int)($_GET['exclude_place_id'] ?? 0);
    
    if (!$regionId) {
        echo json_encode([
            'success' => true,
            'places' => []
        ]);
        exit;
    }
    
    $pdo = getPDO();
    $sql = "
        SELECT id, title
        FROM places 
        WHERE region_id = ?
    ";
    $params = [$regionId];
    
    if ($excludePlaceId) {
        $sql .= " AND id != ?";
        $params[] = $excludePlaceId;
    }
    
    $sql .= " ORDER BY title";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'places' => $places
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_places_by_region.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
