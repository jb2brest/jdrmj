<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
    exit();
}

$response = ['success' => false];

try {
    // Validation des données
    if (!isset($input['roll_id'])) {
        $response['error'] = "ID du jet manquant";
        echo json_encode($response);
        exit();
    }
    
    $roll_id = (int)$input['roll_id'];
    
    if ($roll_id <= 0) {
        $response['error'] = "ID du jet invalide";
        echo json_encode($response);
        exit();
    }
    
    // Vérifier que le jet existe et récupérer les informations
    $stmt = $pdo->prepare("
        SELECT dr.*, c.dm_id 
        FROM dice_rolls dr 
        JOIN campaigns c ON dr.campaign_id = c.id 
        WHERE dr.id = ?
    ");
    $stmt->execute([$roll_id]);
    $roll = $stmt->fetch();
    
    if (!$roll) {
        $response['error'] = "Jet de dés introuvable";
        echo json_encode($response);
        exit();
    }
    
    // Vérifier que l'utilisateur est le MJ de cette campagne
    if ($roll['dm_id'] != $_SESSION['user_id']) {
        $response['error'] = "Accès non autorisé - seuls les MJ peuvent modifier la visibilité des jets";
        echo json_encode($response);
        exit();
    }
    
    // Basculer le statut masqué
    $stmt = $pdo->prepare("UPDATE dice_rolls SET is_hidden = NOT is_hidden WHERE id = ?");
    $stmt->execute([$roll_id]);
    
    // Récupérer le nouveau statut
    $stmt = $pdo->prepare("SELECT is_hidden FROM dice_rolls WHERE id = ?");
    $stmt->execute([$roll_id]);
    $new_status = $stmt->fetch();
    
    $response['success'] = true;
    $response['is_hidden'] = (bool)$new_status['is_hidden'];
    $response['message'] = $new_status['is_hidden'] ? 'Jet masqué pour les joueurs' : 'Jet rendu visible pour les joueurs';
    
} catch (Exception $e) {
    error_log("Erreur dans toggle_dice_roll_hidden.php: " . $e->getMessage());
    $response['error'] = 'Erreur serveur lors de la modification du statut';
}

echo json_encode($response);
?>
