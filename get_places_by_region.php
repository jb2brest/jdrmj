<?php
/**
 * Endpoint pour récupérer les lieux d'une région
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $region_id = (int)($_GET['region_id'] ?? 0);
    $exclude_place_id = (int)($_GET['exclude_place_id'] ?? 0);
    
    if (!$region_id) {
        echo json_encode(['success' => false, 'error' => 'ID de région manquant']);
        exit;
    }
    
    $pdo = getPDO();
    
    if ($exclude_place_id) {
        $stmt = $pdo->prepare("SELECT id, title FROM places WHERE region_id = ? AND id != ? ORDER BY title");
        $stmt->execute([$region_id, $exclude_place_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, title FROM places WHERE region_id = ? ORDER BY title");
        $stmt->execute([$region_id]);
    }
    
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'places' => $places
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


