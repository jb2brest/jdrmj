<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de race invalide']);
    exit;
}

$raceId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->execute([$raceId]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$race) {
        echo json_encode(['success' => false, 'message' => 'Race non trouvée']);
        exit;
    }
    
    echo json_encode(['success' => true, 'race' => $race]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_race_info: " . $e->getMessage());
}
?>
