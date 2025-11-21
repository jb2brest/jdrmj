<?php
/**
 * API Endpoint: Générer des suggestions de noms pour un PNJ
 */

require_once '../includes/functions.php';
require_once '../classes/init.php';
require_once '../classes/NpcNameGenerator.php';

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $race_id = (int)($_POST['race_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $background_id = isset($_POST['background_id']) && $_POST['background_id'] !== '' ? (int)$_POST['background_id'] : null;
    $count = isset($_POST['count']) ? (int)$_POST['count'] : 5;
    
    if (!$race_id || !$class_id) {
        throw new Exception('Race et classe requises');
    }
    
    // Générer les suggestions
    $suggestions = NpcNameGenerator::generateSuggestions($race_id, $class_id, $background_id, $count);
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    error_log("Erreur generate_npc_name.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

