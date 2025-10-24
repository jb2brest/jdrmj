<?php
require_once '../config/database.php';
require_once '../classes/NPC.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['character_id']) || !isset($input['item_name']) || !isset($input['item_type']) || !isset($input['slot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$npcId = (int)$input['character_id'];
$itemName = $input['item_name'];
$itemType = $input['item_type'];
$slot = $input['slot'];

try {
    // Utiliser la nouvelle méthode de la classe NPC
    $result = NPC::equipItem($npcId, $itemName, $itemType, $slot);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
