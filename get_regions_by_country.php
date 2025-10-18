<?php
/**
 * Endpoint pour récupérer les régions d'un pays
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $country_id = (int)($_GET['country_id'] ?? 0);
    
    if (!$country_id) {
        echo json_encode(['success' => false, 'error' => 'ID de pays manquant']);
        exit;
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, name FROM regions WHERE country_id = ? ORDER BY name");
    $stmt->execute([$country_id]);
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'regions' => $regions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
