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

if (!$input || !isset($input['place_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de scène manquant']);
    exit();
}

$place_id = (int)$input['place_id'];

// Vérifier que l'utilisateur est le DM de cette scène
$stmt = $pdo->prepare("SELECT c.dm_id FROM places s JOIN campaigns c ON s.campaign_id = c.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

if (!$scene || $scene['dm_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Vous n\'êtes pas le DM de cette scène']);
    exit();
}

try {
    // Remettre tous les pions sur le côté du plan
    $stmt = $pdo->prepare("
        UPDATE place_tokens 
        SET position_x = 0, position_y = 0, is_on_map = FALSE, updated_at = CURRENT_TIMESTAMP
        WHERE place_id = ?
    ");
    
    $stmt->execute([$place_id]);
    
    echo json_encode(['success' => true, 'message' => 'Positions réinitialisées']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la réinitialisation: ' . $e->getMessage()]);
}
?>


