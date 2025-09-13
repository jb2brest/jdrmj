<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de classe invalide']);
    exit;
}

$classId = (int)$_GET['id'];

try {
    $equipment = getClassStartingEquipment($classId);
    
    if (empty($equipment)) {
        echo json_encode(['success' => true, 'equipment' => []]);
    } else {
        echo json_encode(['success' => true, 'equipment' => $equipment]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_class_starting_equipment: " . $e->getMessage());
}
?>
