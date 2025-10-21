<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPdo();
    
    $world_id = $_GET['world_id'] ?? null;
    
    if (!$world_id) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, name FROM countries WHERE world_id = ? ORDER BY name");
    $stmt->execute([$world_id]);
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($countries);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des pays']);
}
?>

