<?php
/**
 * API endpoint pour récupérer les entités avec filtres
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/init.php';

// Vérifier l'authentification
requireLogin();

// Vérifier les permissions
if (!User::isDMOrAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// Récupérer les filtres
$filters = [
    'type' => $_GET['type'] ?? '',
    'world' => $_GET['world'] ?? '',
    'country' => $_GET['country'] ?? '',
    'region' => $_GET['region'] ?? '',
    'place' => $_GET['place'] ?? ''
];

$user_id = $_SESSION['user_id'];

try {
    // Utiliser la méthode de classe
    $entities = Lieu::getEntitiesByUser($user_id, $filters);
    
    echo json_encode([
        'success' => true,
        'entities' => $entities,
        'count' => count($entities)
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des entités: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
?>


