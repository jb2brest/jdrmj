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
    
    if (!$input || !isset($input['roll_id'])) {
        $response['error'] = 'ID du jet manquant';
        echo json_encode($response);
        exit();
    }
    
    $roll_id = (int)$input['roll_id'];
    
    // Vérifier que l'utilisateur est le MJ de la campagne
    $stmt = $pdo->prepare("
        SELECT dr.campaign_id, c.dm_id 
        FROM dice_rolls dr 
        JOIN campaigns c ON dr.campaign_id = c.id 
        WHERE dr.id = ?
    ");
    $stmt->execute([$roll_id]);
    $roll = $stmt->fetch();
    
    if (!$roll) {
        $response['error'] = 'Jet de dés non trouvé';
        echo json_encode($response);
        exit();
    }
    
    // Vérifier que l'utilisateur est le MJ de cette campagne
    if ($roll['dm_id'] != $_SESSION['user_id']) {
        $response['error'] = 'Seul le MJ peut supprimer des jets de dés';
        echo json_encode($response);
        exit();
    }
    
    // Supprimer le jet de dés
    $stmt = $pdo->prepare("DELETE FROM dice_rolls WHERE id = ?");
    $stmt->execute([$roll_id]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Jet de dés supprimé avec succès';
    } else {
        $response['error'] = 'Erreur lors de la suppression';
    }
    
} catch (Exception $e) {
    error_log("Erreur dans delete_dice_roll.php: " . $e->getMessage());
    $response['error'] = 'Erreur serveur';
}

echo json_encode($response);
?>
