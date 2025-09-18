<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Méthode non autorisée';
    echo json_encode($response);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['error'] = 'Données JSON invalides';
        echo json_encode($response);
        exit();
    }
    
    // Validation des données
    $required_fields = ['campaign_id', 'dice_type', 'dice_sides', 'quantity', 'results', 'total', 'max_result', 'min_result'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            $response['error'] = "Champ manquant: {$field}";
            echo json_encode($response);
            exit();
        }
    }
    
    // Récupérer le statut masqué (optionnel, par défaut false)
    $is_hidden = isset($input['is_hidden']) ? (bool)$input['is_hidden'] : false;
    
    $campaign_id = (int)$input['campaign_id'];
    $dice_type = $input['dice_type'];
    $dice_sides = (int)$input['dice_sides'];
    $quantity = (int)$input['quantity'];
    $results = $input['results'];
    $total = (int)$input['total'];
    $max_result = (int)$input['max_result'];
    $min_result = (int)$input['min_result'];
    
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
    
    // Déterminer si c'est un critique ou un échec critique
    $has_crit = false;
    $has_fumble = false;
    
    if ($dice_sides === 20) {
        $has_crit = in_array(20, $results) ? 1 : 0;
        $has_fumble = in_array(1, $results) ? 1 : 0;
    } else {
        $has_crit = in_array($dice_sides, $results) ? 1 : 0;
    }
    
    // Récupérer le nom d'utilisateur
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $username = $user['username'];
    
    // Sauvegarder le jet de dés
    $stmt = $pdo->prepare("
        INSERT INTO dice_rolls 
        (campaign_id, user_id, username, dice_type, dice_sides, quantity, results, total, max_result, min_result, has_crit, has_fumble, is_hidden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $campaign_id,
        $_SESSION['user_id'],
        $username,
        $dice_type,
        $dice_sides,
        $quantity,
        json_encode($results),
        $total,
        $max_result,
        $min_result,
        $has_crit,
        $has_fumble,
        $is_hidden ? 1 : 0
    ]);
    
    $response['success'] = true;
    $response['roll_id'] = $pdo->lastInsertId();
    
} catch (Exception $e) {
    error_log("Erreur dans save_dice_roll.php: " . $e->getMessage());
    $response['error'] = 'Erreur serveur';
}

echo json_encode($response);
?>
