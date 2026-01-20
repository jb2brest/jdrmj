<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPdo();
    
    $region_id = $_GET['region_id'] ?? null;
    
    if (!$region_id) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, title FROM places WHERE region_id = ? ORDER BY title");
    $stmt->execute([$region_id]);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($places);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des pièces']);
}
?>