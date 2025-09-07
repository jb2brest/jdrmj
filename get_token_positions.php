<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

// Récupérer les paramètres
$place_id = (int)($_GET['place_id'] ?? 0);

if ($place_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de lieu invalide']);
    exit();
}

// Vérifier que l'utilisateur a accès à ce lieu
$stmt = $pdo->prepare("
    SELECT c.id as campaign_id, c.dm_id 
    FROM places p 
    JOIN campaigns c ON p.campaign_id = c.id 
    WHERE p.id = ? AND (
        c.dm_id = ? OR 
        EXISTS (
            SELECT 1 FROM campaign_applications ca 
            WHERE ca.campaign_id = c.id 
            AND ca.player_id = ? 
            AND ca.status = 'approved'
        )
    )
");
$stmt->execute([$place_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$place = $stmt->fetch();

if (!$place) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé à ce lieu']);
    exit();
}

// Récupérer les positions des pions
$stmt = $pdo->prepare("
    SELECT token_type, entity_id, position_x, position_y, is_on_map, updated_at
    FROM place_tokens 
    WHERE place_id = ?
    ORDER BY updated_at DESC
");
$stmt->execute([$place_id]);
$tokenPositions = [];
while ($row = $stmt->fetch()) {
    $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
        'x' => (int)$row['position_x'],
        'y' => (int)$row['position_y'],
        'is_on_map' => (bool)$row['is_on_map'],
        'updated_at' => $row['updated_at']
    ];
}

// Retourner les positions
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'place_id' => $place_id,
    'token_positions' => $tokenPositions,
    'timestamp' => time()
]);
?>
