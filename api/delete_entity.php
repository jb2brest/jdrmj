<?php
/**
 * API endpoint pour supprimer une entité (PNJ ou monstre)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier l'authentification
requireLogin();

// Vérifier les permissions
if (!User::isDMOrAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// Récupérer les données
$entity_id = (int)($_POST['entity_id'] ?? 0);
$entity_type = $_POST['entity_type'] ?? null;
$user_id = $_SESSION['user_id'];

if ($entity_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID d\'entité invalide']);
    exit;
}

try {
    // Utiliser la méthode de classe avec le type d'entité
    $success = Room::deleteEntity($entity_id, $user_id, $entity_type);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Entité supprimée avec succès']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Entité non trouvée ou permissions insuffisantes']);
    }
} catch (Exception $e) {
    error_log("Erreur lors de la suppression de l'entité: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
?>
