<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPdo();
    
    $country_id = $_GET['country_id'] ?? null;
    
    if (!$country_id) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, name FROM regions WHERE country_id = ? ORDER BY name");
    $stmt->execute([$country_id]);
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($regions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des régions']);
}
?>