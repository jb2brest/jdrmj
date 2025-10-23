<?php
/**
 * API endpoint pour supprimer un objet d'un personnage
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
$itemId = (int)($input['item_id'] ?? 0);

// Validation
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID d\'objet invalide']);
    exit();
}

// Vérifier les permissions (vérifier que l'objet appartient à un personnage accessible)
$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT ce.character_id, c.user_id 
    FROM character_equipment ce 
    JOIN characters c ON ce.character_id = c.id 
    WHERE ce.id = ?
");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Objet non trouvé']);
    exit();
}

$isOwner = ($item['user_id'] == $_SESSION['user_id']);
$isDM = isDM();
$isAdmin = User::isAdmin();

if (!$isOwner && !$isDM && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit();
}

// Supprimer l'objet
$success = Character::deleteItem($itemId);

if ($success) {
    echo json_encode([
        'success' => true, 
        'message' => 'Objet supprimé avec succès'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
}
?>
