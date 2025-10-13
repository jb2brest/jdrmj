<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$response = ['success' => false, 'rolls' => []];

$campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);
$show_hidden = filter_input(INPUT_GET, 'show_hidden', FILTER_VALIDATE_BOOLEAN);

if (!$campaign_id) {
    $response['error'] = "ID de campagne manquant ou invalide";
    echo json_encode($response);
    exit();
}

try {
    // Vérifier que l'utilisateur a accès à cette campagne
    $stmt = $pdo->prepare("
        SELECT 1 FROM campaigns c 
        WHERE c.id = ? AND (
            c.dm_id = ? OR 
            EXISTS (SELECT 1 FROM campaign_members cm WHERE cm.campaign_id = c.id AND cm.user_id = ?)
        )
    ");
    $stmt->execute([$campaign_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        $response['error'] = 'Accès non autorisé à cette campagne';
        echo json_encode($response);
        exit();
    }
    
    // Construire la requête selon si on doit afficher les jets masqués ou non
    $where_clause = "WHERE campaign_id = ?";
    $params = [$campaign_id];
    
    if (!$show_hidden) {
        $where_clause .= " AND is_hidden = 0";
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            username,
            dice_type,
            dice_sides,
            quantity,
            results,
            total,
            max_result,
            min_result,
            has_crit,
            has_fumble,
            is_hidden,
            rolled_at
        FROM dice_rolls 
        {$where_clause}
        ORDER BY rolled_at DESC 
        LIMIT 50
    ");
    $stmt->execute($params);
    $rolls = $stmt->fetchAll();
    
    // Formater les résultats
    foreach ($rolls as $roll) {
        $results = json_decode($roll['results'], true);
        
        $response['rolls'][] = [
            'id' => (int)$roll['id'],
            'username' => $roll['username'],
            'dice_type' => $roll['dice_type'],
            'dice_sides' => (int)$roll['dice_sides'],
            'quantity' => (int)$roll['quantity'],
            'results' => $results,
            'total' => (int)$roll['total'],
            'max_result' => (int)$roll['max_result'],
            'min_result' => (int)$roll['min_result'],
            'has_crit' => (bool)$roll['has_crit'],
            'has_fumble' => (bool)$roll['has_fumble'],
            'is_hidden' => (bool)$roll['is_hidden'],
            'rolled_at' => $roll['rolled_at']
        ];
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    error_log("Erreur dans get_dice_rolls_history.php: " . $e->getMessage());
    $response['error'] = 'Erreur serveur';
}

echo json_encode($response);
?>
