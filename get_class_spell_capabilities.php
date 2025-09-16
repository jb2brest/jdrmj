<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de classe invalide']);
    exit;
}

$classId = (int)$_GET['class_id'];
$level = isset($_GET['level']) ? (int)$_GET['level'] : 1;

try {
    $capabilities = getClassSpellCapabilities($classId, $level);
    
    if (!$capabilities) {
        echo json_encode(['success' => false, 'message' => 'Capacités non trouvées']);
        exit;
    }
    
    echo json_encode(['success' => true, 'capabilities' => $capabilities]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_class_spell_capabilities: " . $e->getMessage());
}
?>

