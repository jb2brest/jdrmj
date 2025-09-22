<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Log de début d'appel
error_log("=== UPDATE_TOKEN_POSITION DEBUG ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Vérifier que l'utilisateur est connecté et est DM ou Admin
if (!isset($_SESSION['user_id']) || !isDMOrAdmin()) {
    error_log("ERREUR: Accès refusé - User ID: " . ($_SESSION['user_id'] ?? 'non défini') . ", isDMOrAdmin: " . (isDMOrAdmin() ? 'true' : 'false'));
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

error_log("Input JSON: " . json_encode($input));

if (!$input || !isset($input['place_id'], $input['token_type'], $input['entity_id'], $input['position_x'], $input['position_y'], $input['is_on_map'])) {
    error_log("ERREUR: Paramètres manquants - Input: " . json_encode($input));
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

error_log("Données traitées - place_id: $place_id, token_type: $token_type, entity_id: $entity_id, position_x: $position_x, position_y: $position_y, is_on_map: " . ($is_on_map ? 'true' : 'false'));

// Vérifier que l'utilisateur est le DM de cette scène
$stmt = $pdo->prepare("SELECT c.dm_id FROM places s JOIN place_campaigns pc ON s.id = pc.place_id JOIN campaigns c ON pc.campaign_id = c.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

error_log("Vérification DM - scene: " . json_encode($scene) . ", user_id: " . $_SESSION['user_id']);

if (!$scene || $scene['dm_id'] != $_SESSION['user_id']) {
    error_log("ERREUR: Pas le DM de cette scène - scene: " . json_encode($scene) . ", user_id: " . $_SESSION['user_id']);
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
    error_log("Tentative de sauvegarde en base de données...");
    
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
    
    $result = $stmt->execute([$place_id, $token_type, $entity_id, $position_x, $position_y, $is_on_map]);
    
    error_log("Résultat de l'exécution SQL: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("Nombre de lignes affectées: " . $stmt->rowCount());
    
    // Vérifier que la position a bien été sauvegardée
    $checkStmt = $pdo->prepare("SELECT * FROM place_tokens WHERE place_id = ? AND token_type = ? AND entity_id = ?");
    $checkStmt->execute([$place_id, $token_type, $entity_id]);
    $savedPosition = $checkStmt->fetch();
    
    error_log("Position sauvegardée vérifiée: " . json_encode($savedPosition));
    
    echo json_encode(['success' => true, 'message' => 'Position mise à jour']);
    
} catch (Exception $e) {
    error_log("ERREUR lors de la sauvegarde: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
}
?>


