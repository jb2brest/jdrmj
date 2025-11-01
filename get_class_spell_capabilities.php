<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/Classe.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de classe invalide']);
    exit;
}

$classId = (int)$_GET['class_id'];
$level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$wisdomModifier = isset($_GET['wisdom_modifier']) ? (int)$_GET['wisdom_modifier'] : 0;
$intelligenceModifier = isset($_GET['intelligence_modifier']) ? (int)$_GET['intelligence_modifier'] : 0;
$maxSpellsLearned = isset($_GET['max_spells_learned']) ? (int)$_GET['max_spells_learned'] : null;

try {
    $classObj = Classe::findById($classId);
    
    if (!$classObj) {
        echo json_encode(['success' => false, 'message' => 'Classe non trouvée']);
        exit;
    }
    
    $capabilities = $classObj->getSpellCapabilities($level, $wisdomModifier, $maxSpellsLearned, $intelligenceModifier);
    
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

