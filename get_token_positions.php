<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['place_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit();
}

$place_id = (int)$input['place_id'];
$last_update = $input['last_update'] ?? null;

try {
    // Vérifier que l'utilisateur a accès à ce lieu
    $stmt = $pdo->prepare("
        SELECT p.id, c.id as campaign_id 
        FROM places p 
        JOIN campaigns c ON p.campaign_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();
    
    if (!$place) {
        http_response_code(404);
        echo json_encode(['error' => 'Lieu non trouvé']);
        exit();
    }
    
    // Vérifier que l'utilisateur est membre de la campagne
    $stmt = $pdo->prepare("
        SELECT role FROM campaign_members 
        WHERE campaign_id = ? AND user_id = ?
    ");
    $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
    $membership = $stmt->fetch();
    
    if (!$membership) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
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
    
    $positions = [];
    $latest_timestamp = null;
    
    while ($row = $stmt->fetch()) {
        $tokenKey = $row['token_type'] . '_' . $row['entity_id'];
        $positions[$tokenKey] = [
            'x' => (int)$row['position_x'],
            'y' => (int)$row['position_y'],
            'is_on_map' => (bool)$row['is_on_map']
        ];
        
        // Garder la timestamp la plus récente
        if ($latest_timestamp === null || $row['updated_at'] > $latest_timestamp) {
            $latest_timestamp = $row['updated_at'];
        }
    }
    
    // Si last_update est fourni, vérifier s'il y a des changements
    if ($last_update && $latest_timestamp) {
        $lastUpdateTime = new DateTime($last_update);
        $latestTime = new DateTime($latest_timestamp);
        
        if ($latestTime <= $lastUpdateTime) {
            // Aucun changement depuis la dernière mise à jour
            echo json_encode([
                'success' => true,
                'positions' => [],
                'timestamp' => $latest_timestamp,
                'no_changes' => true
            ]);
            exit();
        }
    }
    
    echo json_encode([
        'success' => true,
        'positions' => $positions,
        'timestamp' => $latest_timestamp
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_token_positions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>