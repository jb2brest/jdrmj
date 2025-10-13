<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/init.php';

// Vérifier que l'utilisateur est connecté
User::requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de sort invalide']);
    exit;
}

$spellId = (int)$_GET['id'];

try {
    $spell = Sort::findById($spellId);
    
    if (!$spell) {
        echo json_encode(['success' => false, 'message' => 'Sort non trouvé']);
        exit;
    }
    
    echo json_encode(['success' => true, 'spell' => $spell->toArray()]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_spell_details: " . $e->getMessage());
}
?>

