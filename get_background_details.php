<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$backgroundId = (int)($_GET['id'] ?? 0);

if ($backgroundId === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID d\'historique invalide'
    ]);
    exit;
}

try {
    $background = Background::getBackgroundById($backgroundId);
    
    if (!$background) {
        echo json_encode([
            'success' => false,
            'message' => 'Historique non trouvé'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'background' => $background
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
    ]);
}
?>

