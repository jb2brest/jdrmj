<?php
/**
 * API endpoint pour modifier l'expérience d'un personnage
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
$newXp = (int)($input['new_xp'] ?? 0);

// Validation
if ($characterId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de personnage invalide']);
    exit();
}

if ($newXp < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Expérience invalide']);
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

// Mettre à jour l'expérience
$success = Character::updateExperiencePoints($characterId, $newXp);

if ($success) {
    echo json_encode([
        'success' => true, 
        'message' => 'Expérience mise à jour avec succès',
        'new_xp' => $newXp
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
?>
