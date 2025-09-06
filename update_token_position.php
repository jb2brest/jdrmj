<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté et est DM
if (!isset($_SESSION['user_id']) || !isDM()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['place_id'], $input['token_type'], $input['entity_id'], $input['position_x'], $input['position_y'], $input['is_on_map'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit();
}

$place_id = (int)$input['place_id'];
$token_type = $input['token_type'];
$entity_id = (int)$input['entity_id'];
$position_x = (int)$input['position_x'];
$position_y = (int)$input['position_y'];
$is_on_map = (bool)$input['is_on_map'];

// Vérifier que l'utilisateur est le DM de cette scène
$stmt = $pdo->prepare("SELECT c.dm_id FROM places s JOIN campaigns c ON s.campaign_id = c.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

if (!$scene || $scene['dm_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Vous n\'êtes pas le DM de cette scène']);
    exit();
}

// Valider le type de token
if (!in_array($token_type, ['player', 'npc', 'monster'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de token invalide']);
    exit();
}

try {
    // Mettre à jour ou créer la position du pion
    $stmt = $pdo->prepare("
        INSERT INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        position_x = VALUES(position_x),
        position_y = VALUES(position_y),
        is_on_map = VALUES(is_on_map),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$place_id, $token_type, $entity_id, $position_x, $position_y, $is_on_map]);
    
    echo json_encode(['success' => true, 'message' => 'Position mise à jour']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
}
?>


