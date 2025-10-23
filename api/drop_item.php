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
    SELECT i.owner_id as character_id, c.user_id 
    FROM items i 
    JOIN characters c ON i.owner_id = c.id 
    WHERE i.id = ? AND i.owner_type = 'player'
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

// Récupérer le lieu du personnage pour déposer l'objet
$stmt = $pdo->prepare("
    SELECT pp.place_id 
    FROM place_players pp 
    JOIN characters c ON pp.character_id = c.id 
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$item['character_id'], $item['user_id']]);
$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place || !$place['place_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le personnage n\'est dans aucun lieu']);
    exit();
}

// Déposer l'objet dans le lieu (mettre à jour place_id et owner_type)
$stmt = $pdo->prepare("
    UPDATE items 
    SET place_id = ?, 
        owner_type = 'place', 
        owner_id = ?, 
        is_visible = 1, 
        is_equipped = 0,
        position_x = 0,
        position_y = 0,
        is_on_map = 0
    WHERE id = ?
");

$success = $stmt->execute([$place['place_id'], $place['place_id'], $itemId]);

if ($success) {
    echo json_encode([
        'success' => true, 
        'message' => 'Objet déposé dans le lieu avec succès'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors du dépôt de l\'objet']);
}
?>
