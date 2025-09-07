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

// Récupérer les positions des pions avec informations de visibilité
$stmt = $pdo->prepare("
    SELECT pt.token_type, pt.entity_id, pt.position_x, pt.position_y, pt.is_on_map, pt.updated_at,
           pn.is_visible, pn.name as entity_name
    FROM place_tokens pt
    LEFT JOIN place_npcs pn ON (
        (pt.token_type = 'npc' AND pt.entity_id = pn.id) OR 
        (pt.token_type = 'monster' AND pt.entity_id = pn.id)
    )
    WHERE pt.place_id = ? 
    AND (
        pt.token_type = 'player' OR 
        (pn.id IS NOT NULL AND pn.is_visible = TRUE)
    )
    ORDER BY pt.updated_at DESC
");
$stmt->execute([$place_id]);
$tokenPositions = [];
while ($row = $stmt->fetch()) {
    $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
        'x' => (int)$row['position_x'],
        'y' => (int)$row['position_y'],
        'is_on_map' => (bool)$row['is_on_map'],
        'is_visible' => $row['token_type'] === 'player' ? true : (bool)$row['is_visible'],
        'entity_name' => $row['entity_name'],
        'updated_at' => $row['updated_at']
    ];
}

// Récupérer aussi les PNJ/monstres masqués pour informer le client qu'ils doivent être cachés
$stmt = $pdo->prepare("
    SELECT pt.token_type, pt.entity_id, pt.position_x, pt.position_y, pt.is_on_map, pt.updated_at,
           pn.is_visible, pn.name as entity_name
    FROM place_tokens pt
    JOIN place_npcs pn ON (
        (pt.token_type = 'npc' AND pt.entity_id = pn.id) OR 
        (pt.token_type = 'monster' AND pt.entity_id = pn.id)
    )
    WHERE pt.place_id = ? 
    AND pn.is_visible = FALSE
    ORDER BY pt.updated_at DESC
");
$stmt->execute([$place_id]);
$hiddenTokens = [];
while ($row = $stmt->fetch()) {
    $hiddenTokens[$row['token_type'] . '_' . $row['entity_id']] = [
        'x' => (int)$row['position_x'],
        'y' => (int)$row['position_y'],
        'is_on_map' => (bool)$row['is_on_map'],
        'is_visible' => false,
        'entity_name' => $row['entity_name'],
        'updated_at' => $row['updated_at']
    ];
}

// Retourner les positions
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'place_id' => $place_id,
    'token_positions' => $tokenPositions,
    'hidden_tokens' => $hiddenTokens,
    'timestamp' => time()
]);
?>
