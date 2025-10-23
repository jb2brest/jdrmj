<?php
/**
 * API endpoint pour équiper un objet sur un personnage
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);
$characterId = (int)($input['character_id'] ?? 0);
$itemName = $input['item_name'] ?? '';
$itemType = $input['item_type'] ?? '';
$slot = $input['slot'] ?? '';

// Validation
if ($characterId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de personnage invalide']);
    exit();
}

if (empty($itemName) || empty($itemType) || empty($slot)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// Vérifier les permissions
$character = Character::findById($characterId);
if (!$character) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé']);
    exit();
}

$isOwner = $character->belongsToUser($_SESSION['user_id']);
$isDM = isDM();
$isAdmin = User::isAdmin();

if (!$isOwner && !$isDM && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit();
}

// Équiper l'objet
$success = Character::equipItemStatic($characterId, $itemName, $itemType, $slot);

if ($success) {
    echo json_encode([
        'success' => true, 
        'message' => 'Objet équipé avec succès'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'équipement']);
}
?>
