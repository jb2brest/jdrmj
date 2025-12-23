<?php
// API pour récupérer la configuration des rangs d'un groupe
require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Groupe.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

error_log("get_group_hierarchy.php called");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("isLoggedIn: " . (isLoggedIn() ? 'true' : 'false'));

if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
error_log("Group ID: " . $group_id);

if ($group_id <= 0) {
    error_log("Invalid group ID");
    echo json_encode(['success' => false, 'message' => 'ID de groupe invalide']);
    exit;
}

try {
    $group = Groupe::findById($group_id);
    if (!$group) {
        error_log("Group not found: " . $group_id);
        echo json_encode(['success' => false, 'message' => 'Groupe introuvable']);
        exit;
    }

    $levels = $group->getHierarchyLevelsConfig();
    error_log("Hierarchy levels: " . json_encode($levels));
    
    // Si aucune configuration personnalisée n'existe, on peut renvoyer une config par défaut
    // ou laisser le front gérer. Ici on renvoie ce qu'on a.
    
    // On s'assure d'avoir aussi le max_hierarchy_levels
    $maxLevels = $group->max_hierarchy_levels ?? 5;
    error_log("Max levels: " . $maxLevels);

    echo json_encode([
        'success' => true, 
        'levels' => $levels,
        'max_levels' => $maxLevels
    ]);

} catch (Exception $e) {
    error_log("Exception in get_group_hierarchy: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
